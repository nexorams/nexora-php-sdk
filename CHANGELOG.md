# Changelog

All notable changes to the `nexorams/sdk` PHP package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
