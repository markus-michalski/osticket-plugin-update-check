<?php
/**
 * Plugin Update Checker - Metadata
 *
 * osTicket plugin that checks GitHub for available updates
 * and shows update badges in the plugin list.
 *
 * @package    osTicket\Plugins\UpdateCheck
 * @author     Markus Michalski
 * @version    0.1.0
 * @license    GPL-2.0-or-later
 * @link       https://github.com/markus-michalski/osticket-plugin-update-check
 */

return [
    'id'          => 'net.michalski:update-check',
    'version'     => '0.1.0',
    'name'        => /* trans */ 'Plugin Update Checker',
    'author'      => 'Markus Michalski',
    'description' => /* trans */ 'Checks GitHub for plugin updates and shows badges in the plugin list',
    'url'         => 'https://github.com/markus-michalski/osticket-plugin-update-check',
    'plugin'      => 'class.UpdateCheckPlugin.php:UpdateCheckPlugin',
];
