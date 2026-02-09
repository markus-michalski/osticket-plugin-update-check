<?php

declare(strict_types=1);

namespace Mmd\UpdateCheck\Tests;

use Mmd\UpdateCheck\AssetInjector;
use PHPUnit\Framework\TestCase;

class AssetInjectorTest extends TestCase
{
    private AssetInjector $injector;
    private string $pluginDir;

    protected function setUp(): void
    {
        $this->pluginDir = dirname(__DIR__);
        $this->injector = new AssetInjector($this->pluginDir);
    }

    public function testInjectAddsCssBeforeHeadClose(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $updates = [1 => ['current' => '1.0.0', 'latest' => '1.2.0', 'url' => 'https://example.com']];

        $result = $this->injector->inject($html, $updates);

        $this->assertStringContainsString('data-plugin="update-check"', $result);
        $this->assertStringContainsString('<style', $result);
        // CSS should be before </head>
        $stylePos = strpos($result, '<style');
        $headPos = strpos($result, '</head>');
        $this->assertLessThan($headPos, $stylePos);
    }

    public function testInjectAddsJsonDataBeforeBodyClose(): void
    {
        $html = '<html><head></head><body><p>Content</p></body></html>';
        $updates = [42 => ['current' => '1.0.0', 'latest' => '2.0.0', 'url' => 'https://github.com/test/release']];

        $result = $this->injector->inject($html, $updates);

        $this->assertStringContainsString('window.__pluginUpdates=', $result);
        $this->assertStringContainsString('"42"', $result); // JSON key
        $this->assertStringContainsString('2.0.0', $result);
    }

    public function testInjectPreventsDoubleInjection(): void
    {
        $html = '<html><head></head><body><div data-plugin="update-check"></div></body></html>';
        $updates = [1 => ['current' => '1.0.0', 'latest' => '1.2.0', 'url' => 'https://example.com']];

        $result = $this->injector->inject($html, $updates);

        // Should not inject again
        $this->assertSame($html, $result);
    }

    public function testInjectHandlesEmptyUpdates(): void
    {
        $html = '<html><head></head><body></body></html>';

        $result = $this->injector->inject($html, []);

        // Should still inject (empty JSON object is valid)
        $this->assertStringContainsString('window.__pluginUpdates=', $result);
    }

    public function testInjectUsesXssProtectedJson(): void
    {
        $html = '<html><head></head><body></body></html>';
        $updates = [1 => [
            'current' => '1.0.0',
            'latest' => '1.1.0',
            'url' => 'https://github.com/test/<script>alert(1)</script>',
        ]];

        $result = $this->injector->inject($html, $updates);

        // JSON_HEX_TAG should encode < and > as unicode escapes
        $this->assertStringNotContainsString('<script>alert', $result);
    }
}
