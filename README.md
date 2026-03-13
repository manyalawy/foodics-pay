# Foodics Pay — Online Wallet

An online wallet application that receives money via bank webhooks (multiple formats) and generates XML payment requests for outgoing transfers. Built with Laravel, Redis, and Horizon for production-grade scalability.

## Architecture Overview

```
Bank Webhook → Auth Middleware → Queue Job → Parser → Database
                (API Key + HMAC + Client Token)     (insertOrIgnore)

Transfer Request → Validation → DTO → XML Builder → XML Response
```

### Key Design Decisions

- **Strategy Pattern for Parsers**: Each bank has its own parser implementing `BankParserInterface`. Adding a new bank requires only a new parser class and a one-line registration in `BankParserFactory`.
- **Queue-First Ingestion**: Webhooks are accepted immediately (202) and processed asynchronously via Redis/Horizon. This ensures low latency for webhook responses even under heavy load.
- **Idempotency**: Transactions use a composite unique constraint `(bank_id, reference)` with `insertOrIgnore()` for safe duplicate handling.
- **Three-Layer Authentication**: API key (bank identity) → HMAC-SHA256 (payload integrity) → Client token (client identity).
- **Security**: API keys stored as SHA-256 hashes, webhook secrets encrypted at rest via Laravel's `encrypted` cast.

## Setup

### Requirements

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Composer

### Installation

```bash
# Start MySQL and Redis
docker-compose up -d

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start Horizon (queue worker)
php artisan horizon
```

### Running Tests

```bash
php artisan test
```

Tests use SQLite in-memory database and sync queue driver — no external services needed.

## API Endpoints

### POST /api/webhooks

Receives bank webhook data. Requires three authentication headers.

**Headers:**
| Header | Description |
|--------|-------------|
| `Authorization` | `Bearer {api_key}` — identifies the bank |
| `X-Signature` | HMAC-SHA256 of request body using bank's webhook secret |
| `X-Client-Token` | Identifies the client receiving funds |

**Body:** Raw text in bank-specific format (see Bank Formats below).

**Response:** `202 Accepted`

### POST /api/transfers

Generates an XML payment request.

**Body (JSON):**
```json
{
    "reference": "REF001",
    "date": "2025-06-15",
    "amount": 1500.00,
    "currency": "SAR",
    "sender_account": "SA1234567890",
    "receiver_bank_code": "RJHI",
    "receiver_account": "SA0987654321",
    "beneficiary_name": "John Doe",
    "notes": ["Payment for invoice 123"],
    "payment_type": 1,
    "charge_details": "OUR"
}
```

Optional fields: `notes` (omitted from XML if empty), `payment_type` (omitted if 99), `charge_details` (omitted if "SHA").

**Response:** `200 OK` with `Content-Type: application/xml`

### Ingestion Control

- `POST /api/ingestion/pause` — Pauses webhook processing
- `POST /api/ingestion/resume` — Resumes webhook processing
- `GET /api/ingestion/status` — Returns `{"paused": true|false}`

Also available as artisan commands:
```bash
php artisan ingestion:pause
php artisan ingestion:resume
```

## Bank Formats

### Foodics Bank

```
{YYYYMMDD}{amount}#{reference}#{key/value/key/value}
```

Example:
```
20250615156,50#202506159000001#note/debt payment march/internal_reference/A462JE81
```

### Acme Bank

```
{amount}//{reference}//{YYYYMMDD}
```

Example:
```
156,50//202506159000001//20250615
```

Both formats: amounts use comma as decimal separator, multiple transactions separated by newlines.

## Adding a New Bank

1. Create a parser class implementing `BankParserInterface`:

```php
// app/Services/Parsers/NewBankParser.php
class NewBankParser implements BankParserInterface
{
    public function parse(string $rawBody): Collection
    {
        // Parse the bank's specific format
        // Return Collection of TransactionData DTOs
    }
}
```

2. Register in `BankParserFactory`:

```php
private array $parsers = [
    'foodics' => FoodicsBankParser::class,
    'acme' => AcmeBankParser::class,
    'newbank' => NewBankParser::class,  // Add this line
];
```

3. Create a bank record in the database with the new bank's credentials.

## Project Structure

```
app/
├── Contracts/BankParserInterface.php      # Parser contract
├── DTOs/
│   ├── TransactionData.php                # Webhook transaction DTO
│   └── PaymentRequestData.php             # Transfer request DTO
├── Http/
│   ├── Controllers/Api/
│   │   ├── WebhookController.php          # Webhook endpoint
│   │   ├── TransferController.php         # XML transfer endpoint
│   │   └── IngestionController.php        # Pause/resume control
│   ├── Middleware/
│   │   └── VerifyBankWebhook.php          # Three-layer auth
│   └── Requests/
│       └── TransferRequest.php            # Transfer validation
├── Jobs/
│   └── ProcessWebhookJob.php              # Async webhook processing
├── Models/
│   ├── Bank.php
│   ├── Client.php
│   └── Transaction.php
└── Services/
    ├── Parsers/
    │   ├── BankParserFactory.php           # Resolves parser by bank name
    │   ├── FoodicsBankParser.php
    │   └── AcmeBankParser.php
    └── PaymentXmlBuilder.php              # XML generation
```

## Scalability

- **Horizon** manages Redis-backed queue workers with auto-scaling
- **Chunked inserts** (batches of 500) prevent memory issues on large webhooks
- **Cached lookups** for bank and client authentication reduce database load
- **Pause/resume** mechanism allows controlled ingestion during maintenance
- Performance tested: 1,000 transactions process in < 1 second
