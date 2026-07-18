# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - Unreleased

### Added
- Full parity with Python ihela-sdk v0.4.0 API surface
- `Ihela\Merchant\MerchantClient` — bill init/verify, cash-in, bank lists, customer lookup
- `Ihela\Auth\MerchantAuthorizationClient` — OAuth2 authorization-code SSO flow
- `Ihela\Banking\BankingClient` — deposits, withdrawals, account lookup, balance, statements, transaction fees/status
- `Ihela\Agent\AgentClient` — agent deposit, operation lookup, withdrawal validation, transaction status
- `Ihela\Core\BaseClient` — connection pooling, circuit breaker, rate limiter, retry with backoff
- `Ihela\Core\Exception\*` — typed exceptions with `isRetryable()` support
- `Ihela\Core\Security\*` — HMAC-SHA256 request signing, sensitive-data masking
- `Ihela\Dto\*` — validated DTOs for deposit, withdrawal, withdrawal validation payloads
- PSR-18 HTTP client abstraction via `php-http/discovery` (framework-agnostic)
- PHPUnit 10 test suite with mocked HTTP layer
- PHPStan level-max static analysis
- PHP CS Fixer (PSR-12) linting
- MkDocs documentation site
- Security disclosure policy (`SECURITY.md`)
- `composer audit` CI enforcement
- Dependabot configuration

### Changed
- Minimum PHP version raised to 8.1 (was 7.1)
- Gateway endpoints updated to current iHela API (`gate.ihela.online` / `testgate.ihela.online`)
- Replaced `league/oauth2-client` with direct PSR-18 HTTP client
- Namespace restructured from `Ihela\Merchant\IhelaMerchant` to `Ihela\Merchant\MerchantClient`

### Removed
- Legacy `Ihela\Merchant\IhelaMerchant` class (obsolete endpoints)

### Security
- All API traffic via HTTPS; plain-HTTP only for local development
- HMAC-SHA256 request signing support (`X-iHela-Signature`)
- Sensitive data masking before logging
- DTO input validation for financial payloads
- Hardcoded credentials scrubbed from repository

## [0.1.1] - 2023-01-23

### Fixed
- Echo errors on production

## [0.0.7] - 2021-10-16

### Added
- League OAuth2 Client integration
- Bill init/verify features
- Cash-in, bank lists, customer lookup

## [0.0.1] - 2020-09-06

### Added
- Initial release with basic merchant bill handling
