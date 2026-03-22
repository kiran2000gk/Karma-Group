<?php
/**
 * Plugin Name: WP Offers Manager
 * Description: Dashboard to manage offers + REST API
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-offers-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-offers-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-offers-api.php';

register_activation_hook(__FILE__, function() {
    Offers_DB::create_table();
    if (!get_option('wp_offers_api_key')) {
        update_option('wp_offers_api_key', bin2hex(random_bytes(24)));
    }
});