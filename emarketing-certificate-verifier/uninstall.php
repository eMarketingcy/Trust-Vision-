<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'ecv_certificates';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
