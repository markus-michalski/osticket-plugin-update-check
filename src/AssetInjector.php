<?php

declare(strict_types=1);

namespace Mmd\UpdateCheck;

/**
 * Injects update badge CSS/JS into the HTML output buffer.
 *
 * Uses the same output buffer pattern as osticket-priority-icons:
 * - CSS injected before </head>
 * - JSON data + JS injected before </body>
 * - All assets inline (osTicket blocks external plugin files via .htaccess)
 */
class AssetInjector
{
    private string $pluginDir;

    public function __construct(string $pluginDir)
    {
        $this->pluginDir = rtrim($pluginDir, '/');
    }

    /**
     * Inject update badge assets into the HTML buffer.
     *
     * @param array<int, array{current: string, latest: string, url: string}> $updateData
     */
    public function inject(string $buffer, array $updateData): string
    {
        // Prevent double injection
        if (strpos($buffer, 'data-plugin="update-check"') !== false) {
            return $buffer;
        }

        $css = $this->buildCss();
        $js = $this->buildJs($updateData);

        // Inject CSS before </head>
        if (stripos($buffer, '</head>') !== false) {
            $buffer = str_ireplace('</head>', $css . '</head>', $buffer);
        }

        // Inject JS before </body>
        if (stripos($buffer, '</body>') !== false) {
            $buffer = str_ireplace('</body>', $js . '</body>', $buffer);
        } else {
            // Fallback: append at end
            $buffer .= $js;
        }

        return $buffer;
    }

    private function buildCss(): string
    {
        $cssFile = $this->pluginDir . '/assets/update-badges.css';
        if (!file_exists($cssFile)) {
            return '';
        }

        $css = file_get_contents($cssFile);
        return '<style data-plugin="update-check">' . $css . '</style>' . "\n";
    }

    private function buildJs(array $updateData): string
    {
        // JSON config with XSS protection
        $json = json_encode(
            $updateData,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_THROW_ON_ERROR
        );

        $html = '<script data-plugin="update-check">'
            . 'window.__pluginUpdates=' . $json . ';'
            . '</script>' . "\n";

        // Inline JS
        $jsFile = $this->pluginDir . '/assets/update-badges.js';
        if (file_exists($jsFile)) {
            $js = file_get_contents($jsFile);
            $html .= '<script data-plugin="update-check">' . $js . '</script>' . "\n";
        }

        return $html;
    }
}
