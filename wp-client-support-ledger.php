<?php
/**
 * Plugin Name:       WP Client Support Ledger
 * Description:       Manages client support hours, tasks, and calculates billable time.
 * Version:           5.0.0
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


/**
 * Create custom database table for invoices on plugin activation.
 */
function wcsl_create_invoices_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wcsl_invoices';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        client_id bigint(20) UNSIGNED NOT NULL,
        month int(2) NOT NULL,
        year int(4) NOT NULL,
        invoice_number varchar(255) DEFAULT '',
        status varchar(50) NOT NULL DEFAULT 'pending',
        amount decimal(10, 2) NOT NULL DEFAULT 0.00,
        generated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        paid_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY client_month_year (client_id, month, year),
        KEY status (status)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Sets up default task categories on plugin activation.
 */
function wcsl_setup_default_task_categories() {
    // Define our default categories and their primary type
    $default_terms = array(
        'Update'       => 'support',
        'Issue Fixing' => 'fixing',
    );

    $default_term_ids = get_option( 'wcsl_default_term_ids', array() );
    if ( !is_array($default_term_ids) ) {
        $default_term_ids = array();
    }

    foreach ( $default_terms as $term_name => $primary_type ) {
        // Check if the term already exists
        $term_exists = term_exists( $term_name, 'task_category' );
        if ( 0 === $term_exists || null === $term_exists ) {
            // Create the term
            $term_data = wp_insert_term( $term_name, 'task_category' );

            if ( ! is_wp_error( $term_data ) ) {
                $term_id = $term_data['term_id'];
                // Add our custom meta to classify it as 'support' or 'fixing'
                add_term_meta( $term_id, 'wcsl_billable_type', $primary_type, true );
                // Store its ID for our default setting
                $default_term_ids[$primary_type] = $term_id;
            }
        } else {
            // If it already exists, find its ID and store it
            $existing_term_id = is_array($term_exists) ? $term_exists['term_id'] : $term_exists;
            $default_term_ids[$primary_type] = $existing_term_id;
        }
    }

    // Save the IDs of our default terms to the options table for easy access later
    update_option( 'wcsl_default_term_ids', $default_term_ids );
}


/**
 * Adds custom user roles for the plugin.
 * Runs on activation.
 */
function wcsl_add_custom_roles() {
    // Client Role: Can only read public content. Backend access will be blocked separately.
    add_role(
        'wcsl_client',
        __( 'Ledger Client', 'wp-client-support-ledger' ),
        array(
            'read' => true,
        )
    );

    // Employee Role: Can read, edit posts (we'll filter to their own later), and upload files.
    add_role(
        'wcsl_employee',
        __( 'Ledger Employee', 'wp-client-support-ledger' ),
        array(
            'read'                   => true,
            'edit_posts'             => true,
            'edit_published_posts'   => true,
            'upload_files'           => true,
            'delete_posts'           => true,
        )
    );
}


/**
 * Main activation function to run all setup tasks.
 */
function wcsl_activate_plugin() {
    // Ensure post types and taxonomies are registered before creating terms
    if (function_exists('wcsl_register_client_task_cpt')) {
        wcsl_register_client_task_cpt();
    }
    if (function_exists('wcsl_register_task_category_taxonomy')) {
        wcsl_register_task_category_taxonomy();
    }
    
    // Create our database tables
    wcsl_create_notifications_table();
    wcsl_create_invoices_table(); 
    
    // NEW: Add the custom user roles
    wcsl_add_custom_roles();
    
    // Set up default data
    wcsl_setup_default_task_categories();

    // Flush rewrite rules to recognize the new taxonomy
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wcsl_activate_plugin' );


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
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/portal-client-templates.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/portal-client-templates.php';
}

if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/portal-employee-templates.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/portal-employee-templates.php';
}

if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
}




//settings & help page
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/setting.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/setting.php';
}




/**
 * Forces WordPress to use the standard page template for our portal views.
 * This prevents the theme from incorrectly loading an archive or 404 template.
 *
 * @param string $template The path of the template to include.
 * @return string The modified template path.
 */
function wcsl_force_portal_page_template( $template ) {
    // Get the page ID that is designated as our portal page in the settings.
    $portal_settings = get_option('wcsl_portal_settings');
    $portal_page_id = isset($portal_settings['portal_page_id']) ? (int) $portal_settings['portal_page_id'] : 0;

    // If no portal page is set in the plugin settings, do nothing.
    if ( empty($portal_page_id) ) {
        return $template;
    }

    // This is the crucial check. It verifies two things:
    // 1. Is the page WordPress is trying to load our designated Portal Page?
    // 2. Does the URL contain our custom 'wcsl_view' parameter?
    if ( is_page( $portal_page_id ) && isset( $_GET['wcsl_view'] ) ) {
        
        // If both are true, we find the theme's generic page.php template.
        $page_template = get_page_template();
        
        // If the theme has a page.php, we force WordPress to use it.
        if ( ! empty( $page_template ) ) {
            return $page_template;
        }
    }

    // In all other cases, we don't interfere and return the original template.
    return $template;
}
add_filter( 'template_include', 'wcsl_force_portal_page_template', 99 );