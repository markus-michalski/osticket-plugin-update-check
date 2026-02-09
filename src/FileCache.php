<?php

declare(strict_types=1);

namespace Mmd\UpdateCheck;

/**
 * Simple file-based cache for GitHub API responses.
 *
 * Stores cached data as JSON files with TTL expiry.
 * Gracefully handles unwritable directories.
 */
class FileCache
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
    }

    /**
     * Get a cached value by key.
     *
     * @return mixed|null Cached data or null if expired/missing
     */
    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $entry = json_decode($content, true);
        if (!is_array($entry) || !isset($entry['expires_at'], $entry['data'])) {
            @unlink($file);
            return null;
        }

        // Check TTL
        if (time() > $entry['expires_at']) {
            @unlink($file);
            return null;
        }

        return $entry['data'];
    }

    /**
     * Store a value in cache with TTL.
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        if (!$this->ensureDirectory()) {
            return;
        }

        $entry = [
            'expires_at' => time() + $ttl,
            'data' => $value,
        ];

        $file = $this->getFilePath($key);
        @file_put_contents($file, json_encode($entry, JSON_THROW_ON_ERROR), LOCK_EX);
    }

    /**
     * Remove all cache entries.
     */
    public function clear(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }

        $files = glob($this->cacheDir . '/*.json');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            @unlink($file);
        }
    }

    private function getFilePath(string $key): string
    {
        // Sanitize key to safe filename
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . '/' . $safe . '.json';
    }

    private function ensureDirectory(): bool
    {
        if (is_dir($this->cacheDir)) {
            return is_writable($this->cacheDir);
        }

        return @mkdir($this->cacheDir, 0755, true);
    }
}
