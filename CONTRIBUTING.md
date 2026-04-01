# Contributing to Plugin Update Checker

## Development Setup

```bash
git clone https://github.com/markus-michalski/osticket-plugin-update-check.git
cd osticket-plugin-update-check
composer install
```

## Requirements

- PHP 8.1+
- PHP cURL extension
- osTicket 1.18.x (for integration testing)
- Composer

## Code Style

- **Language:** PHP 8.1+
- **Standard:** PSR-12
- **Static Analysis:** PHPStan (`./vendor/bin/phpstan`)
- **Comments:** English
- **Testing:** PHPUnit 11 with PHP 8 attributes (`#[Test]`, `#[CoversClass]`)

## Architecture

```
plugin.php                          # Plugin metadata
class.UpdateCheckPlugin.php         # Main plugin class
config.php                          # Configuration (cache duration, GitHub token)
src/                                # Source code
tests/                              # PHPUnit tests
```

### How It Works

The plugin reads `plugin.php` metadata from all installed plugins, checks for a GitHub URL, and queries the GitHub Releases API for newer versions. Results are cached for the configured duration.

## Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Static analysis
./vendor/bin/phpstan analyse
```

## CI

GitHub Actions on push/PR to main: PHPUnit + PHPStan.

## Commits

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add new feature
fix: correct a bug
docs: update documentation
refactor: restructure code
test: add or modify tests
```

## Pull Requests

1. Create a feature branch from `main`
2. Make your changes
3. Ensure all tests and PHPStan pass
4. Open a PR with a clear description

## License

GPL-2.0, compatible with osTicket core.
