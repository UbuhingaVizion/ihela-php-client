# Contributing

Thank you for considering contributing to iHela PHP Client.

## Development Setup

Clone the repository and install dependencies:

```bash
git clone git@github.com:UbuhingaVizion/ihela-php-client.git
cd ihela-php-client
composer install
```

## Running Checks

```bash
composer test          # PHPUnit
composer lint          # PHP CS Fixer (dry run)
composer lint-fix      # PHP CS Fixer (auto-fix)
composer analyse       # PHPStan static analysis
composer check         # All of the above
composer audit         # Security advisory scan
```

## Coding Standards

- PSR-12 code style via PHP CS Fixer
- PHPStan level-max static analysis (strict types)
- `declare(strict_types=1)` in all source files

## Testing

Write tests using PHPUnit 10. Mock the PSR-18 HTTP layer — no live API
calls. Use `Nyholm\Psr7` for PSR-7 request/response factories in tests.

```bash
composer test
```

## Pull Request Process

1. Create a feature branch from `develop`
2. Add or update tests covering your changes
3. Run `composer check` locally and ensure everything passes
4. Update `CHANGELOG.md` under Unreleased
5. Open a PR against `develop`

## Release Process

Releases are tagged on `develop` and merged to `master` simultaneously.
GitHub Actions publishes the tag to Packagist automatically.
