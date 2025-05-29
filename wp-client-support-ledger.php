<?php
/**
 * Plugin Name:       WP Client Support Ledger
 * Description:       Manages client support hours, tasks, and calculates billable time.
 * Version:           2.0.4
 * Author:            Sezan Ahmed
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-client-support-ledger
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define maximum number of notifications to keep
define( 'WCSL_MAX_NOTIFICATIONS', 100 ); 

// *** IMPORTANT: Include helpers.php EARLY, as it's needed by the activation hook ***
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/helpers.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/helpers.php';
} else {
    // Optional: Add some error handling if a critical file is missing
    // For example, trigger an admin notice or prevent further loading.
    // For now, we'll assume it's there.
}

//report page
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/reports-data.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/reports-data.php';
}


/**
 * Create custom database table on plugin activation.
 */
function wcsl_create_notifications_table() {
    global $wpdb;
    // Now wcsl_get_notifications_table_name() will be available because helpers.php is included above
    $table_name = wcsl_get_notifications_table_name();

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED DEFAULT 0,
        type varchar(50) NOT NULL DEFAULT '',
        message text NOT NULL,
        related_object_id bigint(20) UNSIGNED DEFAULT 0,
        related_object_type varchar(50) DEFAULT '',
        is_read tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY is_read (is_read),
        KEY type (type)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'wcsl_create_notifications_table' );




// Core structures first
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/post-types.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/post-types.php';
}
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/meta-boxes.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/meta-boxes.php';
}

// Admin specific functionality
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/admin-menu.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin-menu.php';
}

// Notification logic (depends on helpers)
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/notification-triggers.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/notification-triggers.php';
}
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/ajax-handlers.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/ajax-handlers.php';
}

// Frontend functionality
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
}

//settings & help page
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/setting.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/setting.php';
}
