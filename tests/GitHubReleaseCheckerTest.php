<?php

declare(strict_types=1);

namespace Mmd\UpdateCheck\Tests;

use Mmd\UpdateCheck\FileCache;
use Mmd\UpdateCheck\GitHubReleaseChecker;
use PHPUnit\Framework\TestCase;

class GitHubReleaseCheckerTest extends TestCase
{
    private GitHubReleaseChecker $checker;

    protected function setUp(): void
    {
        $cache = new FileCache(sys_get_temp_dir() . '/osticket-update-check-test-' . uniqid());
        $this->checker = new GitHubReleaseChecker($cache);
    }

    // =========================================================================
    // parseGitHubUrl()
    // =========================================================================

    public function testParseGitHubUrlWithHttps(): void
    {
        $result = $this->checker->parseGitHubUrl('https://github.com/markus-michalski/osticket-api-endpoints');
        $this->assertSame(['markus-michalski', 'osticket-api-endpoints'], $result);
    }

    public function testParseGitHubUrlWithHttp(): void
    {
        $result = $this->checker->parseGitHubUrl('http://github.com/user/repo');
        $this->assertSame(['user', 'repo'], $result);
    }

    public function testParseGitHubUrlWithGitSuffix(): void
    {
        $result = $this->checker->parseGitHubUrl('https://github.com/user/repo.git');
        $this->assertSame(['user', 'repo'], $result);
    }

    public function testParseGitHubUrlWithTrailingSlash(): void
    {
        $result = $this->checker->parseGitHubUrl('https://github.com/user/repo/');
        $this->assertSame(['user', 'repo'], $result);
    }

    public function testParseGitHubUrlWithoutProtocol(): void
    {
        $result = $this->checker->parseGitHubUrl('github.com/user/repo');
        $this->assertSame(['user', 'repo'], $result);
    }

    public function testParseGitHubUrlReturnsNullForNonGithub(): void
    {
        $this->assertNull($this->checker->parseGitHubUrl('https://gitlab.com/user/repo'));
    }

    public function testParseGitHubUrlReturnsNullForEmpty(): void
    {
        $this->assertNull($this->checker->parseGitHubUrl(''));
    }

    public function testParseGitHubUrlReturnsNullForMalformed(): void
    {
        $this->assertNull($this->checker->parseGitHubUrl('https://github.com/'));
        $this->assertNull($this->checker->parseGitHubUrl('https://github.com/only-owner'));
    }

    public function testParseGitHubUrlWithSubpath(): void
    {
        // URLs with extra path segments should NOT match
        $this->assertNull($this->checker->parseGitHubUrl('https://github.com/user/repo/tree/main'));
    }

    // =========================================================================
    // isGitHubUrl()
    // =========================================================================

    public function testIsGitHubUrlReturnsTrue(): void
    {
        $this->assertTrue($this->checker->isGitHubUrl('https://github.com/user/repo'));
    }

    public function testIsGitHubUrlReturnsFalse(): void
    {
        $this->assertFalse($this->checker->isGitHubUrl('https://example.com'));
        $this->assertFalse($this->checker->isGitHubUrl(''));
    }

    // =========================================================================
    // hasUpdate()
    // =========================================================================

    public function testHasUpdateReturnsTrueWhenNewer(): void
    {
        $this->assertTrue($this->checker->hasUpdate('1.0.0', '1.2.0'));
    }

    public function testHasUpdateReturnsTrueWithVPrefix(): void
    {
        $this->assertTrue($this->checker->hasUpdate('v1.0.0', 'v1.2.0'));
    }

    public function testHasUpdateReturnsTrueWithMixedPrefix(): void
    {
        $this->assertTrue($this->checker->hasUpdate('1.0.0', 'v1.2.0'));
        $this->assertTrue($this->checker->hasUpdate('v1.0.0', '1.2.0'));
    }

    public function testHasUpdateReturnsFalseWhenSame(): void
    {
        $this->assertFalse($this->checker->hasUpdate('1.0.0', '1.0.0'));
        $this->assertFalse($this->checker->hasUpdate('v1.0.0', '1.0.0'));
    }

    public function testHasUpdateReturnsFalseWhenOlder(): void
    {
        $this->assertFalse($this->checker->hasUpdate('2.0.0', '1.0.0'));
    }

    public function testHasUpdateReturnsFalseForEmptyStrings(): void
    {
        $this->assertFalse($this->checker->hasUpdate('', '1.0.0'));
        $this->assertFalse($this->checker->hasUpdate('1.0.0', ''));
        $this->assertFalse($this->checker->hasUpdate('', ''));
    }

    public function testHasUpdateHandlesPatchVersions(): void
    {
        $this->assertTrue($this->checker->hasUpdate('1.0.0', '1.0.1'));
        $this->assertFalse($this->checker->hasUpdate('1.0.1', '1.0.0'));
    }

    public function testHasUpdateHandlesPreRelease(): void
    {
        // version_compare treats 1.0.0-beta < 1.0.0
        $this->assertTrue($this->checker->hasUpdate('1.0.0-beta', '1.0.0'));
    }
}
