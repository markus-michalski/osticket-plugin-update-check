# osTicket Plugin Update Checker

Checks GitHub for available updates of installed osTicket plugins and shows update badges directly in the plugin list.

## Features

- Automatic GitHub Releases API check for all plugins with GitHub URL
- Update badge with version number in the plugin list
- Click on badge opens GitHub release page
- File-based caching (6h default, configurable)
- Optional GitHub Personal Access Token for higher rate limits
- Zero core file modifications
- PJAX-compatible
- XSS-safe URL validation

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
- PHP 8.1 or higher
- PHP cURL extension
- Modern browser (ES6+ support)

## Installation

### Manual Installation

1. Download the latest release
2. Extract to `include/plugins/update-check/`
3. Run `composer install --no-dev` in the plugin directory
4. Navigate to Admin Panel > Manage > Plugins
5. Find "Plugin Update Checker" and click "Install"
6. Enable the plugin

### Directory Structure

```
include/plugins/update-check/
├── plugin.php                      # Plugin metadata
├── class.UpdateCheckPlugin.php     # Main plugin class
├── config.php                      # Configuration form
├── src/
│   ├── AssetInjector.php           # Output buffer injection
│   ├── FileCache.php               # File-based cache
│   ├── GitHubReleaseChecker.php    # GitHub API client
│   └── PluginUpdateCollector.php   # Update data collector
├── assets/
│   ├── update-badges.css           # Badge styling
│   └── update-badges.js            # DOM manipulation
├── vendor/                         # Composer autoload
└── README.md
```

## Configuration

After installation, configure the plugin in Admin Panel > Manage > Plugins > Plugin Update Checker:

| Option | Default | Description |
|--------|---------|-------------|
| Cache Duration | 6 hours | How long to cache GitHub API responses |
| GitHub Token | (empty) | Optional Personal Access Token for higher rate limits |

### GitHub Rate Limits

| Mode | Limit | Recommendation |
|------|-------|----------------|
| Without token | 60 requests/hour | Fine for < 10 plugins |
| With token | 5,000 requests/hour | Recommended for many plugins |

## How It Works

1. Plugin's `bootstrap()` detects if user is on `/scp/plugins.php`
2. On other pages: returns immediately (zero performance impact)
3. On plugins page: collects update data for all plugins with GitHub URLs
4. GitHub API responses are cached in temp directory (configurable TTL)
5. Output buffer injects CSS and JavaScript before page closes
6. JavaScript reads update data and adds badges to plugin table rows

## Plugin Prerequisites

For a plugin to receive update checks, its `plugin.php` must contain a GitHub URL:

```php
<?php return [
    'id'      => 'vendor:plugin-name',
    'version' => '1.0.0',
    'name'    => 'My Plugin',
    'url'     => 'https://github.com/user/repo',  // Required!
    'plugin'  => 'class.MyPlugin.php:MyPlugin',
];
```

Supported URL formats:
- `https://github.com/user/repo`
- `https://github.com/user/repo.git`
- `http://github.com/user/repo`

The plugin developer must create GitHub Releases (tagged versions) for updates to be detected.

## Technical Details

### Output Buffer Injection

Uses the same proven pattern as the Priority Icons plugin:

```php
ob_start(function (string $buffer) use ($injector, $updateData): string {
    return $injector->inject($buffer, $updateData);
});
```

### Security

- URL validation in JavaScript (only `http(s)` protocols)
- JSON encoding with `JSON_HEX_TAG | JSON_HEX_APOS` (XSS prevention)
- Explicit SSL verification for GitHub API calls
- try-catch in output buffer callback (prevents white screen on errors)
- No database modifications

### Performance

- Zero overhead on non-plugin pages (early return in `bootstrap()`)
- File-based cache prevents redundant API calls
- 5 second cURL timeout per request
- Cached responses served in < 1ms

## Development

### Running Tests

```bash
composer install
vendor/bin/phpunit
```

### Test Coverage

- `FileCache` - set/get/TTL/clear/corruption handling
- `GitHubReleaseChecker` - URL parsing, version comparison
- `AssetInjector` - HTML injection, XSS prevention, double-injection guard

## Troubleshooting

### No Badges Appearing

1. Verify plugin is enabled in Admin Panel
2. Check that plugins have `url` field in their `plugin.php`
3. Check that GitHub repositories have published Releases
4. Check PHP error log for `[UpdateCheck]` messages
5. Clear cache: delete files in system temp dir (`/tmp/osticket-update-check/`)

### Rate Limit Issues

If badges stop appearing after many checks:
1. Wait 1 hour (error cache TTL)
2. Or configure a GitHub Personal Access Token in plugin settings

## License

GPL-2.0-or-later (compatible with osTicket)

## Author

Markus Michalski
