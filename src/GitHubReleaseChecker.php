<?php

declare(strict_types=1);

namespace Mmd\UpdateCheck;

/**
 * Checks GitHub Releases API for the latest version of a repository.
 *
 * Uses cURL with timeout and caches results via FileCache.
 * Handles rate limits and errors gracefully.
 */
class GitHubReleaseChecker
{
    private const CURL_TIMEOUT = 5;
    private const ERROR_CACHE_TTL = 3600; // 1 hour for errors
    private const USER_AGENT = 'osTicket-Plugin-UpdateChecker/0.1';

    private FileCache $cache;
    private int $successTtl;
    private string $githubToken;

    public function __construct(FileCache $cache, int $successTtl = 21600, string $githubToken = '')
    {
        $this->cache = $cache;
        $this->successTtl = $successTtl;
        $this->githubToken = $githubToken;
    }

    /**
     * Get the latest release info for a GitHub URL.
     *
     * @return array{tag_name: string, html_url: string}|null
     */
    public function getLatestRelease(string $githubUrl): ?array
    {
        $parsed = $this->parseGitHubUrl($githubUrl);
        if ($parsed === null) {
            return null;
        }

        [$owner, $repo] = $parsed;
        $cacheKey = "github_release_{$owner}_{$repo}";

        // Check cache
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached === 'none' ? null : $cached;
        }

        // Fetch from GitHub API
        $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";
        $response = $this->fetchFromGitHub($apiUrl);

        if ($response === null) {
            $this->cache->set($cacheKey, 'none', self::ERROR_CACHE_TTL);
            return null;
        }

        $result = [
            'tag_name' => $response['tag_name'] ?? '',
            'html_url' => $response['html_url'] ?? '',
        ];

        if (empty($result['tag_name'])) {
            $this->cache->set($cacheKey, 'none', self::ERROR_CACHE_TTL);
            return null;
        }

        $this->cache->set($cacheKey, $result, $this->successTtl);
        return $result;
    }

    /**
     * Check if a URL is a GitHub repository URL.
     */
    public function isGitHubUrl(string $url): bool
    {
        return $this->parseGitHubUrl($url) !== null;
    }

    /**
     * Parse a GitHub URL into [owner, repo].
     *
     * @return array{0: string, 1: string}|null
     */
    public function parseGitHubUrl(string $url): ?array
    {
        // Match github.com/owner/repo with optional .git suffix
        if (!preg_match('#(?:https?://)?github\.com/([^/]+)/([^/.]+?)(?:\.git)?/?$#i', $url, $matches)) {
            return null;
        }

        $owner = $matches[1];
        $repo = $matches[2];

        // Filter out invalid patterns
        if (empty($owner) || empty($repo) || $owner === '.' || $repo === '.') {
            return null;
        }

        return [$owner, $repo];
    }

    /**
     * Check if a newer version is available.
     */
    public function hasUpdate(string $currentVersion, string $latestVersion): bool
    {
        $current = ltrim($currentVersion, 'v');
        $latest = ltrim($latestVersion, 'v');

        if ($current === '' || $latest === '') {
            return false;
        }

        return version_compare($latest, $current, '>');
    }

    /**
     * @return array<string, mixed>|null Decoded JSON response or null on failure
     */
    private function fetchFromGitHub(string $apiUrl): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($apiUrl);
        if ($ch === false) {
            return null;
        }

        $headers = [
            'Accept: application/vnd.github.v3+json',
            'User-Agent: ' . self::USER_AGENT,
        ];

        if ($this->githubToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->githubToken;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => self::CURL_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::CURL_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response)) {
            $path = parse_url($apiUrl, PHP_URL_PATH) ?: $apiUrl;
            error_log(sprintf('[UpdateCheck] GitHub API HTTP %d for %s', $httpCode, $path));
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}
