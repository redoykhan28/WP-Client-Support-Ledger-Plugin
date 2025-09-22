<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register a custom post type called "client".
 *
 * @see get_post_type_labels() for label keys.
 */
function wcsl_register_client_cpt() {
    $labels = array(
        'name'                  => _x( 'Clients', 'Post type general name', 'wp-client-support-ledger' ),
        'singular_name'         => _x( 'Client', 'Post type singular name', 'wp-client-support-ledger' ),
        'menu_name'             => _x( 'Clients', 'Admin Menu text', 'wp-client-support-ledger' ),
        'name_admin_bar'        => _x( 'Client', 'Add New on Toolbar', 'wp-client-support-ledger' ),
        'add_new'               => __( 'Add New', 'wp-client-support-ledger' ),
        'add_new_item'          => __( 'Add New Client', 'wp-client-support-ledger' ),
        'new_item'              => __( 'New Client', 'wp-client-support-ledger' ),
        'edit_item'             => __( 'Edit Client', 'wp-client-support-ledger' ),
        'view_item'             => __( 'View Client', 'wp-client-support-ledger' ),
        'all_items'             => __( 'All Clients', 'wp-client-support-ledger' ),
        'search_items'          => __( 'Search Clients', 'wp-client-support-ledger' ),
        'parent_item_colon'     => __( 'Parent Clients:', 'wp-client-support-ledger' ),
        'not_found'             => __( 'No clients found.', 'wp-client-support-ledger' ),
        'not_found_in_trash'    => __( 'No clients found in Trash.', 'wp-client-support-ledger' ),
        'featured_image'        => _x( 'Client Logo', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'wp-client-support-ledger' ),
        'set_featured_image'    => _x( 'Set client logo', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'wp-client-support-ledger' ),
        'remove_featured_image' => _x( 'Remove client logo', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'wp-client-support-ledger' ),
        'use_featured_image'    => _x( 'Use as client logo', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'wp-client-support-ledger' ),
        'archives'              => _x( 'Client archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'wp-client-support-ledger' ),
        'insert_into_item'      => _x( 'Insert into client', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'wp-client-support-ledger' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this client', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'wp-client-support-ledger' ),
        'filter_items_list'     => _x( 'Filter clients list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'wp-client-support-ledger' ),
        'items_list_navigation' => _x( 'Clients list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'wp-client-support-ledger' ),
        'items_list'            => _x( 'Clients list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'wp-client-support-ledger' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true, // Set to true if you want clients to have individual pages (e.g., example.com/client/hotel-perla)
                                     // For now, we can set it to true. If we only manage them in admin, can be false.
        'publicly_queryable' => true,
        'show_ui'            => true, // Show in admin UI
        'show_in_menu'       => false, // We will create our own top-level menu later
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'client' ), // URL slug
        'capability_type'    => 'post',
        'has_archive'        => true, // Enable client archives (e.g., example.com/clients/)
        'hierarchical'       => false, // Clients are not hierarchical like pages
        'menu_position'      => null,
        'supports'           => array( 'title', 'thumbnail' ), // 'title' = Client Name, 'editor' for notes, 'thumbnail' for logo
        'show_in_rest'       => true, // Enable for Gutenberg editor and REST API
    );

    register_post_type( 'client', $args );
}
add_action( 'init', 'wcsl_register_client_cpt' );



/**
 * Register a custom post type called "employee".
 */
function wcsl_register_employee_cpt() {
    $labels = array(
        'name'                  => _x( 'Employees', 'Post type general name', 'wp-client-support-ledger' ),
        'singular_name'         => _x( 'Employee', 'Post type singular name', 'wp-client-support-ledger' ),
        'menu_name'             => _x( 'Employees', 'Admin Menu text', 'wp-client-support-ledger' ),
        'name_admin_bar'        => _x( 'Employee', 'Add New on Toolbar', 'wp-client-support-ledger' ),
        'add_new'               => __( 'Add New', 'wp-client-support-ledger' ),
        'add_new_item'          => __( 'Add New Employee', 'wp-client-support-ledger' ),
        'new_item'              => __( 'New Employee', 'wp-client-support-ledger' ),
        'edit_item'             => __( 'Edit Employee', 'wp-client-support-ledger' ),
        'view_item'             => __( 'View Employee', 'wp-client-support-ledger' ),
        'all_items'             => __( 'All Employees', 'wp-client-support-ledger' ),
        'search_items'          => __( 'Search Employees', 'wp-client-support-ledger' ),
        'not_found'             => __( 'No employees found.', 'wp-client-support-ledger' ),
        'not_found_in_trash'    => __( 'No employees found in Trash.', 'wp-client-support-ledger' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // Typically, employees don't have public-facing individual pages
        'publicly_queryable' => false,
        'show_ui'            => true,  // Show in admin UI
        'show_in_menu'       => false, // We will add it to our custom menu
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'employee' ),
        'capability_type'    => 'post', // Or a custom capability if needed
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' ), // 'title' will be Employee Name
        'show_in_rest'       => true, // Good for future API use
    );

    register_post_type( 'employee', $args );
}
add_action( 'init', 'wcsl_register_employee_cpt' );




/**
 * Register a custom post type called "client_task".
 */
function wcsl_register_client_task_cpt() {
    $labels = array(
        'name'                  => _x( 'Client Tasks', 'Post type general name', 'wp-client-support-ledger' ),
        'singular_name'         => _x( 'Client Task', 'Post type singular name', 'wp-client-support-ledger' ),
        'menu_name'             => _x( 'Client Tasks', 'Admin Menu text', 'wp-client-support-ledger' ),
        'name_admin_bar'        => _x( 'Client Task', 'Add New on Toolbar', 'wp-client-support-ledger' ),
        'add_new'               => __( 'Add New Task', 'wp-client-support-ledger' ),
        'add_new_item'          => __( 'Add New Client Task', 'wp-client-support-ledger' ),
        'new_item'              => __( 'New Client Task', 'wp-client-support-ledger' ),
        'edit_item'             => __( 'Edit Client Task', 'wp-client-support-ledger' ),
        'view_item'             => __( 'View Client Task', 'wp-client-support-ledger' ),
        'all_items'             => __( 'All Client Tasks', 'wp-client-support-ledger' ),
        'search_items'          => __( 'Search Client Tasks', 'wp-client-support-ledger' ),
        'parent_item_colon'     => __( 'Parent Client Tasks:', 'wp-client-support-ledger' ),
        'not_found'             => __( 'No client tasks found.', 'wp-client-support-ledger' ),
        'not_found_in_trash'    => __( 'No client tasks found in Trash.', 'wp-client-support-ledger' ),
        // You can add more specific labels if needed, similar to the 'client' CPT
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // Tasks are usually not public individual pages
        'publicly_queryable' => false,
        'show_ui'            => true,  // Show in admin UI
        'show_in_menu'       => false, // Will be added under our custom top-level menu
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'client-task' ),
        'capability_type'    => 'post',
        'has_archive'        => false, // No public archive page for tasks
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' ), // 'title' = Task Title, 'editor' for details/notes
                                                            // We'll add other fields like hours, client link, date via meta boxes
        'show_in_rest'       => true, // Good for future flexibility
    );

    register_post_type( 'client_task', $args );
}
add_action( 'init', 'wcsl_register_client_task_cpt' );


/**
 * Register a custom taxonomy called "Task Type" (internally 'task_category').
 */
function wcsl_register_task_category_taxonomy() {
    $labels = array(
        'name'              => _x( 'Task Types', 'taxonomy general name', 'wp-client-support-ledger' ),
        'singular_name'     => _x( 'Task Type', 'taxonomy singular name', 'wp-client-support-ledger' ),
        'search_items'      => __( 'Search Task Types', 'wp-client-support-ledger' ),
        'all_items'         => __( 'All Task Types', 'wp-client-support-ledger' ),
        'parent_item'       => __( 'Parent Task Type', 'wp-client-support-ledger' ),
        'parent_item_colon' => __( 'Parent Task Type:', 'wp-client-support-ledger' ),
        'edit_item'         => __( 'Edit Task Type', 'wp-client-support-ledger' ),
        'update_item'       => __( 'Update Task Type', 'wp-client-support-ledger' ),
        'add_new_item'      => __( 'Add New Task Type', 'wp-client-support-ledger' ),
        'new_item_name'     => __( 'New Task Type Name', 'wp-client-support-ledger' ),
        'menu_name'         => __( 'Task Types', 'wp-client-support-ledger' ),
    );

    $args = array(
        'hierarchical'      => true, // Makes it behave like categories (parent/child) rather than tags.
        'labels'            => $labels,
        'show_ui'           => true, // Show the UI in the admin.
        'show_admin_column' => true, // Show a column for it on the main "All Tasks" list.
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'task-type' ),
        'show_in_rest'      => true, // Important for REST API and Gutenberg.
    );

    register_taxonomy( 'task_category', array( 'client_task' ), $args );
}
add_action( 'init', 'wcsl_register_task_category_taxonomy' );


/**
 * --------------------------------------------------------------------------
 * Add custom field ('billable_type') to our 'task_category' taxonomy.
 * --------------------------------------------------------------------------
 */

// 1. Function to add the field to the "Add New Task Type" form.
function wcsl_add_billable_type_field_to_task_category( $taxonomy ) {
    ?>
    <div class="form-field term-billable-type-wrap">
        <label for="term-billable-type"><?php esc_html_e( 'Primary Type', 'wp-client-support-ledger' ); ?></label>
        <select name="wcsl_billable_type" id="term-billable-type">
            <option value="support" selected><?php esc_html_e( 'Support (Billable)', 'wp-client-support-ledger' ); ?></option>
            <option value="fixing"><?php esc_html_e( 'Fixing (Non-Billable)', 'wp-client-support-ledger' ); ?></option>
        </select>
        <p><?php esc_html_e( 'Classify this category as either billable (Support) or non-billable (Fixing). This determines which dropdown it appears in on the task creation screen.', 'wp-client-support-ledger' ); ?></p>
    </div>
    <?php
}
add_action( 'task_category_add_form_fields', 'wcsl_add_billable_type_field_to_task_category' );


// 2. Function to add the field to the "Edit Task Type" form.
function wcsl_edit_billable_type_field_for_task_category( $term, $taxonomy ) {
    $billable_type = get_term_meta( $term->term_id, 'wcsl_billable_type', true );
    if ( empty( $billable_type ) ) {
        $billable_type = 'support'; // Default to support
    }
    ?>
    <tr class="form-field term-billable-type-wrap">
        <th scope="row">
            <label for="term-billable-type"><?php esc_html_e( 'Primary Type', 'wp-client-support-ledger' ); ?></label>
        </th>
        <td>
            <select name="wcsl_billable_type" id="term-billable-type">
                <option value="support" <?php selected( $billable_type, 'support' ); ?>><?php esc_html_e( 'Support (Billable)', 'wp-client-support-ledger' ); ?></option>
                <option value="fixing" <?php selected( $billable_type, 'fixing' ); ?>><?php esc_html_e( 'Fixing (Non-Billable)', 'wp-client-support-ledger' ); ?></option>
            </select>
            <p class="description"><?php esc_html_e( 'Classify this category as either billable (Support) or non-billable (Fixing).', 'wp-client-support-ledger' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'task_category_edit_form_fields', 'wcsl_edit_billable_type_field_for_task_category', 10, 2 );


// 3. Function to save the custom field value.
function wcsl_save_task_category_billable_type( $term_id ) {
    if ( ! isset( $_POST['wcsl_billable_type'] ) ) {
        return;
    }
    $billable_type = sanitize_key( $_POST['wcsl_billable_type'] );
    if ( in_array( $billable_type, array( 'support', 'fixing' ) ) ) {
        update_term_meta( $term_id, 'wcsl_billable_type', $billable_type );
    }
}
add_action( 'created_task_category', 'wcsl_save_task_category_billable_type' );
add_action( 'edited_task_category', 'wcsl_save_task_category_billable_type' );


/**
 * Removes the default "Task Types" meta box from the task edit screen.
 */
function wcsl_remove_task_category_meta_box() {
    remove_meta_box( 'task_categorydiv', 'client_task', 'side' );
}
add_action( 'admin_menu', 'wcsl_remove_task_category_meta_box' );