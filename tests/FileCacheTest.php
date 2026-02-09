<?php

declare(strict_types=1);

namespace Mmd\UpdateCheck\Tests;

use Mmd\UpdateCheck\FileCache;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    private string $cacheDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/osticket-update-check-test-' . uniqid();
        $this->cache = new FileCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        // Clean up
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($this->cacheDir);
        }
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function testSetAndGetRoundtrip(): void
    {
        $this->cache->set('test_key', ['version' => '1.2.0'], 3600);
        $result = $this->cache->get('test_key');

        $this->assertIsArray($result);
        $this->assertSame('1.2.0', $result['version']);
    }

    public function testSetAndGetWithStringValue(): void
    {
        $this->cache->set('test_string', 'none', 3600);
        $this->assertSame('none', $this->cache->get('test_string'));
    }

    public function testExpiredEntryReturnsNull(): void
    {
        $this->cache->set('expired_key', 'data', 0);

        // TTL of 0 means it expires immediately
        sleep(1);
        $this->assertNull($this->cache->get('expired_key'));
    }

    public function testClearRemovesAllEntries(): void
    {
        $this->cache->set('key1', 'value1', 3600);
        $this->cache->set('key2', 'value2', 3600);

        $this->cache->clear();

        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    public function testCreatesDirectoryAutomatically(): void
    {
        $this->assertDirectoryDoesNotExist($this->cacheDir);

        $this->cache->set('auto_create', 'data', 3600);

        $this->assertDirectoryExists($this->cacheDir);
        $this->assertSame('data', $this->cache->get('auto_create'));
    }

    public function testSanitizesKeyForFilename(): void
    {
        $this->cache->set('owner/repo:special', 'data', 3600);
        $this->assertSame('data', $this->cache->get('owner/repo:special'));
    }

    public function testHandlesCorruptedCacheFile(): void
    {
        // Manually write corrupt data
        @mkdir($this->cacheDir, 0755, true);
        file_put_contents(
            $this->cacheDir . '/corrupt_key.json',
            'not valid json{'
        );

        $this->assertNull($this->cache->get('corrupt_key'));
    }
}
