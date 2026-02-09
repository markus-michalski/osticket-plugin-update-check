<?php
/**
 * Plugin Update Checker - Configuration
 *
 * Admin configuration form for cache TTL and optional GitHub token.
 *
 * @package    osTicket\Plugins\UpdateCheck
 * @author     Markus Michalski
 * @version    0.1.0
 * @license    GPL-2.0-or-later
 */

declare(strict_types=1);

if (!class_exists('PluginConfig')) {
    require_once INCLUDE_DIR . 'class.plugin.php';
}

class UpdateCheckConfig extends PluginConfig
{
    public function getOptions(): array
    {
        return [
            'cache_ttl' => new TextboxField([
                'label'   => __('Cache Duration (hours)'),
                'default' => '6',
                'hint'    => __('How long to cache GitHub API responses (default: 6 hours)'),
                'size'    => 5,
                'maxlength' => 4,
            ]),

            'github_token' => new TextboxField([
                'label'   => __('GitHub Token (optional)'),
                'default' => '',
                'hint'    => __('Personal Access Token for higher API rate limits (60/h without, 5000/h with token)'),
                'size'    => 50,
                'maxlength' => 255,
            ]),
        ];
    }
}
