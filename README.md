# Foodics Pay — Online Wallet

Laravel application that receives money via bank webhooks (multiple formats) and generates XML payment requests for outgoing transfers. Built for scalability with Redis/Horizon queue processing.

**Key highlights:** queue-first ingestion, three-layer auth, idempotency, strategy-pattern parsers, circuit breaker resilience.

## Tech Stack

- PHP 8.2+
- Laravel 12
- MySQL 8.0+
- Redis 6.0+
- Laravel Horizon 5.45+ (queue dashboard & worker)
- Predis 3.4+ (Redis client)

## Setup / Installation

```bash
git clone <repo-url> && cd foodics-pay
composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env` with database and Redis credentials, then:

```bash
php artisan migrate
php artisan db:seed
php artisan serve       # starts the web server at http://localhost:8000
php artisan horizon     # run in a separate terminal — processes queued jobs
```

### Seeded Test Credentials

| Resource               | Value                          |
|------------------------|--------------------------------|
| Foodics Bank API Key   | `foodics-api-key-2025`         |
| Foodics Webhook Secret | `foodics-webhook-secret-2025`  |
| Acme Bank API Key      | `acme-api-key-2025`            |
| Acme Webhook Secret    | `acme-webhook-secret-2025`     |
| Client Token           | `client-webhook-token-2025`    |

### Example Requests

**Send a Foodics webhook:**
```bash
BODY='20250615156,50#202506159000099#note/test payment'
SIGNATURE=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "foodics-webhook-secret-2025" | awk '{print $2}')

curl -X POST http://localhost:8000/api/webhooks \
  -H "Authorization: Bearer foodics-api-key-2025" \
  -H "X-Signature: $SIGNATURE" \
  -H "X-Client-Token: client-webhook-token-2025" \
  -H "Content-Type: text/plain" \
  -d "$BODY"
```

**Generate an XML payment:**
```bash
curl -X POST http://localhost:8000/api/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "reference": "REF001",
    "date": "2025-06-15",
    "amount": 1500.00,
    "currency": "SAR",
    "sender_account": "SA1234567890",
    "receiver_bank_code": "RJHI",
    "receiver_account": "SA0987654321",
    "beneficiary_name": "John Doe"
  }'
```

## Architecture Overview

```
Webhook Request
  │
  ▼
VerifyBankWebhook Middleware
  ├─ 1. Payload size check (413 if too large)
  ├─ 2. API key lookup via SHA-256 hash (401 if invalid)
  ├─ 3. HMAC-SHA256 signature verification (403 if mismatch)
  └─ 4. Client token lookup via SHA-256 hash (401 if invalid)
  │
  ▼
WebhookController → returns 202 Accepted immediately
  │
  ▼
ProcessWebhookJob (Redis/Horizon queue)
  ├─ Parse raw body via bank-specific parser (strategy pattern)
  ├─ Batch insert transactions in chunks of 500
  └─ insertOrIgnore() for idempotency (unique: bank_id + reference)
```

```
Transfer Request → Validation → PaymentRequestData DTO → XML Builder → XML Response
```

- **Strategy Pattern** — each bank implements `BankParserInterface` via `AbstractBankParser`
- **Queue-First Ingestion** — webhooks return 202 immediately, processed asynchronously
- **Idempotency** — composite unique constraint `(bank_id, reference)` with `insertOrIgnore()`

## API Endpoints

### POST /api/webhooks

Receives bank webhook data. Requires three authentication headers.

**Headers:**

| Header          | Description                                                    |
|-----------------|----------------------------------------------------------------|
| `Authorization` | `Bearer {api_key}` — identifies the bank                      |
| `X-Signature`   | HMAC-SHA256 of request body using the bank's webhook secret    |
| `X-Client-Token`| Identifies the client receiving funds                          |

**Body:** Raw text in bank-specific format (one transaction per line).

**Responses:**
- `202 Accepted` — webhook queued for processing
- `401 Unauthorized` — invalid API key or client token
- `403 Forbidden` — invalid HMAC signature
- `413 Payload Too Large` — body exceeds `max_body_size`

### POST /api/transfers

Generates an XML payment request.

**Body (JSON):**

| Field               | Type    | Required | Default |
|---------------------|---------|----------|---------|
| `reference`         | string  | yes      |         |
| `date`              | string  | yes      | (Y-m-d) |
| `amount`            | numeric | yes      | (min 0.01) |
| `currency`          | string  | yes      | (3 chars) |
| `sender_account`    | string  | yes      |         |
| `receiver_bank_code`| string  | yes      |         |
| `receiver_account`  | string  | yes      |         |
| `beneficiary_name`  | string  | yes      |         |
| `notes`             | array   | no       | `[]`    |
| `payment_type`      | integer | no       | `99`    |
| `charge_details`    | string  | no       | `"SHA"` |

Optional fields omitted from XML when at default values: `notes` (empty), `payment_type` (99), `charge_details` ("SHA").

**Note:** No authentication, authorization, or database logic was implemented for the transfer endpoint, as it was not in the scope of the assignment.

**Response:** `200 OK` with `Content-Type: application/xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<PaymentRequestMessage>
  <TransferInfo>
    <Reference>REF001</Reference>
    <Date>2025-06-15</Date>
    <Amount>1500.00</Amount>
    <Currency>SAR</Currency>
  </TransferInfo>
  <SenderInfo>
    <AccountNumber>SA1234567890</AccountNumber>
  </SenderInfo>
  <ReceiverInfo>
    <BankCode>RJHI</BankCode>
    <AccountNumber>SA0987654321</AccountNumber>
    <BeneficiaryName>John Doe</BeneficiaryName>
  </ReceiverInfo>
  <Notes>
    <Note>Payment for invoice #123</Note>
  </Notes>
</PaymentRequestMessage>
```

### Ingestion Control

```
POST /api/ingestion/pause    → {"message": "Ingestion paused."}
POST /api/ingestion/resume   → {"message": "Ingestion resumed."}
GET  /api/ingestion/status   → {"paused": true|false}
```

Also available as artisan commands:
```bash
php artisan ingestion:pause
php artisan ingestion:resume
```

## Authentication

The `VerifyBankWebhook` middleware applies a four-step auth chain to webhook requests:

1. **Payload Size** — rejects bodies exceeding `max_body_size` (default 1 MB) with `413`
2. **API Key** — `Authorization: Bearer <key>` hashed with SHA-256, looked up in `banks.api_key_hash` (cached 5 min); `401` if invalid
3. **HMAC-SHA256 Signature** — `X-Signature` header verified against `hash_hmac('sha256', body, webhook_secret)` using `hash_equals()` for timing-attack protection; `403` if mismatch
4. **Client Token** — `X-Client-Token` header hashed with SHA-256, looked up in `clients.webhook_token_hash` (cached 5 min); `401` if invalid

On success, `bank` and `client` models are attached to the request as attributes.

## Bank Parsers

Each bank implements `BankParserInterface` via `AbstractBankParser`. Parsers are registered in `config/banks.php`.

### Foodics Format

```
{YYYYMMDD}{amount}#{reference}#{key/value/key/value...}
```

Example:
```
20250615156,50#202506159000001#note/debt payment march/internal_reference/A462JE81
```

- Date: first 8 characters (YYYYMMDD)
- Amount: remaining characters before first `#` (comma as decimal separator)
- Reference: second segment
- Metadata: optional third segment (slash-separated key/value pairs)

### Acme Format

```
{amount}//{reference}//{YYYYMMDD}
```

Example:
```
156,50//202506159000001//20250615
```

- Amount: first segment (comma as decimal separator)
- Reference: second segment
- Date: third segment (YYYYMMDD)

Both formats use comma as decimal separator. Multiple transactions are separated by newlines.

### Adding a New Bank

1. Create a parser in `app/Services/Parsers/` extending `AbstractBankParser`
2. Implement `parseLine(string $line): TransactionData`
3. Register in `config/banks.php` parsers array:
   ```php
   'parsers' => [
       'foodics' => FoodicsBankParser::class,
       'acme'    => AcmeBankParser::class,
       'newbank' => NewBankParser::class,
   ],
   ```
4. Create a bank record in the database with the new bank's credentials
5. Add tests in `tests/Unit/`

## Queue Processing

- **Job:** `ProcessWebhookJob` dispatched to the `webhooks` queue
- **Retries:** 3 attempts with backoff: 10s, 30s, 60s
- **Batch inserts:** chunks of 500 with `insertOrIgnore()` for idempotency
- **Ingestion pause:** when `ingestion_paused` cache flag is set, job self-releases after 30s
- **Circuit breaker:** after N failures (default 10) within M minutes (default 5), ingestion auto-pauses and logs a critical alert. Resume with `POST /api/ingestion/resume` or `php artisan ingestion:resume`.

### Parse Error Tracking

The `ParseResult` DTO collects per-line errors with line number, truncated input, and error message. When the fraction of failed lines exceeds `malformed_line_alert_threshold` (default 50%), an error-level log is emitted.

## Configuration Reference

### config/webhook.php

| Key | Env Variable | Default | Description |
|-----|-------------|---------|-------------|
| `max_body_size` | `WEBHOOK_MAX_BODY_SIZE` | `1048576` (1 MB) | Max webhook payload in bytes |
| `circuit_breaker_threshold` | `CIRCUIT_BREAKER_THRESHOLD` | `10` | Failures before auto-pause |
| `circuit_breaker_window_minutes` | `CIRCUIT_BREAKER_WINDOW_MINUTES` | `5` | Sliding window in minutes |
| `malformed_line_alert_threshold` | `MALFORMED_LINE_ALERT_THRESHOLD` | `0.5` | Failed-line fraction triggering error log |

### config/banks.php

Maps bank names to parser classes:

```php
'parsers' => [
    'foodics' => FoodicsBankParser::class,
    'acme'    => AcmeBankParser::class,
],
```

## Database Schema

### banks

| Column         | Type        | Constraints              |
|----------------|-------------|--------------------------|
| `id`           | bigint      | PK, auto-increment       |
| `name`         | string      | unique                   |
| `api_key_hash` | string(64)  | unique (SHA-256)         |
| `webhook_secret` | text      | encrypted at rest        |
| `created_at`   | timestamp   |                          |
| `updated_at`   | timestamp   |                          |

### clients

| Column              | Type       | Constraints              |
|---------------------|------------|--------------------------|
| `id`                | bigint     | PK, auto-increment       |
| `name`              | string     |                          |
| `webhook_token_hash`| string(64) | unique (SHA-256)         |
| `created_at`        | timestamp  |                          |
| `updated_at`        | timestamp  |                          |

### transactions

| Column      | Type          | Constraints                       |
|-------------|---------------|-----------------------------------|
| `id`        | bigint        | PK, auto-increment                |
| `client_id` | bigint        | FK → clients, cascade delete      |
| `bank_id`   | bigint        | FK → banks, cascade delete        |
| `reference` | string        |                                   |
| `amount`    | decimal(15,2) |                                   |
| `date`      | date          |                                   |
| `metadata`  | json          | nullable                          |
| `created_at`| timestamp     |                                   |
| `updated_at`| timestamp     |                                   |

**Unique constraint:** `(bank_id, reference)` — idempotency key

## Project Structure

```
app/
├── Contracts/                    # Interfaces (BankParserInterface)
├── DTOs/                         # TransactionData, PaymentRequestData, ParseResult
├── Http/
│   ├── Controllers/Api/          # WebhookController, TransferController, IngestionController
│   ├── Middleware/                # VerifyBankWebhook
│   └── Requests/                 # TransferRequest
├── Jobs/                         # ProcessWebhookJob
├── Models/                       # Bank, Client, Transaction
└── Services/
    ├── Parsers/                  # AbstractBankParser, FoodicsBankParser, AcmeBankParser, BankParserFactory
    └── PaymentXmlBuilder.php     # XML generation for transfers
config/
├── banks.php                     # Parser class map
├── webhook.php                   # Webhook & circuit breaker config
└── horizon.php                   # Queue worker config
database/
├── migrations/                   # Schema definitions
└── seeders/                      # Demo data
routes/
├── api.php                       # Route loader
├── webhooks.php                  # POST /api/webhooks
├── transfers.php                 # POST /api/transfers
└── ingestion.php                 # Ingestion control routes
tests/
├── Unit/                         # Parser, job, DTO, XML builder tests
├── Feature/                      # Controller & integration tests
└── Performance/                  # Load & idempotency tests
```

## Development

```bash
php artisan test                    # Run all tests
php artisan test --filter=ClassName # Run specific test
./vendor/bin/pint                   # Format code (Laravel Pint)
./vendor/bin/pint --test            # Check formatting
./vendor/bin/phpstan analyse        # Static analysis (Larastan)
php artisan horizon                 # Start queue worker
```

## Testing

Tests span Unit, Feature, and Performance suites using SQLite in-memory for speed (no external services needed).

- **Unit tests** — parsers, DTOs, XML builder, job logic
- **Feature tests** — webhook auth chain, transfer endpoint, ingestion control, ingestion pause behavior
- **Performance tests** — 1,000 transactions processed under 2 seconds, idempotency under concurrent load

## Security

- **API keys** stored as SHA-256 hashes, never in plaintext
- **Webhook secrets** encrypted at rest via Laravel's `encrypted` cast
- **HMAC verification** with `hash_equals()` for timing-attack protection
- **Client tokens** stored as SHA-256 hashes
- **XML escaping** via `htmlspecialchars(ENT_XML1)` to prevent injection
- **Payload size limits** to prevent abuse (default 1 MB)
- **Composite unique constraints** for transaction idempotency
