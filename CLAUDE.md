# Foodics Pay — Online Wallet

## Project Overview

Laravel application that receives money via bank webhooks (multiple formats) and generates XML payment requests for outgoing transfers. Built for scalability with Redis/Horizon queue processing.

## Commands

- **Run tests:** `php artisan test`
- **Run single test:** `php artisan test --filter=TestClassName`
- **Code formatting:** `./vendor/bin/pint`
- **Check formatting:** `./vendor/bin/pint --test`
- **Static analysis:** `./vendor/bin/phpstan analyse`
- **Queue worker:** `php artisan horizon`

## Architecture

- **Strategy Pattern** for bank parsers — each bank implements `BankParserInterface`
- **Queue-First Ingestion** — webhooks return 202 immediately, processed async via `ProcessWebhookJob`
- **Three-Layer Auth** — API key (bank identity) + HMAC-SHA256 (payload integrity) + X-Client-Token (client identity)
- **Idempotency** — composite unique `(bank_id, reference)` with `insertOrIgnore()`

## Key Directories

- `app/Contracts/` — Interfaces (BankParserInterface)
- `app/DTOs/` — Data Transfer Objects (TransactionData, PaymentRequestData)
- `app/Services/Parsers/` — Bank-specific parsers + factory
- `app/Services/PaymentXmlBuilder.php` — XML generation for transfers
- `app/Http/Middleware/VerifyBankWebhook.php` — Webhook authentication
- `app/Jobs/ProcessWebhookJob.php` — Async webhook processing

## Conventions

- Follow Laravel conventions and PSR-12
- Use `./vendor/bin/pint` for code style (Laravel Pint preset)
- Tests use SQLite in-memory (`phpunit.xml` configured)
- Queue connection: Redis via Predis
- Bank webhook secrets use Laravel's `encrypted` cast
- API keys stored as SHA-256 hashes, never raw

## Adding a New Bank

1. Create parser in `app/Services/Parsers/` implementing `BankParserInterface`
2. Register in `BankParserFactory::$parsers` array
3. Add tests in `tests/Unit/`
