# Changelog

All notable changes to the `nexorams/sdk` PHP package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-10-06

This is a major release because webhook signature verification is stricter. See **Migrating from 1.x** below.

### Breaking
- **`SignatureVerifier::verify` / `Nexora::verifyWebhookSignature` require timestamped signatures by default.** With the default tolerance (300 seconds), only the `t=<unix>,v1=<hex>` format is accepted. Bare HMAC signatures (`v1=<hex>` or raw hex) are rejected unless `toleranceSeconds` is explicitly `0`. Signatures must be 64 hex characters, timestamps must be positive, and a negative tolerance always fails verification.

### Added
- Optional trailing `$idempotencyKey` argument on mutating calls: `users->create`, `webhooks->create`, `rotateSecret`, `disable`, `test`, `deliveries->retry`, `domains->create`, `domains->verify`, and the sector `create*`/`record*` methods (school, hospital, hotel, pharmacy, company). It is sent as the `Idempotency-Key` header.

### Fixed
- The `school`, `hospital`, `hotel`, `pharmacy` and `company` resources are now restored when a serialized `Nexora` client is unserialized.
- The `User-Agent` header now reports the real package version (`nexorams-php/<version>`).

### Changed
- `users->list` is documented as returning the API's paginated shape (`data` and `pagination` keys). The SDK passes the response through unchanged.

### Migrating from 1.x
1. **Webhook verification.** Pass the full `X-Nexora-Signature` header (`t=...,v1=...`). If you still receive legacy signatures without a timestamp, verify them with a tolerance of `0`, which disables replay protection, and plan to drop that path:
   ```php
   Nexora::verifyWebhookSignature($payload, $header, $secret, 0);
   ```
2. **`users->list`.** Read users from `$result['data']`.

## [1.0.1] - 2026-09-10

### Added
- Sector resources on the client: `school` (students, attendance, classes), `hospital` (patients, appointments, vitals), `hotel` (rooms, reservations), `pharmacy` (products) and `company` (employees, attendance, payroll).

## [1.0.0] - 2026-09-09

### Added
- Initial official release of the Nexora PHP SDK (`nexorams/sdk`).
- Client initialization with Bearer API key authentication and automatic environment inference (`nx_test_` -> sandbox, `nx_live_` -> live).
- Shared reusable HTTP transport based on Guzzle with connection pooling, timeouts, correlation IDs (`X-Request-Id`), and idempotency headers (`Idempotency-Key`).
- Comprehensive exception hierarchy: `NexoraException`, `AuthenticationException`, `PermissionException`, `ValidationException`, `RateLimitException`, and `NetworkException`.
- Secret redaction and masking in exceptions, `__debugInfo()`, `var_dump()`, and serialization.
- Resource implementations with full parity to backend `/developer/v1`:
  - `Organizations`: Programmatic provisioning, listing, details, branding updates, module updates, subscription status, and custom domain connection/verification.
  - `Users`: Safe tenant staff/practitioner provisioning with invitation tokens and membership listing.
  - `Modules`: Public feature module catalog inspection, organization module toggles, workspace module entitlements (`projectModules`), and account credit summaries (`credits`) with semantic unlimited handling.
  - `Plans`: Active subscription plan querying with sector and `productContext` filtering.
  - `Subscriptions`: Authoritative organization subscription, active tier, limits, and 30-day trial status inspection.
  - `Domains`: Custom domain white-labeling and DNS verification records.
  - `Webhooks`: Webhook endpoint lifecycle (create, list, get, update, delete, disable, secret rotation, synthetic ping test).
  - `Deliveries`: Audit logs of real-time event delivery attempts, latency metrics, and manual redelivery triggers.
  - `Usage`: Monthly request metrics, status code breakdown, rate limit telemetry, and project workspace profile.
- Cryptographic HMAC-SHA256 webhook signature verification via `SignatureVerifier::verify()` and `Nexora::verifyWebhookSignature()` with constant-time `hash_equals()` comparison and replay defense tolerance window.
- Framework-agnostic design with first-class Laravel, Symfony, and vanilla PHP integration patterns.
- Unit and integration test suites with 100% mocked offline execution.
