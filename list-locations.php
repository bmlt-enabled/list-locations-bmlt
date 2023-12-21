<?php
/*
Plugin Name: List Locations BMLT
Plugin URI: https://wordpress.org/plugins/list-locations-bmlt/
Author: bmlt-enabled
Description: This plugin returns all unique towns or counties for a given service body on your site. Simply add [list_locations] shortcode to your page and set shortcode attributes accordingly. Required attributes are root_server and services.
Version: 2.3.2
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/

// Disallow direct access to the plugin file
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Sorry, but you cannot access this page directly.');
}

spl_autoload_register(function (string $class) {
    if (strpos($class, 'ListLocations\\') === 0) {
        $class = str_replace('ListLocations\\', '', $class);
        require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    }
});

use ListLocations\Settings;
use ListLocations\Shortcode;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ListLocations
{
    // phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace

    private static $instance = null;

    public function __construct()
    {
        add_action('init', [$this, 'pluginSetup']);
    }

    public function pluginSetup()
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'optionsMenu']);
            add_action("admin_enqueue_scripts", [$this, "enqueueBackendFiles"], 500);
        } else {
            add_shortcode('list_locations', [$this, 'listShortcode']);
        }
    }

    public function optionsMenu()
    {
        $dashboard = new Settings();
        $dashboard->createMenu(plugin_basename(__FILE__));
    }

    public function listShortcode($atts)
    {
        $shortcode = new Shortcode();
        return $shortcode->render($atts);
    }

    public function enqueueBackendFiles($hook)
    {
        if ($hook !== 'settings_page_list-locations-plugin') {
            return;
        }
        $base_url = plugin_dir_url(__FILE__);
        wp_enqueue_style('list-locations-admin-ui-css', $base_url . 'css/start/jquery-ui.css', [], '1.11.4');
        wp_enqueue_style('chosen', $base_url . 'css/chosen.min.css', [], '1.2', 'all');
        wp_enqueue_style('list-locations-css', $base_url . 'css/list_locations.css');

        wp_enqueue_script('chosen', $base_url . 'js/chosen.jquery.min.js', ['jquery'], '1.2', true);
        wp_enqueue_script('list-locations-admin', $base_url . 'js/list_locations_admin.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . 'js/list_locations_admin.js'), false);

        wp_enqueue_script('common');
        wp_enqueue_script('jquery-ui-accordion');
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

ListLocations::getInstance();
