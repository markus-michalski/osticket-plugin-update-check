<?php
/**
 * Plugin Update Checker - Main Class
 *
 * Checks GitHub for available updates of installed plugins and shows
 * update badges in the plugin list using output buffer injection.
 *
 * @package    osTicket\Plugins\UpdateCheck
 * @author     Markus Michalski
 * @version    0.1.0
 * @license    GPL-2.0-or-later
 */

declare(strict_types=1);

if (!class_exists('Plugin')) {
    require_once INCLUDE_DIR . 'class.plugin.php';
}

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Autoload src/ classes
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Mmd\UpdateCheck\AssetInjector;
use Mmd\UpdateCheck\FileCache;
use Mmd\UpdateCheck\GitHubReleaseChecker;
use Mmd\UpdateCheck\PluginUpdateCollector;

class UpdateCheckPlugin extends Plugin
{
    public $config_class = 'UpdateCheckConfig';

    function isSingleton(): bool
    {
        return true;
    }

    function enable()
    {
        $errors = [];

        if ($this->isSingleton() && $this->getNumInstances() === 0) {
            $vars = [
                'name' => $this->getName(),
                'isactive' => 1,
                'notes' => 'Auto-created singleton instance',
            ];

            if (!$this->addInstance($vars, $errors)) {
                return $errors;
            }
        }
    }

    public function bootstrap(): void
    {
        // Only activate on the plugins list page
        if (!$this->isPluginsPage()) {
            return;
        }

        // Skip AJAX requests
        if ($this->isAjaxRequest()) {
            return;
        }

        // Read config
        $cacheTtl = 6;
        $githubToken = '';

        try {
            $config = $this->getInstanceConfig();
            if ($config) {
                $cacheTtl = max(1, (int) ($config->get('cache_ttl') ?: 6));
                $githubToken = trim((string) ($config->get('github_token') ?: ''));
            }
        } catch (\Throwable $e) {
            // Use defaults
        }

        // Build services
        $cache = new FileCache(
            sys_get_temp_dir() . '/osticket-update-check'
        );
        $checker = new GitHubReleaseChecker($cache, $cacheTtl * 3600, $githubToken);
        $collector = new PluginUpdateCollector($checker);

        // Collect update data
        $updateData = $collector->collectUpdates();
        if (empty($updateData)) {
            return;
        }

        // Inject assets via output buffer (with crash protection)
        $injector = new AssetInjector(__DIR__);
        ob_start(function (string $buffer) use ($injector, $updateData): string {
            try {
                return $injector->inject($buffer, $updateData);
            } catch (\Throwable $e) {
                error_log('[UpdateCheck] Asset injection failed: ' . $e->getMessage());
                return $buffer;
            }
        });
    }

    private function isPluginsPage(): bool
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        return $script === 'plugins.php';
    }

    private function isAjaxRequest(): bool
    {
        $xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower($xRequestedWith) === 'xmlhttprequest';
    }

    /**
     * Get config from active plugin instance (not the base config with defaults only).
     */
    private function getInstanceConfig(): ?\PluginConfig
    {
        try {
            foreach ($this->getActiveInstances() as $instance) {
                return $instance->getConfig();
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return $this->getConfig();
    }
}
