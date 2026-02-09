# osTicket Plugin Update Checker

Checks GitHub for available updates of **all** installed osTicket plugins and shows update badges directly in the plugin list.

Works with any plugin that has a GitHub URL in its `plugin.php` - not just plugins by a specific developer.

## Screenshot

```
Installed Plugins

Plugin Name (instances)      Version              Status      Date Installed
----------------------------------------------------------------------
Markdown Support (2)         1.0.0  [1.2.0]       Enabled     2025-01-10
                                    ^^^^^^^ clickable badge

API Endpoints (3)            1.1.0  [1.1.2]       Enabled     2025-01-05

Priority Icons (1)           1.0.3                 Enabled     2025-01-08
                                    (no badge = up to date)
```

## Requirements

- osTicket 1.18.x
- PHP 8.1+
- PHP cURL extension

## Installation

1. Download the [latest release](https://github.com/markus-michalski/osticket-plugin-update-check/releases)
2. Extract to `include/plugins/update-check/`
3. Run `composer install --no-dev`
4. Enable in Admin Panel > Manage > Plugins

## Configuration

| Option | Default | Description |
|--------|---------|-------------|
| Cache Duration | 6 hours | How long to cache GitHub API responses |
| GitHub Token | (empty) | Optional Personal Access Token for higher rate limits |

## Plugin Prerequisites

For a plugin to be checked, its `plugin.php` must have a GitHub URL and the developer must publish GitHub Releases:

```php
<?php return [
    'id'      => 'vendor:plugin-name',
    'version' => '1.0.0',
    'name'    => 'My Plugin',
    'url'     => 'https://github.com/user/repo',  // Required!
    'plugin'  => 'class.MyPlugin.php:MyPlugin',
];
```

## Documentation

Full documentation (installation, configuration, troubleshooting, technical details):

- [English](https://faq.markus-michalski.net/osticket/update-check)
- [Deutsch](https://faq.markus-michalski.net/de/osticket/update-check)

## Development

```bash
composer install
vendor/bin/phpunit
```

## License

GPL-2.0-or-later (compatible with osTicket)

## Author

Markus Michalski
