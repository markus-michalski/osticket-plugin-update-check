<?php

declare(strict_types=1);

namespace Mmd\UpdateCheck;

/**
 * Collects update information for all installed osTicket plugins.
 *
 * Iterates installed plugins, checks for GitHub URLs in their metadata,
 * and queries GitHub for available updates.
 */
class PluginUpdateCollector
{
    private GitHubReleaseChecker $checker;

    public function __construct(GitHubReleaseChecker $checker)
    {
        $this->checker = $checker;
    }

    /**
     * Collect update data for all installed plugins with GitHub URLs.
     *
     * @return array<int, array{current: string, latest: string, url: string}>
     *         Keyed by plugin ID
     */
    public function collectUpdates(): array
    {
        if (!class_exists('PluginManager')) {
            return [];
        }

        $updates = [];

        foreach (\PluginManager::allInstalled() as $path => $plugin) {
            if (!$plugin instanceof \Plugin) {
                continue;
            }

            $updateInfo = $this->checkPlugin($plugin, $path);
            if ($updateInfo !== null) {
                $updates[$plugin->getId()] = $updateInfo;
            }
        }

        return $updates;
    }

    /**
     * Check a single plugin for available updates.
     *
     * @return array{current: string, latest: string, url: string}|null
     */
    private function checkPlugin(\Plugin $plugin, string $path): ?array
    {
        // Get plugin metadata including the URL field
        $info = \PluginManager::getInfoForPath(
            INCLUDE_DIR . $plugin->getInstallPath(),
            $plugin->isPhar()
        );

        if (!is_array($info)) {
            return null;
        }

        $url = $info['url'] ?? '';
        if (empty($url) || !$this->checker->isGitHubUrl($url)) {
            return null;
        }

        $release = $this->checker->getLatestRelease($url);
        if ($release === null) {
            return null;
        }

        $currentVersion = $plugin->getVersion() ?: '';
        $latestTag = $release['tag_name'] ?? '';

        if (!$this->checker->hasUpdate($currentVersion, $latestTag)) {
            return null;
        }

        return [
            'current' => $currentVersion,
            'latest' => ltrim($latestTag, 'v'),
            'url' => $release['html_url'] ?? '',
        ];
    }
}
