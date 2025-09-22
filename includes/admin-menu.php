<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Helper function to generate a dropdown filter for the task list.
 *
 * @param string $query_var The GET parameter name (e.g., 'filter_client').
 * @param array  $options   An associative array of value => label for the dropdown.
 * @param string $label     The default label for the dropdown (e.g., 'All Clients').
 */
function wcsl_display_task_filter_dropdown( $query_var, $options, $label ) {
    $current_value = isset( $_GET[$query_var] ) ? sanitize_text_field( $_GET[$query_var] ) : '';
    ?>
    <select name="<?php echo esc_attr( $query_var ); ?>">
        <option value=""><?php echo esc_html( $label ); ?></option>
        <?php foreach ( $options as $value => $option_label ) : ?>
            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_value, $value ); ?>>
                <?php echo esc_html( $option_label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}



/**
 * Register the admin menu pages for the plugin.
 */
function wcsl_plugin_admin_menu() {
    // Top-level menu page
    add_menu_page(
        __( 'Client Support Ledger', 'wp-client-support-ledger' ),
        __( 'Support Ledger', 'wp-client-support-ledger' ),
        'manage_options',
        'wcsl-main-menu',
        'wcsl_monthly_overview_page_display',
        'dashicons-clipboard',
        25
    );

    // Submenu: Monthly Overview
    add_submenu_page(
        'wcsl-main-menu',
        __( 'Monthly Overview', 'wp-client-support-ledger' ),
        __( 'Monthly Overview', 'wp-client-support-ledger' ),
        'manage_options',
        'wcsl-main-menu',
        'wcsl_monthly_overview_page_display'
    );

    //submenu: Billing Clients
    add_submenu_page(
    'wcsl-main-menu',                                     
    __( 'Billing Clients', 'wp-client-support-ledger' ),    
    __( 'Billing Clients', 'wp-client-support-ledger' ),    
    'manage_options',                                     
    'wcsl-billing-clients',                               
    'wcsl_billing_clients_page_display'                   
    );

   // Submenu: Reports
    add_submenu_page(
        'wcsl-main-menu',                                     
        __( 'Support Ledger Reports', 'wp-client-support-ledger' ), 
        __( 'Reports', 'wp-client-support-ledger' ),            
        'manage_options',                                     
        'wcsl-reports',                                       
        'wcsl_reports_page_display'                           
    );

    // Submenu: All Tasks
    add_submenu_page(
        'wcsl-main-menu',
        __( 'All Tasks', 'wp-client-support-ledger' ),
        __( 'All Tasks', 'wp-client-support-ledger' ),
        'manage_options',
        'edit.php?post_type=client_task'
    );

    // Submenu: Add New Task
    add_submenu_page(
        'wcsl-main-menu',
        __( 'Add New Task', 'wp-client-support-ledger' ),
        __( 'Add New Task', 'wp-client-support-ledger' ),
        'manage_options',
        'post-new.php?post_type=client_task'
    );

    // Submenu: Add Task Type
    add_submenu_page(
        'wcsl-main-menu',
        __( 'Task Types', 'wp-client-support-ledger' ),      
        __( 'Task Types', 'wp-client-support-ledger' ),     
        'manage_options',                                  
        'edit-tags.php?taxonomy=task_category&post_type=client_task' 
    );

    // Submenu: All Employees
    add_submenu_page(
        'wcsl-main-menu',
        __( 'All Employees', 'wp-client-support-ledger' ),
        __( 'Employees', 'wp-client-support-ledger' ),
        'manage_options', // Or a capability for managing employees
        'edit.php?post_type=employee'
    );

    // Submenu: Add New Employee
    add_submenu_page(
        'wcsl-main-menu',
        __( 'Add New Employee', 'wp-client-support-ledger' ),
        __( 'Add New Employee', 'wp-client-support-ledger' ),
        'manage_options',
        'post-new.php?post_type=employee'
    );

    // Submenu: All Clients
    add_submenu_page(
        'wcsl-main-menu',
        __( 'Clients', 'wp-client-support-ledger' ),
        __( 'Clients', 'wp-client-support-ledger' ),
        'manage_options',
        'edit.php?post_type=client'
    );

    // Submenu: Add New Client
    add_submenu_page(
        'wcsl-main-menu',
        __( 'Add New Client', 'wp-client-support-ledger' ),
        __( 'Add New Client', 'wp-client-support-ledger' ),
        'manage_options',
        'post-new.php?post_type=client'
    );

     // --- Notifications Submenu with Count ---
     $unread_count = 0;
     if ( function_exists( 'wcsl_get_unread_notification_count' ) ) {
         $unread_count = wcsl_get_unread_notification_count();
     }
     
     $notification_menu_title = __( 'Notifications', 'wp-client-support-ledger' );
     if ( $unread_count > 0 ) {
         // Add a bubble with the count
         // Using a non-breaking space before the span for better visual separation.
         $notification_menu_title .= ' <span class="awaiting-mod count-' . $unread_count . '"><span class="pending-count">' . number_format_i18n( $unread_count ) . '</span></span>';
     }
 
     add_submenu_page(
         'wcsl-main-menu',
         __( 'Ledger Notifications', 'wp-client-support-ledger' ),
         $notification_menu_title, // Menu title with count
         'manage_options',         // Capability
         'wcsl-notifications',
         'wcsl_notifications_page_display'
     );

     
    // Submenu: Need Help?
    add_submenu_page(
        'wcsl-main-menu',
        __( 'Ledger Settings & Help', 'wp-client-support-ledger' ),
        __( 'Settings & Help', 'wp-client-support-ledger' ),
        'manage_options',
        'wcsl-settings-help',
        'wcsl_settings_help_page_display'
    );
}
add_action( 'admin_menu', 'wcsl_plugin_admin_menu' );


/**
 * Display callback for the Monthly Overview page.
 * This function now acts as a router based on the 'action' GET parameter.
 */
function wcsl_monthly_overview_page_display() {
    ?>
    <div class="wrap wcsl-overview-page">
        <?php // Page title is now handled by the specific view functions for better context ?>
        <?php
        $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'view_index';
        $month  = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : 0;
        $year   = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : 0;
        $nonce  = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : '';

        // *** FIX: Cleaned up router logic ***
        if ( 'view_month_details' === $action && $month > 0 && $month <= 12 && $year > 1970 ) {
            echo '<h1>' . esc_html( get_admin_page_title() ) . ' - ' . esc_html__('Monthly Details', 'wp-client-support-ledger') . '</h1>';
            wcsl_display_single_month_details( $month, $year );
        } elseif ( 'view_print_report' === $action && $month > 0 && $month <= 12 && $year > 1970 ) {
            // This action is now handled by admin-post.php, this block can be removed
            // or kept for direct access with tighter nonce/cap checks if desired,
            // but for simplicity with admin-post.php, let's assume it's handled there.
            // For safety, if it's reached here, it might be an old link or direct access attempt.
             wp_die('This action is handled elsewhere.'); // Or redirect to index.
        } else { // Default: Display the month index
            echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
            wcsl_display_month_index();
        }
        ?>
    </div> <!-- .wrap -->
    <?php
}



/**
 * Display callback for the Billing Clients page.
 * This function acts as a router for this section.
 */

function wcsl_billing_clients_page_display() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-client-support-ledger' ) );
    }
    ?>
    <div class="wrap wcsl-billing-page">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <?php
        // Get all possible parameters from the URL
        $action    = isset( $_GET['wcsl_billing_action'] ) ? sanitize_key( $_GET['wcsl_billing_action'] ) : 'view_billing_index';
        $month     = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : 0;
        $year      = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : 0;
        $client_id = isset( $_GET['client_id'] ) ? intval( $_GET['client_id'] ) : 0; // Get client_id

        // Route to the correct display function based on the action
        if ( 'view_client_tasks' === $action && $client_id > 0 && $month > 0 && $year > 0 ) {
            // This is the action for showing a single client's tasks for a month
            wcsl_display_billable_client_tasks_page( $client_id, $month, $year );
        } elseif ( 'view_billing_month' === $action && $month > 0 && $year > 0 ) {
            // This is the action for showing the list of billable clients for a month
            wcsl_display_single_billing_month_details( $month, $year );
        } else {
            // Default view: Show the index of months that have billable clients
            wcsl_display_billing_month_index();
        }
        ?>
    </div>
    <?php
}


/**
 * Displays an index of months that have at least one client with billable hours.
 */
function wcsl_display_billing_month_index() {
    global $wpdb, $wp_locale;

    // This PHP-based method gets all months with any tasks, then checks each one for billable clients.
    $all_months_with_tasks_query = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT YEAR(meta_value) as task_year, MONTH(meta_value) as task_month
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = %s
             ORDER BY task_year DESC, task_month DESC",
            '_wcsl_task_date',
            'client_task',
            'publish'
        )
    );
    
    $billable_months = array();
    if ( function_exists('wcsl_get_billable_clients_for_month') && !empty($all_months_with_tasks_query) ) {
        foreach ($all_months_with_tasks_query as $month_data) {
            $billing_check = wcsl_get_billable_clients_for_month($month_data->task_month, $month_data->task_year, 1, 1);
            if ($billing_check['total_clients'] > 0) {
                $billable_months[] = $month_data;
            }
        }
    }
    
    // --- Display Page ---
    echo '<h2>' . esc_html__( 'Billing Overview', 'wp-client-support-ledger' ) . '</h2>';
    echo '<p>' . esc_html__( 'The list below shows months where at least one client has exceeded their contracted support hours.', 'wp-client-support-ledger' ) . '</p>';

    if ( empty( $billable_months ) ) {
        echo '<p>' . esc_html__( 'No months with billable clients found.', 'wp-client-support-ledger' ) . '</p>';
        return;
    }
    ?>
    <table class="wp-list-table widefat fixed striped wcsl-month-index-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Month / Year', 'wp-client-support-ledger' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'wp-client-support-ledger' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $billing_details_page_url = admin_url('admin.php?page=wcsl-billing-clients');

            foreach ( $billable_months as $row ) {
                $year  = intval( $row->task_year );
                $month = intval( $row->task_month );
                $month_name = $wp_locale->get_month( $month );

                // 1. View Details Link
                $view_details_url = add_query_arg( array(
                    'wcsl_billing_action' => 'view_billing_month',
                    'month'  => $month,
                    'year'   => $year,
                ), $billing_details_page_url );

                // 2. Print/Save PDF Link
                $print_nonce_action = 'wcsl_print_billing_report_' . $year . '_' . $month;
                $print_nonce = wp_create_nonce( $print_nonce_action );
                $print_url_args = array(
                    'action'       => 'wcsl_generate_billing_print_page',
                    'month'        => $month,
                    'year'         => $year,
                    '_wpnonce'     => $print_nonce,
                    'nonce_action' => $print_nonce_action
                );
                $print_url = add_query_arg( $print_url_args, admin_url( 'admin-post.php' ) );

                // <<< CHANGE: The entire block for the "Delete Month's Tasks" button has been removed. >>>

                ?>
                <tr>
                    <td><?php echo esc_html( $month_name ) . ' ' . esc_html( $year ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( $view_details_url ); ?>" class="button"><?php esc_html_e( 'View Details', 'wp-client-support-ledger' ); ?></a>
                        <a href="<?php echo esc_url( $print_url ); ?>" class="button" target="_blank"><?php esc_html_e( 'Print/Save PDF', 'wp-client-support-ledger' ); ?></a>
                        <?php // Delete button is now gone ?>
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    <?php
}

/**
 * Display the table of billable clients for a specific month.
 */
function wcsl_display_single_billing_month_details( $current_month, $current_year ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $current_month );

    $clients_per_page = 20;
    $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    
    $billing_data = array('clients' => array(), 'total_clients' => 0);
    if ( function_exists('wcsl_get_billable_clients_for_month') ) {
        $billing_data = wcsl_get_billable_clients_for_month( $current_month, $current_year, $clients_per_page, $paged );
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Error: The required data fetching function is missing.', 'wp-client-support-ledger') . '</p></div>';
    }

    ?>
    <p><a href="<?php echo esc_url( admin_url('admin.php?page=wcsl-billing-clients') ); ?>">« <?php esc_html_e('Back to Billing Month Index', 'wp-client-support-ledger'); ?></a></p>
    <h2 style="font-size:22px; margin-top: 10px; margin-bottom:10px;"><?php 
        printf( esc_html__( 'Billable Clients for %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ); 
    ?></h2>

    <div class="wcsl-month-actions" style="margin-bottom: 20px; padding-top:10px; border-top:1px solid #eee; text-align:right;">
        <?php
        $print_list_nonce = wp_create_nonce( 'wcsl_print_billing_list_' . $current_year . '_' . $current_month );
        $print_list_url_args = array('action' => 'wcsl_print_billing_list_page', 'month' => $current_month, 'year' => $current_year, '_wpnonce' => $print_list_nonce);
        $print_list_url = add_query_arg( $print_list_url_args, admin_url( 'admin-post.php' ) );
        ?>
        <a href="<?php echo esc_url( $print_list_url ); ?>" class="button button-secondary" target="_blank"><?php esc_html_e( 'Print/Save This List as PDF', 'wp-client-support-ledger' ); ?></a>
    </div>

    <form id="wcsl-billing-clients-form" method="get">
        <table class="wp-list-table widefat fixed striped clients-summary-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Contracted Hours', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Total Hours Spent', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Billable Hours', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col" style="width: 100px;"><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col" style="width: 260px;"><?php esc_html_e( 'Actions', 'wp-client-support-ledger' ); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $billing_data['clients'] ) ) : ?>
                    <?php foreach ( $billing_data['clients'] as $summary_item ) : ?>
                        <?php
                        $client_id = $summary_item['id'];
                        $client_hourly_rate = get_post_meta( $client_id, '_wcsl_client_hourly_rate', true );
                        $show_invoice_button = ( !empty($client_hourly_rate) && floatval($client_hourly_rate) > 0 );
                        $invoice_nonce = wp_create_nonce( 'wcsl_generate_invoice_' . $client_id . '_' . $current_year . '_' . $current_month );
                        $invoice_url_args = array('action' => 'wcsl_generate_single_invoice', 'client_id' => $client_id, 'month' => $current_month, 'year' => $current_year, '_wpnonce' => $invoice_nonce);
                        $invoice_url = add_query_arg( $invoice_url_args, admin_url( 'admin-post.php' ) );
                        $view_tasks_url_args = array('wcsl_billing_action' => 'view_client_tasks', 'client_id' => $client_id, 'month' => $current_month, 'year' => $current_year);
                        $view_tasks_url = add_query_arg( $view_tasks_url_args, admin_url('admin.php?page=wcsl-billing-clients') );
                        $invoice_data = wcsl_get_invoice_data( $client_id, $current_month, $current_year );
                        $invoice_status = $invoice_data ? $invoice_data->status : 'unpaid';
                        
                        // <<< CHANGE: Check the billable minutes directly from the summary data >>>
                        $has_billable = ( wcsl_parse_time_string_to_minutes( $summary_item['billable_hours_str'] ) > 0 );
                        $billable_class = $has_billable ? 'wcsl-billable-hours' : '';
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url( get_edit_post_link( $client_id ) ); ?>"><strong><?php echo esc_html( $summary_item['name'] ); ?></strong></a></td>
                            <td><?php echo esc_html( $summary_item['contracted_str'] ); ?></td>
                            <td><?php echo esc_html( $summary_item['total_spent_str'] ); ?></td>
                            <!-- <<< CHANGE: Apply the new class to the table cell >>> -->
                            <td class="<?php echo esc_attr( $billable_class ); ?>"><strong><?php echo esc_html( $summary_item['billable_hours_str'] ); ?></strong></td>
                            <td><span class="wcsl-invoice-status-badge status-<?php echo esc_attr($invoice_status); ?>"><?php echo esc_html( ucfirst($invoice_status) ); ?></span></td>
                            <td>
                                <div class="wcsl-billing-actions" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <a href="<?php echo esc_url($view_tasks_url); ?>" class="button"><?php esc_html_e( 'View Tasks', 'wp-client-support-ledger' ); ?></a>
                                    <?php if ( $show_invoice_button ) : ?>
                                        <?php if ( $invoice_status !== 'paid' && $invoice_status !== 'void' ) : ?>
                                            <a href="<?php echo esc_url($invoice_url); ?>" class="button button-primary" target="_blank"><?php echo $invoice_status === 'invoiced' ? esc_html__('Re-Generate', 'wp-client-support-ledger') : esc_html__('Generate Invoice', 'wp-client-support-ledger'); ?></a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="description"><em><?php esc_html_e( 'Set hourly rate to enable.', 'wp-client-support-ledger' ); ?></em></span>
                                    <?php endif; ?>
                                    <?php if ( $invoice_data && $invoice_status !== 'paid' && $invoice_status !== 'void' ) : ?>
                                        <span class="action-divider" style="margin: 0 4px;">|</span>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=wcsl_mark_invoice_paid&invoice_id=' . $invoice_data->id), 'wcsl_change_invoice_status_' . $invoice_data->id ) ); ?>" class="action-link"><?php esc_html_e('Mark Paid', 'wp-client-support-ledger'); ?></a>
                                        <span class="action-divider">|</span>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=wcsl_mark_invoice_void&invoice_id=' . $invoice_data->id), 'wcsl_change_invoice_status_' . $invoice_data->id ) ); ?>" class="action-link deletion"><?php esc_html_e('Void', 'wp-client-support-ledger'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr class="no-items"><td class="colspanchange" colspan="6"><?php esc_html_e( 'No clients with billable hours found for this period.', 'wp-client-support-ledger' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Contracted Hours', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Total Hours Spent', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Billable Hours', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Actions', 'wp-client-support-ledger' ); ?></th>
                </tr>
            </tfoot>
        </table>
        <?php
        $total_pages = ceil( $billing_data['total_clients'] / $clients_per_page );
        if ($total_pages > 1){
            echo '<div class="tablenav bottom"><div class="tablenav-pages">';
            $pagination_base_url = add_query_arg( array('page' => 'wcsl-billing-clients', 'wcsl_billing_action' => 'view_billing_month', 'month' => $current_month, 'year' => $current_year), admin_url('admin.php') );
            echo paginate_links(array( 'base' => add_query_arg( 'paged', '%#%', $pagination_base_url ), 'format' => '', 'current' => max(1, $paged), 'total' => $total_pages, 'prev_text' => '«', 'next_text' => '»' ));
            echo '</div></div>';
        }
        ?>
    </form>
    <?php
}



/**
 * Handler for generating the print page for BILLING clients.
 * Hooked to admin-post.php. This function performs security checks.
 */
function wcsl_handle_generate_billing_print_page() {
    // 1. Security and Parameter Checks
    if ( ! isset( $_GET['month'], $_GET['year'], $_GET['_wpnonce'], $_GET['nonce_action'] ) ) {
        wp_die(__( 'Invalid request: Missing parameters.', 'wp-client-support-ledger' ));
    }

    $month = intval( $_GET['month'] );
    $year  = intval( $_GET['year'] );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] );
    $nonce_action = sanitize_text_field( $_GET['nonce_action'] );

    if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
        wp_die( __( 'Security check failed. Please try again.', 'wp-client-support-ledger' ) );
    }
 // New, flexible permission check for Admin, Employee, or Client
    if ( ! is_user_logged_in() ) {
        wp_die( __( 'You must be logged in to perform this action.', 'wp-client-support-ledger' ) );
    }
    
    $user = wp_get_current_user();
    $allowed_roles = array('administrator', 'wcsl_employee', 'wcsl_client');
    // array_intersect finds common values between two arrays. If it's not empty, the user has at least one of the allowed roles.
    if ( empty( array_intersect( $allowed_roles, $user->roles ) ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    // 2. Call the display function
    wcsl_display_billing_print_page( $month, $year );
}
add_action( 'admin_post_wcsl_generate_billing_print_page', 'wcsl_handle_generate_billing_print_page' );


/**
 * Renders the clean, print-friendly HTML page containing ONLY the billable clients table.
 * This function is called by the handler above and ends with exit;.
 *
 * @param int $current_month The month to display.
 * @param int $current_year The year to display.
 */
function wcsl_display_billing_print_page( $current_month, $current_year ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $current_month );

    // --- Fetch ALL billable clients for the PDF (no pagination) ---
    $billing_data = array();
    if ( function_exists('wcsl_get_billable_clients_for_month') ) {
        // Call helper with -1 for per_page to get all results
        $billing_data_full = wcsl_get_billable_clients_for_month( $current_month, $current_year, -1, 1 );
        $billing_data = $billing_data_full['clients']; // We only need the clients array
    }
    
    // --- Output Print-Specific HTML ---
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php printf( esc_html__( 'Billing Report - %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ); ?></title>
        <link rel="stylesheet" id="wcsl-print-style" href="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/print-style.css' ); ?>" type="text/css" media="all">
    </head>
    <body class="wcsl-print-body">
        <div class="print-report-container">
            <div class="report-header">
                <h1><?php bloginfo('name'); ?> - <?php esc_html_e( 'Client Support Ledger', 'wp-client-support-ledger' ); ?></h1>
                <h2><?php printf( esc_html__( 'Billing Report: %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ); ?></h2>
                <p><?php esc_html_e( 'Showing all clients with billable hours for the period.', 'wp-client-support-ledger' ); ?></p>
            </div>

            <h3><?php esc_html_e( 'Billable Client Summary', 'wp-client-support-ledger' ); ?></h3>
            <?php if ( ! empty( $billing_data ) ) : ?>
            <table class="clients-summary-table"> <?php // Use a class for potential styling ?>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Contracted Hours', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Total Hours Spent', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Billable Hours', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $billing_data as $summary_item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $summary_item['name'] ); ?></td>
                        <td><?php echo esc_html( $summary_item['contracted_str'] ); ?></td>
                        <td><?php echo esc_html( $summary_item['total_spent_str'] ); ?></td>
                        <td><strong><?php echo esc_html( $summary_item['billable_hours_str'] ); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: echo '<p>' . esc_html__('No clients with billable hours found for this period.', 'wp-client-support-ledger') . '</p>'; endif; ?>

        </div> <!-- .print-report-container -->

        <script type="text/javascript">
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit; // CRITICAL: Stop WordPress from rendering anything else.
}



// In includes/admin-menu.php

/**
 * Displays a table of all tasks for a specific client for a given month.
 * Highlights rows with duplicate links (except the last instance).
 *
 * @param int $client_id The ID of the client.
 * @param int $month The month to display.
 * @param int $year The year to display.
 */
function wcsl_display_billable_client_tasks_page( $client_id, $month, $year ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $month );
    $client_name = get_the_title( $client_id );
    if (empty($client_name)) {
        wp_die('Error: Client not found.');
    }

    $back_url_args = array(
        'page' => 'wcsl-billing-clients',
        'wcsl_billing_action' => 'view_billing_month',
        'month' => $month,
        'year' => $year,
    );
    $back_url = add_query_arg( $back_url_args, admin_url('admin.php') );

    echo '<p><a href="' . esc_url( $back_url ) . '">« ' . esc_html__('Back to Billing Report for ', 'wp-client-support-ledger') . esc_html($month_name) . '</a></p>';
    echo '<h2 style="font-size:22px; margin-top: 10px;">' . sprintf( esc_html__( 'All Tasks for %1$s - %2$s %3$s', 'wp-client-support-ledger' ), esc_html($client_name), esc_html($month_name), esc_html($year) ) . '</h2>';

    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $month, 1, $year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $month + 1, 0, $year ) );

    $tasks_args = array(
        'post_type'      => 'client_task', 'posts_per_page' => -1, 'post_status'    => 'publish', 'meta_key'       => '_wcsl_task_date',
        'orderby'        => 'meta_value', 'order'          => 'ASC',
        'meta_query'     => array('relation' => 'AND', array( 'key' => '_wcsl_related_client_id', 'value' => $client_id ),
            array( 'key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE' ),
            array('relation' => 'OR', array('key' => '_wcsl_task_type', 'value' => 'support', 'compare' => '='), array('key' => '_wcsl_task_type', 'compare' => 'NOT EXISTS'))
        ),
    );
    $tasks_query = new WP_Query( $tasks_args );
    $task_list_for_processing = $tasks_query->get_posts();

    $task_links_with_ids = array();
    if ( !empty($task_list_for_processing) ) {
        foreach($task_list_for_processing as $task_post) {
            $link = get_post_meta( $task_post->ID, '_wcsl_task_link', true );
            if ( !empty($link) ) { $task_links_with_ids[$link][] = $task_post->ID; }
        }
    }
    
    // <<< MODIFIED LOGIC: Highlight all but the last duplicate >>>
    $duplicate_ids_to_highlight = array();
    foreach ( $task_links_with_ids as $link => $ids ) {
        if ( count($ids) > 1 ) {
            // Remove the last ID from this group.
            array_pop($ids); 
            // Add all the remaining (earlier) IDs to our master highlight list.
            $duplicate_ids_to_highlight = array_merge($duplicate_ids_to_highlight, $ids);
        }
    }
    ?>
    <table class="wp-list-table widefat fixed striped client-task-details-table">
        <thead>
            <tr>
                <th style="width:120px;"><?php esc_html_e( 'Task Date', 'wp-client-support-ledger' ); ?></th>
                <th class="column-primary"><?php esc_html_e( 'Task Title', 'wp-client-support-ledger' ); ?></th>
                <th><?php esc_html_e( 'Hours Spent', 'wp-client-support-ledger' ); ?></th>
                <th><?php esc_html_e( 'Employee', 'wp-client-support-ledger' ); ?></th>
                <th><?php esc_html_e( 'Task Link', 'wp-client-support-ledger' ); ?></th>
                <th><?php esc_html_e( 'Attachment', 'wp-client-support-ledger' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( !empty($task_list_for_processing) ) : ?>
                <?php foreach ( $task_list_for_processing as $task_post ) : ?>
                    <?php
                    $task_id = $task_post->ID;
                    // <<< MODIFIED LOGIC: Check against the new, correct array of IDs >>>
                    $is_duplicate_row = in_array( $task_id, $duplicate_ids_to_highlight );
                    $row_style = $is_duplicate_row ? 'style="background-color: #ffebe8;"' : '';
                    $task_link_url = get_post_meta( $task_id, '_wcsl_task_link', true );
                    $attachment_url = get_post_meta( $task_id, '_wcsl_task_attachment_url', true );
                    ?>
                    <tr <?php echo $row_style; ?>>
                        <td><?php echo esc_html( get_post_meta( $task_id, '_wcsl_task_date', true ) ); ?></td>
                        <td><a href="<?php echo esc_url(get_edit_post_link($task_id)); ?>"><strong><?php echo esc_html($task_post->post_title); ?></strong></a></td>
                        <td><?php echo esc_html( get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true ) ); ?></td>
                        <td><?php echo esc_html( get_post_meta( $task_id, '_wcsl_employee_name', true ) ); ?></td>
                        <td>
                            <?php if ( !empty($task_link_url) ) : ?>
                                <a href="<?php echo esc_url($task_link_url); ?>" target="_blank">Link</a>
                            <?php else: echo esc_html__('N/A', 'wp-client-support-ledger'); endif; ?>
                        </td>
                        <td>
                            <?php if ( !empty($attachment_url) ) : ?>
                                <a href="<?php echo esc_url($attachment_url); ?>" target="_blank"><?php esc_html_e( 'View Attachment', 'wp-client-support-ledger' ); ?></a>
                            <?php else: echo esc_html__('N/A', 'wp-client-support-ledger'); endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <tr class="no-items"><td class="colspanchange" colspan="6"><?php esc_html_e( 'No billable support tasks found for this client in this period.', 'wp-client-support-ledger' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}





/**
 * Display callback for the Settings & Help page.
 * Includes forms for Email Settings and Invoice Settings, plus help content.
 */
function wcsl_settings_help_page_display() {
    // Check user capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-client-support-ledger' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        
        <?php settings_errors(); ?>

        <h2 style="margin-top: 20px;"><?php esc_html_e( 'Portal Settings', 'wp-client-support-ledger' ); ?></h2>
        <form method="post" action="options.php">
            <?php
            // FIX: This group corresponds to the 'wcsl_portal_settings_page'
            settings_fields( 'wcsl_portal_settings_group' );
            do_settings_sections( 'wcsl_portal_settings_page' );
            submit_button( __( 'Save Portal Settings', 'wp-client-support-ledger' ) );
            ?>
        </form>

        <hr>

        <h2 style="margin-top: 20px;"><?php esc_html_e( 'Notification Settings', 'wp-client-support-ledger' ); ?></h2>
        <form method="post" action="options.php">
            <?php
            // FIX: This group corresponds to the 'wcsl_email_settings_section_page'
            settings_fields( 'wcsl_settings_group' );
            do_settings_sections( 'wcsl_email_settings_section_page' );
            submit_button( __( 'Save Notification Settings', 'wp-client-support-ledger' ) );
            ?>
        </form>
        
        <hr>

        <h2 style="margin-top: 20px;"><?php esc_html_e( 'Invoice Settings', 'wp-client-support-ledger' ); ?></h2>
        <form method="post" action="options.php">
            <?php
            // FIX: This group corresponds to the 'wcsl_invoice_settings_page'
            settings_fields( 'wcsl_invoice_settings_group' );
            do_settings_sections( 'wcsl_invoice_settings_page' );
            submit_button( __( 'Save Invoice Settings', 'wp-client-support-ledger' ) );
            ?>
        </form>

        <hr>

        <h2 style="margin-top: 20px;"><?php esc_html_e( 'Tools & Help', 'wp-client-support-ledger' ); ?></h2>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e( 'Import / Export', 'wp-client-support-ledger' ); ?></span></h2>
                            <div class="inside">
                                <?php do_settings_sections( 'wcsl_import_export_page' ); ?>
                            </div>
                        </div>
                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e( 'Shortcode Usage', 'wp-client-support-ledger' ); ?></span></h2>
                            <div class="inside">
                                <p><strong><?php esc_html_e( 'Client & Employee Portal Shortcode:', 'wp-client-support-ledger' ); ?></strong></p>
                                <p><code>[wcsl_portal]</code></p>
                                <p><?php esc_html_e( 'Place this shortcode on the page you selected in the "Portal Configuration" settings above. It will automatically show the correct interface for logged-in Clients and Employees.', 'wp-client-support-ledger' ); ?></p>
                            </div>
                        </div>
                         <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e( 'Tips for Printing / Saving as PDF', 'wp-client-support-ledger' ); ?></span></h2>
                            <div class="inside">
                                <p><strong><?php esc_html_e( 'Getting Clickable Links in PDFs:', 'wp-client-support-ledger' ); ?></strong></p>
                                <ul>
                                    <li><?php esc_html_e( 'Choose "Save as PDF" as the destination/printer.', 'wp-client-support-ledger' ); ?></li>
                                    <li><?php esc_html_e( 'Avoid using "Microsoft Print to PDF" if you need clickable links.', 'wp-client-support-ledger' ); ?></li>
                                </ul>
                                <p><strong><?php esc_html_e( 'Removing Headers/Footers from PDF:', 'wp-client-support-ledger' ); ?></strong></p>
                                <ul>
                                    <li><?php esc_html_e( 'In the browser\'s print dialog, look for "More settings".', 'wp-client-support-ledger' ); ?></li>
                                    <li><?php esc_html_e( 'Uncheck the option for "Headers and footers".', 'wp-client-support-ledger' ); ?></li>
                                </ul>
                                <hr>
                                <p><strong><?php esc_html_e( 'Important Note on Email Deliverability (SMTP):', 'wp-client-support-ledger' ); ?></strong></p>
                                <p><?php esc_html_e( 'For reliable email notifications, it is highly recommended to configure an SMTP service using a plugin like WP Mail SMTP.', 'wp-client-support-ledger' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e( 'Plugin Support', 'wp-client-support-ledger' ); ?></span></h2>
                            <div class="inside">
                                <p><?php esc_html_e( 'If you need help or find a bug, please reach out via sezan@razibmarketing.net.', 'wp-client-support-ledger' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br class="clear">
        </div>
    </div>
    <?php
}

/**
 * Displays an index of months that have task data.
 */
function wcsl_display_month_index() {
    global $wpdb;

    // --- NEW: Dashboard Data Fetching ---
    $current_month = date( 'n' );
    $current_year  = date( 'Y' );
    
    // Get data using our new helper functions
    $metrics = function_exists('wcsl_get_dashboard_metrics') ? wcsl_get_dashboard_metrics( $current_month, $current_year ) : array();
    $watchlist = function_exists('wcsl_get_clients_nearing_limit') ? wcsl_get_clients_nearing_limit( $current_month, $current_year ) : array();
    $recent_activity = function_exists('wcsl_get_recent_activity') ? wcsl_get_recent_activity() : array();
    ?>

    <!-- NEW: Dashboard HTML Structure -->
    <div class="wcsl-dashboard">
        <h2><?php printf( esc_html__( 'Dashboard for %s', 'wp-client-support-ledger' ), esc_html( date_i18n('F Y') ) ); ?></h2>

        <!-- Metric Boxes -->
        <div class="wcsl-dashboard-metrics">
            <div class="wcsl-metric-box">
                <h3><?php esc_html_e( 'Total Hours Logged', 'wp-client-support-ledger' ); ?></h3>
                <div class="metric-value"><?php echo isset($metrics['total_minutes']) ? esc_html( wcsl_format_minutes_to_time_string( $metrics['total_minutes'] ) ) : '0m'; ?></div>
            </div>
            <div class="wcsl-metric-box">
                <h3><?php esc_html_e( 'Billable Hours This Month', 'wp-client-support-ledger' ); ?></h3>
                <div class="metric-value"><?php echo isset($metrics['billable_minutes']) ? esc_html( wcsl_format_minutes_to_time_string( $metrics['billable_minutes'] ) ) : '0m'; ?></div>
            </div>
            <div class="wcsl-metric-box">
                <h3><?php esc_html_e( 'Active Tasks', 'wp-client-support-ledger' ); ?></h3>
                <div class="metric-value"><?php echo isset($metrics['active_tasks']) ? esc_html( number_format_i18n($metrics['active_tasks']) ) : '0'; ?></div>
            </div>
        </div>

        <!-- Dashboard Columns -->
        <div class="wcsl-dashboard-columns">
            <!-- Watchlist Panel -->
            <div class="wcsl-dashboard-panel">
                <h3><?php esc_html_e( 'Clients Nearing Limit (80%+)', 'wp-client-support-ledger' ); ?></h3>
                <div class="inside">
                    <?php if ( ! empty( $watchlist ) ) : ?>
                        <table class="wcsl-dashboard-watchlist-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                                    <th><?php esc_html_e( 'Usage', 'wp-client-support-ledger' ); ?></th>
                                    <th style="width: 120px;"><?php esc_html_e( 'Percent Used', 'wp-client-support-ledger' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $watchlist as $client ) : ?>
                                    <tr>
                                        <td><a href="<?php echo esc_url( $client['link'] ); ?>"><?php echo esc_html( $client['name'] ); ?></a></td>
                                        <td><?php echo esc_html( $client['usage_str'] ); ?></td>
                                        <td>
                                            <!-- <<< CHANGE: Added a div to hold both bar and text >>> -->
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div class="wcsl-usage-bar-container">
                                                    <div class="wcsl-usage-bar <?php echo $client['percentage'] >= 100 ? 'danger' : ''; ?>" style="width: <?php echo min(100, $client['percentage']); ?>%;"></div>
                                                </div>
                                                <!-- <<< NEW: Display the percentage text >>> -->
                                                <span class="wcsl-percentage-text"><?php echo esc_html( $client['percentage'] ); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p style="padding: 15px; text-align: center;"><?php esc_html_e( 'No clients have reached the 80% threshold this month.', 'wp-client-support-ledger' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Recent Activity Panel -->
            <div class="wcsl-dashboard-panel">
                <h3><?php esc_html_e( 'Recent Task Activity', 'wp-client-support-ledger' ); ?></h3>
                <div class="inside">
                    <?php if ( ! empty( $recent_activity ) ) : ?>
                        <ul class="wcsl-recent-activity-list">
                            <?php foreach ( $recent_activity as $task ) : ?>
                                <li>
                                    <a href="<?php echo esc_url( $task['task_link'] ); ?>"><?php echo esc_html( $task['task_title'] ); ?></a> for <a href="<?php echo esc_url( $task['client_link'] ); ?>"><?php echo esc_html( $task['client_name'] ); ?></a>
                                    <span class="activity-time"><?php printf( esc_html__( 'Last updated: %s', 'wp-client-support-ledger' ), esc_html( $task['modified_date'] ) ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p style="padding: 15px; text-align: center;"><?php esc_html_e( 'No recent task activity found.', 'wp-client-support-ledger' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <hr>

    <?php
    // --- EXISTING MONTH INDEX LOGIC (Unchanged from your provided code) ---
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT YEAR(meta_value) as task_year, MONTH(meta_value) as task_month
             FROM {$wpdb->postmeta}
             WHERE meta_key = %s
             ORDER BY task_year DESC, task_month DESC",
            '_wcsl_task_date'
        )
    );

    if ( empty( $results ) ) {
        echo '<p>' . esc_html__( 'No task data found to generate a month index. Please add some tasks.', 'wp-client-support-ledger' ) . '</p>';
        return;
    }

    echo '<h2 style="font-size:30px;">' . esc_html__( 'Monthly Data Archive', 'wp-client-support-ledger' ) . '</h2>';
    echo '<p>' . esc_html__( 'Select a month below to view details, print/save a PDF report, or manage tasks for that period.', 'wp-client-support-ledger' ) . '</p>';

    echo '<table class="wp-list-table widefat fixed striped wcsl-month-index-table">';
    echo '<thead><tr><th>' . esc_html__( 'Month / Year', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Actions', 'wp-client-support-ledger' ) . '</th></tr></thead>';
    echo '<tbody>';

    foreach ( $results as $row ) {
        $loop_year  = intval( $row->task_year );
        $loop_month = intval( $row->task_month );

        $timestamp = mktime( 0, 0, 0, $loop_month, 1, $loop_year );
        $month_name = date_i18n( 'F', $timestamp );

        $view_url = admin_url( 'admin.php?page=wcsl-main-menu&action=view_month_details&month=' . $loop_month . '&year=' . $loop_year );
        
        $print_nonce_action = 'wcsl_print_report_action_' . $loop_year . '_' . $loop_month;
        $print_nonce = wp_create_nonce( $print_nonce_action );
        
        $delete_nonce_action = 'wcsl_delete_month_tasks_action_' . $loop_year . '_' . $loop_month;
        $delete_nonce = wp_create_nonce( $delete_nonce_action );

        $print_url_args = array('action' => 'wcsl_generate_print_page', 'month' => $loop_month, 'year' => $loop_year, '_wpnonce' => $print_nonce, 'nonce_action' => $print_nonce_action);
        $print_url = add_query_arg( $print_url_args, admin_url( 'admin-post.php' ) );
        
        $delete_url_args = array('action' => 'wcsl_handle_delete_month_tasks', 'month' => $loop_month, 'year' => $loop_year, '_wpnonce' => $delete_nonce);
        $delete_url = add_query_arg( $delete_url_args, admin_url( 'admin-post.php' ) );

        echo '<tr>';
        echo '<td>' . esc_html( $month_name ) . ' ' . esc_html( $loop_year ) . '</td>';
        echo '<td>';
        echo '<a href="' . esc_url( $view_url ) . '" class="button">' . esc_html__( 'View Details', 'wp-client-support-ledger' ) . '</a> ';
        echo '<a href="' . esc_url( $print_url ) . '" class="button" target="_blank">' . esc_html__( 'Print/Save PDF', 'wp-client-support-ledger' ) . '</a> ';
        echo '<a href="' . esc_url( $delete_url ) . '" class="button button-link-delete deletion" 
            onclick="return confirm(\'' . sprintf(esc_js(__('Are you sure you want to PERMANENTLY DELETE all tasks for %s %s? This action cannot be undone!')), esc_js($month_name), esc_js($loop_year)) . '\');">' 
            . esc_html__( 'Delete Month\'s Tasks', 'wp-client-support-ledger' ) . '</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}


/**
 * Displays the detailed Client Summary and Task Log for a single month.
 * Pagination is removed from this admin view.
 */
function wcsl_display_single_month_details( $current_month, $current_year ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $current_month );

    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $current_month, 1, $current_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $current_month + 1, 0, $current_year ) );

    // Get search terms and filters
    $search_term_summary = isset( $_GET['wcsl_search_summary'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsl_search_summary'] ) ) : '';
    $search_term_tasks   = isset( $_GET['wcsl_search_tasks'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsl_search_tasks'] ) ) : '';
    $filter_client_id    = isset( $_GET['filter_client'] ) ? intval( $_GET['filter_client'] ) : 0;
    $filter_employee_id  = isset( $_GET['filter_employee'] ) ? intval( $_GET['filter_employee'] ) : 0;
    $filter_status       = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : '';
    $filter_task_type    = isset( $_GET['filter_task_type'] ) ? sanitize_key( $_GET['filter_task_type'] ) : '';
    
    // Pagination variables
    $clients_per_page = 5;
    $paged_clients    = isset( $_GET['paged_clients'] ) ? max( 1, intval( $_GET['paged_clients'] ) ) : 1;
    $tasks_per_page   = 15;
    $paged_tasks      = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

    echo '<p><a href="' . esc_url( admin_url('admin.php?page=wcsl-main-menu') ) . '">' . esc_html__('« Back to Month Index', 'wp-client-support-ledger') . '</a></p>';
    
    echo '<h2 style="font-size:22px; margin-top: 10px; margin-bottom:10px;">' . sprintf( esc_html__( 'Details for %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ) . '</h2>';
    
    echo '<div class="wcsl-month-actions" style="margin-bottom: 20px; padding-bottom:15px; border-bottom:1px solid #eee; text-align:right;">';
    $print_nonce_action = 'wcsl_print_report_action_' . $current_year . '_' . $current_month;
    $print_nonce = wp_create_nonce( $print_nonce_action );
    $print_url_args = array('action'   => 'wcsl_generate_print_page', 'month'    => $current_month, 'year'     => $current_year, '_wpnonce' => $print_nonce, 'nonce_action' => $print_nonce_action);
    if ( !empty($search_term_summary) ) $print_url_args['search_summary'] = $search_term_summary;
    if ( !empty($search_term_tasks) )   $print_url_args['search_tasks'] = $search_term_tasks;
    $print_url = add_query_arg( $print_url_args, admin_url( 'admin-post.php' ) );
    echo '<a href="' . esc_url( $print_url ) . '" class="button button-secondary" target="_blank" style="margin-right:10px;">' . esc_html__( 'Print/Save PDF (Full)', 'wp-client-support-ledger' ) . '</a>';
    echo '</div>';

    ?>
    <div class="wcsl-section-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h3><?php esc_html_e( 'Client Summary', 'wp-client-support-ledger' ); ?></h3>
        <form method="GET" class="wcsl-table-search-form">
            <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>"><input type="hidden" name="action" value="view_month_details"><input type="hidden" name="month" value="<?php echo esc_attr( $current_month ); ?>"><input type="hidden" name="year" value="<?php echo esc_attr( $current_year ); ?>">
            <?php if ( !empty($search_term_tasks) ) : ?><input type="hidden" name="wcsl_search_tasks" value="<?php echo esc_attr( $search_term_tasks ); ?>"><?php endif; ?>
            <?php if ($paged_tasks > 1) : ?><input type="hidden" name="paged" value="<?php echo esc_attr( $paged_tasks ); ?>"><?php endif; ?>
            <label for="wcsl_search_summary_input" class="screen-reader-text"><?php esc_html_e( 'Search Clients:', 'wp-client-support-ledger' ); ?></label>
            <input type="search" id="wcsl_search_summary_input" name="wcsl_search_summary" value="<?php echo esc_attr( $search_term_summary ); ?>" placeholder="<?php esc_attr_e('Search Clients...', 'wp-client-support-ledger'); ?>" />
            <input type="submit" class="button" value="<?php esc_attr_e('Search', 'wp-client-support-ledger'); ?>" />
            <?php if ( !empty($search_term_summary) ) : ?><a href="<?php echo esc_url( remove_query_arg(array('wcsl_search_summary', 'paged_clients')) ); ?>" class="button button-link"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a><?php endif; ?>
        </form>
    </div>
    <?php
    $clients_query_args = array('post_type' => 'client', 'posts_per_page' => $clients_per_page, 'paged' => $paged_clients, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish');
    if ( !empty($search_term_summary) ) { $clients_query_args['s'] = $search_term_summary; }
    $clients_query = new WP_Query( $clients_query_args );
    if ( $clients_query->have_posts() ) :
        echo '<table class="wp-list-table widefat fixed striped clients-summary-table">';
        echo '<thead><tr><th>' . esc_html__( 'Client', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Contracted Hours', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Total Hours Spent', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Fixing Hours', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Billable Hours', 'wp-client-support-ledger' ) . '</th></tr></thead>';
        echo '<tbody>';
        while ( $clients_query->have_posts() ) : $clients_query->the_post();
            $client_id = get_the_ID();
            $client_name_summary = get_the_title();
            $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
            $client_tasks_args = array('post_type' => 'client_task', 'posts_per_page' => -1, 'post_status' => 'publish', 'meta_query' => array('relation' => 'AND', array('key' => '_wcsl_related_client_id', 'value' => $client_id), array('key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE')));
            $client_tasks_query = new WP_Query( $client_tasks_args );
            $total_minutes_spent_this_month = 0; $total_support_minutes_this_month = 0; $total_fixing_minutes_this_month = 0;
            if ( $client_tasks_query->have_posts() ) {
                while ( $client_tasks_query->have_posts() ) : $client_tasks_query->the_post();
                    $task_id_inner = get_the_ID();
                    $hours_spent_str = get_post_meta( $task_id_inner, '_wcsl_hours_spent_on_task', true );
                    $minutes_for_task = wcsl_parse_time_string_to_minutes( $hours_spent_str );
                    $task_type = get_post_meta( $task_id_inner, '_wcsl_task_type', true );
                    $total_minutes_spent_this_month += $minutes_for_task;
                    if ( $task_type === 'fixing' ) { $total_fixing_minutes_this_month += $minutes_for_task; } else { $total_support_minutes_this_month += $minutes_for_task; }
                endwhile;
            }
            wp_reset_postdata();
            $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );
            $billable_minutes = max( 0, $total_support_minutes_this_month - $contracted_minutes );
            
            // <<< CHANGE: Add a class if there are billable minutes >>>
            $billable_class = $billable_minutes > 0 ? 'wcsl-billable-hours' : '';

            echo '<tr><td><a href="' . esc_url( get_edit_post_link( $client_id ) ) . '">' . esc_html( $client_name_summary ) . '</a></td><td>' . esc_html( !empty($contracted_hours_str) ? $contracted_hours_str : 'N/A' ) . '</td><td>' . esc_html( wcsl_format_minutes_to_time_string( $total_minutes_spent_this_month ) ) . '</td><td>' . esc_html( wcsl_format_minutes_to_time_string( $total_fixing_minutes_this_month ) ) . '</td>';
            // <<< CHANGE: Apply the new class to the table cell >>>
            echo '<td class="' . esc_attr( $billable_class ) . '">' . esc_html( wcsl_format_minutes_to_time_string( $billable_minutes ) ) . '</td></tr>';
        endwhile;
        echo '</tbody></table>';
        $total_client_pages = $clients_query->max_num_pages;
        if ($total_client_pages > 1){
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">';
            $client_pagination_base_args = array('page' => $_REQUEST['page'], 'action' => 'view_month_details', 'month' => $current_month, 'year' => $current_year);
            if (!empty($search_term_summary)) { $client_pagination_base_args['wcsl_search_summary'] = $search_term_summary; }
            if (!empty($search_term_tasks)) { $client_pagination_base_args['wcsl_search_tasks'] = $search_term_tasks; }
            if ($paged_tasks > 1) { $client_pagination_base_args['paged'] = $paged_tasks; }
            $client_pagination_base_url = add_query_arg($client_pagination_base_args, admin_url('admin.php'));
            echo paginate_links(array('base' => add_query_arg( 'paged_clients', '%#%', $client_pagination_base_url ), 'format' => '', 'current' => max(1, $paged_clients), 'total' => $total_client_pages, 'prev_text' => __('« Prev Clients'), 'next_text' => __('Next Clients »'), 'add_args' => false));
            echo '</div></div>';
        }
        wp_reset_postdata();
    else :
        echo '<p>' . esc_html__( 'No clients found for this period or matching your search.', 'wp-client-support-ledger' ) . '</p>';
    endif;
    echo '<br><hr/><br>';
    ?>
    <div class="wcsl-section-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
        <h3><?php esc_html_e( 'Detailed Task Log', 'wp-client-support-ledger' ); ?></h3>
        <div class="wcsl-task-log-actions" style="text-align: right;">
            <?php
            $export_url_args = array('action' => 'wcsl_export_tasks_csv', 'month' => $current_month, 'year' => $current_year, 'filter_client' => $filter_client_id, 'filter_employee' => $filter_employee_id, 'filter_status' => $filter_status, 'filter_task_type' => $filter_task_type, 'wcsl_search_tasks' => $search_term_tasks, '_wpnonce' => wp_create_nonce('wcsl_export_tasks_csv_nonce'));
            ?>
            <a href="<?php echo esc_url( add_query_arg( $export_url_args, admin_url('admin-post.php') ) ); ?>" class="button button-secondary" style="margin-bottom: 20px;"><?php esc_html_e( 'Export to CSV', 'wp-client-support-ledger' ); ?></a>
            <form method="GET" class="wcsl-table-search-form" style="display:flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>"><input type="hidden" name="action" value="view_month_details"><input type="hidden" name="month" value="<?php echo esc_attr( $current_month ); ?>"><input type="hidden" name="year" value="<?php echo esc_attr( $current_year ); ?>">
                <?php
                $clients = get_posts(array('post_type' => 'client', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                $client_options = array(); foreach ($clients as $client) { $client_options[$client->ID] = $client->post_title; }
                $employees = get_posts(array('post_type' => 'employee', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                $employee_options = array(); foreach ($employees as $employee) { $employee_options[$employee->ID] = $employee->post_title; }
                $status_options = array('pending' => 'Pending', 'in-progress' => 'In Progress', 'in-review' => 'In Review', 'completed' => 'Completed', 'billed' => 'Billed');
                $type_options = array('support' => 'Support', 'fixing' => 'Fixing');
                wcsl_display_task_filter_dropdown('filter_client', $client_options, 'All Clients');
                wcsl_display_task_filter_dropdown('filter_employee', $employee_options, 'All Employees');
                wcsl_display_task_filter_dropdown('filter_status', $status_options, 'All Statuses');
                wcsl_display_task_filter_dropdown('filter_task_type', $type_options, 'All Task Types');
                ?>
                <input type="search" name="wcsl_search_tasks" value="<?php echo esc_attr( $search_term_tasks ); ?>" placeholder="<?php esc_attr_e('Search Tasks...', 'wp-client-support-ledger'); ?>" />
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'wp-client-support-ledger'); ?>" />
                <a href="<?php echo esc_url( remove_query_arg(array('wcsl_search_tasks', 'paged', 'filter_client', 'filter_employee', 'filter_status', 'filter_task_type')) ); ?>" class="button button-link"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a>
            </form>
        </div>
    </div>
    <?php
    $meta_query = array('relation' => 'AND', array('key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE' ));
    if ( $filter_client_id > 0 ) { $meta_query[] = array('key' => '_wcsl_related_client_id', 'value' => $filter_client_id); }
    if ( $filter_employee_id > 0 ) { $meta_query[] = array('key' => '_wcsl_assigned_employee_id', 'value' => $filter_employee_id); }
    if ( !empty($filter_status) ) { $meta_query[] = array('key' => '_wcsl_task_status', 'value' => $filter_status); }
    if ( !empty($filter_task_type) ) { $meta_query[] = array('key' => '_wcsl_task_type', 'value' => $filter_task_type); }
    $all_tasks_args = array('post_type' => 'client_task', 'posts_per_page' => $tasks_per_page, 'paged' => $paged_tasks, 'post_status' => 'publish', 's' => $search_term_tasks, 'meta_query' => $meta_query);
    $all_tasks_query = new WP_Query( $all_tasks_args );
    if ( $all_tasks_query->have_posts() ) :
        echo '<table class="wp-list-table widefat fixed striped wcsl-tasks-log-table">';
        echo '<thead><tr><th>' . esc_html__( 'Date', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Client', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Task Title', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Task Link', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Hours Spent', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Status', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Employee', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Attachment', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Note', 'wp-client-support-ledger' ) . '</th></tr></thead>';
        echo '<tbody>';
        while ( $all_tasks_query->have_posts() ) : $all_tasks_query->the_post();
            $task_id = get_the_ID();
            $task_title = get_the_title();
            $task_type_for_highlight = get_post_meta( $task_id, '_wcsl_task_type', true );
            $row_class = '';
            if ( $task_type_for_highlight === 'fixing' ) { $row_class = 'wcsl-task-type-fixing'; }
            $task_date = get_post_meta( $task_id, '_wcsl_task_date', true );
            $task_link_url = get_post_meta( $task_id, '_wcsl_task_link', true );
            $hours_spent_str = get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true );
            $task_status = get_post_meta( $task_id, '_wcsl_task_status', true );
            $related_client_id = get_post_meta( $task_id, '_wcsl_related_client_id', true );
            $client_name_task = $related_client_id ? get_the_title( $related_client_id ) : __( 'N/A', 'wp-client-support-ledger' );
            $employee_name_meta = get_post_meta( $task_id, '_wcsl_employee_name', true );
            $employee_name = !empty($employee_name_meta) ? $employee_name_meta : __( 'N/A', 'wp-client-support-ledger' );
            $task_note = get_post_meta( $task_id, '_wcsl_task_note', true );
            $attachment_url = get_post_meta( $task_id, '_wcsl_task_attachment_url', true );
            echo '<tr class="' . esc_attr( $row_class ) . '">';
            echo '<td>' . esc_html( $task_date ) . '</td>'; echo '<td>' . esc_html( $client_name_task ) . '</td>'; echo '<td><a href="' . esc_url( get_edit_post_link( $task_id ) ) . '">' . esc_html( $task_title ) . '</a></td>';
            echo '<td>'; if ( ! empty( $task_link_url ) ) { echo '<a href="' . esc_url( $task_link_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Task', 'wp-client-support-ledger' ) . '</a>'; } else { echo esc_html__( 'N/A', 'wp-client-support-ledger' ); } echo '</td>';
            echo '<td>' . esc_html( !empty($hours_spent_str) ? $hours_spent_str : '0m') . '</td>';
            echo '<td>'; wcsl_display_status_badge( $task_status ); echo '</td>';
            echo '<td>' . esc_html( $employee_name ) . '</td>';
            echo '<td>'; if ( ! empty( $attachment_url ) ) { echo '<a href="' . esc_url( $attachment_url ) . '" target="_blank">' . esc_html__( 'View Attachment', 'wp-client-support-ledger' ) . '</a>'; } else { echo esc_html__( 'N/A', 'wp-client-support-ledger' ); } echo '</td>';
            echo '<td>' . nl2br( esc_html( $task_note ) ) . '</td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';
        $total_task_pages = $all_tasks_query->max_num_pages;
        if ($total_task_pages > 1){
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">';
            $task_pagination_base_args = array('page' => $_REQUEST['page'], 'action' => 'view_month_details', 'month' => $current_month, 'year' => $current_year);
            if (!empty($search_term_summary)) { $task_pagination_base_args['wcsl_search_summary'] = $search_term_summary; }
            if (!empty($search_term_tasks)) { $task_pagination_base_args['wcsl_search_tasks'] = $search_term_tasks; }
            if ($paged_clients > 1) { $task_pagination_base_args['paged_clients'] = $paged_clients; }
            if ($filter_client_id > 0) $task_pagination_base_args['filter_client'] = $filter_client_id;
            if ($filter_employee_id > 0) $task_pagination_base_args['filter_employee'] = $filter_employee_id;
            if (!empty($filter_status)) $task_pagination_base_args['filter_status'] = $filter_status;
            if (!empty($filter_task_type)) $task_pagination_base_args['filter_task_type'] = $filter_task_type;
            $task_pagination_base_url = add_query_arg($task_pagination_base_args, admin_url('admin.php'));
            echo paginate_links(array('base' => add_query_arg( 'paged', '%#%', $task_pagination_base_url ), 'format' => '', 'current' => max(1, $paged_tasks), 'total' => $total_task_pages, 'prev_text' => __('« Prev Tasks'), 'next_text' => __('Next Tasks »'), 'add_args' => false));
            echo '</div></div>';
        }
        wp_reset_postdata();
    else :
        echo '<p>' . esc_html__( 'No tasks found for this period or matching your search.', 'wp-client-support-ledger' ) . '</p>';
    endif;
}





// In includes/admin-menu.php

/**
 * Display callback for the Reports page.
 */
function wcsl_reports_page_display() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-client-support-ledger' ) );
    }

    $current_url_base = admin_url('admin.php?page=wcsl-reports');
    $today = current_time('Y-m-d');
    $default_start_date = date('Y-m-d', strtotime('-29 days', strtotime($today)));

    $filter_start_date = isset( $_GET['start_date'] ) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date']) 
                           ? sanitize_text_field( $_GET['start_date'] ) 
                           : $default_start_date;
    $filter_end_date   = isset( $_GET['end_date'] ) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date'])
                           ? sanitize_text_field( $_GET['end_date'] )
                           : $today;
    
    if (strtotime($filter_end_date) < strtotime($filter_start_date)) {
        $filter_end_date = $filter_start_date;
    }
    ?>
    <div class="wrap wcsl-reports-page">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form method="GET" action="<?php echo esc_url( $current_url_base ); ?>" class="wcsl-reports-filter-form" style="margin-bottom:20px; padding:15px; background:#f9f9f9; border:1px solid #e5e5e5;">
            <input type="hidden" name="page" value="wcsl-reports">
            <label for="wcsl_start_date"><?php esc_html_e('Start Date:', 'wp-client-support-ledger'); ?></label>
            <input type="date" id="wcsl_start_date" name="start_date" value="<?php echo esc_attr($filter_start_date); ?>" max="<?php echo esc_attr($today); ?>">
            <label for="wcsl_end_date" style="margin-left: 15px;"><?php esc_html_e('End Date:', 'wp-client-support-ledger'); ?></label>
            <input type="date" id="wcsl_end_date" name="end_date" value="<?php echo esc_attr($filter_end_date); ?>" max="<?php echo esc_attr($today); ?>">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Filter Report', 'wp-client-support-ledger'); ?>" style="margin-left: 15px;">
            <?php if ( $filter_start_date !== $default_start_date || $filter_end_date !== $today ) : ?>
                <a href="<?php echo esc_url( $current_url_base ); ?>" class="button" style="margin-left: 5px;">
                    <?php esc_html_e('Reset to Last 30 Days', 'wp-client-support-ledger'); ?>
                </a>
            <?php endif; ?>
        </form>
        <hr style="margin-bottom: 20px;">

        <div class="wcsl-reports-grid">
            <div class="wcsl-report-block">
                <h2><?php printf( esc_html__( 'Hours Per Client (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h2>
                <div class="wcsl-chart-container"><canvas id="wcslHoursPerClientChart"></canvas></div>
            </div>
            <div class="wcsl-report-block">
                <h2><?php printf( esc_html__( 'Total Billable Hours by Client (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h2>
                <div class="wcsl-chart-container"><canvas id="wcslBillableHoursPerClientChart"></canvas></div>
            </div>
            <div class="wcsl-report-block">
                <h2><?php printf( esc_html__( 'Total Billable Hours (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h2>
                <div class="wcsl-data-metric-container">
                    <?php
                    if ( function_exists('wcsl_get_total_billable_minutes_for_period') && function_exists('wcsl_format_minutes_to_time_string') ) {
                        $total_billable_minutes = wcsl_get_total_billable_minutes_for_period( $filter_start_date, $filter_end_date );
                        $total_billable_string = wcsl_format_minutes_to_time_string( $total_billable_minutes );
                        echo '<p class="wcsl-metric-value">' . esc_html( $total_billable_string ) . '</p>';
                        if ($total_billable_minutes > 0) {
                            echo '<p class="wcsl-metric-description">' . esc_html__('Total billable hours logged for the selected period.', 'wp-client-support-ledger') . '</p>';
                        } else {
                            echo '<p class="wcsl-metric-description">' . esc_html__('No billable hours logged for the selected period.', 'wp-client-support-ledger') . '</p>';
                        }
                    } else {
                        echo '<p class="wcsl-error-message">' . esc_html__('Required helper functions for billable hours report are missing.', 'wp-client-support-ledger') . '</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="wcsl-report-block">
                 <h2><?php printf( esc_html__( 'Hours by Employee (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h2>
                <div class="wcsl-chart-container" style="height: 350px;"><canvas id="wcslHoursByEmployeeChart"></canvas></div>
            </div>
        </div>

        <!-- <<< NEW: Full-width container for the Task Analysis chart >>> -->
        <div class="wcsl-reports-grid-full-width">
            <div class="wcsl-report-block">
                <div class="wcsl-report-header-with-tabs">
                    <h2><?php printf( esc_html__( 'Task Analysis (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h2>
                    <div class="wcsl-tabs-nav">
                        <button class="wcsl-tab-link active" data-target="supportTasksChartContainer"><?php esc_html_e( 'Support Tasks', 'wp-client-support-ledger' ); ?></button>
                        <button class="wcsl-tab-link" data-target="fixingTasksChartContainer"><?php esc_html_e( 'Fixing Tasks', 'wp-client-support-ledger' ); ?></button>
                    </div>
                </div>
                <div id="supportTasksChartContainer" class="wcsl-chart-container wcsl-tab-content active">
                    <canvas id="wcslSupportTasksChart"></canvas>
                </div>
                <div id="fixingTasksChartContainer" class="wcsl-chart-container wcsl-tab-content" style="display: none;">
                    <canvas id="wcslFixingTasksChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Billable Hours Trend chart -->
        <div class="wcsl-reports-grid-full-width">
            <div class="wcsl-report-block">
                <h2><?php esc_html_e( 'Billable Hours Trend (Last 12 Months)', 'wp-client-support-ledger' ); ?></h2>
                <div class="wcsl-chart-container">
                    <canvas id="wcslBillableTrendChart"></canvas>
                </div>
            </div>
        </div>

    </div>
    <?php
}





/**
 * Handles bulk actions submitted from the Notifications page.
 * Hooked to admin_init or a specific load action.
 */
function wcsl_handle_bulk_notification_actions() {
    // Check if we are on our notifications page and if a bulk action was submitted
    if ( isset( $_POST['page'] ) && $_POST['page'] === 'wcsl-notifications' &&
         ( (isset( $_POST['action'] ) && $_POST['action'] !== '-1') || (isset( $_POST['action2'] ) && $_POST['action2'] !== '-1') ) ) {

        global $wpdb;
        $table_name = wcsl_get_notifications_table_name();
        $current_page_base_url = admin_url('admin.php?page=wcsl-notifications'); // Base URL for redirects

        $bulk_action = (isset($_POST['action']) && $_POST['action'] !== '-1') ? sanitize_key($_POST['action']) : sanitize_key($_POST['action2']);
        
        $message_text = '';
        $message_type = 'info';

        // Verify the nonce for bulk actions
        if ( ! isset( $_POST['_wcsl_bulk_nonce'] ) || ! wp_verify_nonce( $_POST['_wcsl_bulk_nonce'], 'wcsl_bulk_notifications_action' ) ) {
            $message_text = __( 'Bulk action security check failed. Please try again.', 'wp-client-support-ledger' );
            $message_type = 'error';
        } else {
            if ( isset( $_POST['notification_ids'] ) && is_array( $_POST['notification_ids'] ) && !empty($_POST['notification_ids']) ) {
                $notification_ids = array_map( 'intval', $_POST['notification_ids'] );
                $items_processed = 0;

                if ( !empty($notification_ids) ) {
                    $ids_placeholder = implode( ', ', array_fill( 0, count( $notification_ids ), '%d' ) );
                    $sql_query_args = $notification_ids; 

                    if ( 'bulk_mark_read' === $bulk_action ) {
                        $items_processed = $wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET is_read = 1 WHERE id IN ({$ids_placeholder})", $sql_query_args ) );
                        if (false !== $items_processed) {
                             $message_text = sprintf( _n( '%s notification marked as read.', '%s notifications marked as read.', $items_processed, 'wp-client-support-ledger' ), number_format_i18n( $items_processed ) );
                             $message_type = 'success';
                        } else { /* ... error handling ... */ }
                    } elseif ( 'bulk_mark_unread' === $bulk_action ) {
                        $items_processed = $wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET is_read = 0 WHERE id IN ({$ids_placeholder})", $sql_query_args ) );
                         if (false !== $items_processed) {
                            $message_text = sprintf( _n( '%s notification marked as unread.', '%s notifications marked as unread.', $items_processed, 'wp-client-support-ledger' ), number_format_i18n( $items_processed ) );
                            $message_type = 'success';
                        } else { /* ... error handling ... */ }
                    } elseif ( 'bulk_delete' === $bulk_action ) {
                        $items_processed = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id IN ({$ids_placeholder})", $sql_query_args ) );
                         if (false !== $items_processed) {
                            $message_text = sprintf( _n( '%s notification deleted.', '%s notifications deleted.', $items_processed, 'wp-client-support-ledger' ), number_format_i18n( $items_processed ) );
                            $message_type = 'success';
                        } else { /* ... error handling ... */ }
                    } else {
                        $message_text = __('Invalid bulk action selected.', 'wp-client-support-ledger');
                        $message_type = 'warning';
                    }
                    if (false === $items_processed && $bulk_action !== '-1' && $bulk_action !== '') {
                         $message_text = __('An error occurred during the bulk action.', 'wp-client-support-ledger');
                         $message_type = 'error';
                    }
                }
            } else { 
                 $message_text = __('No notifications selected for the bulk action.', 'wp-client-support-ledger');
                 $message_type = 'warning';
            }
        }
        
        $redirect_args = array();
        if (!empty($message_text)) {
            $redirect_args['wcsl_admin_notice'] = urlencode($message_text);
            $redirect_args['notice_type'] = $message_type;
        }
        if (isset($_REQUEST['paged']) && intval($_REQUEST['paged']) > 0) {
            $redirect_args['paged'] = intval($_REQUEST['paged']);
        }
        wp_safe_redirect( add_query_arg( $redirect_args, $current_page_base_url ) );
        exit;
    }
}
add_action( 'admin_init', 'wcsl_handle_bulk_notification_actions' );


/**
 * Handles single item GET actions (Mark Read/Unread) for notifications.
 * Hooked to an early action like admin_init or load-{$page_hook}.
 */
function wcsl_handle_single_notification_get_actions() {
    // ... (This function for non-AJAX Mark Read/Unread remains AS IS from your last working version)
    // It should NOT handle 'delete' if delete is AJAX.
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'wcsl-notifications' &&
         isset( $_GET['wcsl_notification_action'] ) && 
         isset( $_GET['notification_id'] ) && 
         isset( $_GET['_wpnonce'] ) ) {

        if ( (isset($_POST['action']) && $_POST['action'] !== '-1') || (isset($_POST['action2']) && $_POST['action2'] !== '-1') ) {
            return; 
        }
        global $wpdb;
        $table_name = wcsl_get_notifications_table_name();
        $current_page_base_url = admin_url('admin.php?page=wcsl-notifications');
        $action          = sanitize_key( $_GET['wcsl_notification_action'] );
        $notification_id = intval( $_GET['notification_id'] );
        $nonce           = sanitize_text_field( $_GET['_wpnonce'] );
        $message_text    = '';
        $message_type    = 'info';

        if ( $notification_id > 0 && wp_verify_nonce( $nonce, 'wcsl_manage_notification_' . $notification_id ) ) {
            $result = false;
            if ( 'mark_read' === $action ) {
                $result = $wpdb->update( $table_name, array( 'is_read' => 1 ), array( 'id' => $notification_id ), array('%d'), array('%d') );
                $message_text = (false !== $result) ? __( 'Notification marked as read.', 'wp-client-support-ledger' ) : __('Error marking as read.', 'wp-client-support-ledger');
                $message_type = (false !== $result) ? 'success' : 'error';
            } elseif ( 'mark_unread' === $action ) {
                $result = $wpdb->update( $table_name, array( 'is_read' => 0 ), array( 'id' => $notification_id ), array('%d'), array('%d') );
                $message_text = (false !== $result) ? __( 'Notification marked as unread.', 'wp-client-support-ledger' ) : __('Error marking as unread.', 'wp-client-support-ledger');
                $message_type = (false !== $result) ? 'success' : 'error';
            } 
            // 'delete' is NOT handled here as it's AJAX
            else {
                $message_text = __('Invalid GET action for notification.', 'wp-client-support-ledger');
                $message_type = 'warning';
            }
        } else if ($notification_id > 0) { 
            $message_text = __( 'Security check failed (GET action). Please try again.', 'wp-client-support-ledger' );
            $message_type = 'error';
        } else {
             $message_text = __( 'Invalid notification ID for GET action.', 'wp-client-support-ledger' );
             $message_type = 'error';
        }
        $redirect_args = array();
        if (!empty($message_text)) {
            $redirect_args['wcsl_admin_notice'] = urlencode($message_text);
            $redirect_args['notice_type'] = $message_type;
        }
        if (isset($_GET['paged']) && intval($_GET['paged']) > 0) {
            $redirect_args['paged'] = intval($_GET['paged']);
        }
        wp_safe_redirect( add_query_arg( $redirect_args, $current_page_base_url ) );
        exit;
    }
}

add_action( 'admin_init', 'wcsl_handle_single_notification_get_actions' );


/**
 * Display callback for the Notifications page.
 */
function wcsl_notifications_page_display() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-client-support-ledger' ) );
    }

    global $wpdb;
    $table_name = wcsl_get_notifications_table_name(); 
    $current_page_base_url = admin_url('admin.php?page=wcsl-notifications');

    // --- Pagination Parameters ---
    $items_per_page = 20; 
    $current_paged_val = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset = ( $current_paged_val - 1 ) * $items_per_page;

    // <<< CORRECTION: Build a dynamic WHERE clause to show ONLY admin notifications >>>
    // For admins, we only want to see notifications where the user_id is 0.
    $where_clause = $wpdb->prepare( "WHERE user_id = %d", 0 );

    // --- Fetch Total Number of Notifications (for admins) ---
    $total_items_sql = "SELECT COUNT(id) FROM {$table_name} {$where_clause}";
    $total_items = $wpdb->get_var( $total_items_sql );

    // --- Fetch Notifications for the Current Page (for admins) ---
    $notifications_sql = $wpdb->prepare(
        "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
        $items_per_page, $offset
    );
    $notifications = $wpdb->get_results( $notifications_sql );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <?php // Admin notices will still be displayed here ?>

        <form id="wcsl-notifications-form" method="post" action="<?php echo esc_url( $current_page_base_url ); ?>">
            <?php wp_nonce_field( 'wcsl_bulk_notifications_action', '_wcsl_bulk_nonce' ); ?>
            <input type="hidden" name="page" value="wcsl-notifications" />
            <?php if ($current_paged_val > 1) : ?>
                <input type="hidden" name="paged" value="<?php echo esc_attr($current_paged_val); ?>" />
            <?php endif; ?>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action' ); ?></label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php esc_html_e( 'Bulk actions' ); ?></option>
                        <option value="bulk_mark_read"><?php esc_html_e( 'Mark Read', 'wp-client-support-ledger' ); ?></option>
                        <option value="bulk_mark_unread"><?php esc_html_e( 'Mark Unread', 'wp-client-support-ledger' ); ?></option>
                        <option value="bulk_delete"><?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply' ); ?>">
                </div>
                <br class="clear">
            </div>

            <table class="wp-list-table widefat fixed striped wcsl-notifications-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                        <th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Notification', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Type', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column" id="status_header_text"><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ( ! empty( $notifications ) ) : ?>
                        <?php foreach ( $notifications as $notification ) : ?>
                            <?php
                            $row_classes = ($notification->is_read == 0) ? 'wcsl-notification-unread' : 'wcsl-notification-read';
                            $get_action_base_url = add_query_arg( 'notification_id', $notification->id, $current_page_base_url );
                            if ($current_paged_val > 1) { $get_action_base_url = add_query_arg('paged', $current_paged_val, $get_action_base_url); }
                            $manage_get_nonce = wp_create_nonce('wcsl_manage_notification_' . $notification->id);
                            $ajax_delete_nonce = wp_create_nonce('wcsl_ajax_manage_notification_' . $notification->id);
                            ?>
                            <tr class="<?php echo esc_attr($row_classes); ?>" id="notification-<?php echo $notification->id; ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="notification_ids[]" id="cb-select-<?php echo esc_attr( $notification->id ); ?>" value="<?php echo esc_attr( $notification->id ); ?>">
                                </th>
                                <td class="column-primary has-row-actions" data-colname="<?php esc_attr_e( 'Notification', 'wp-client-support-ledger' ); ?>">
                                    <?php echo wp_kses_post( $notification->message ); ?>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details' ); ?></span></button>
                                    <div class="row-actions">
                                        <?php if ( $notification->is_read == 0 ) : ?>
                                            <span class="mark-read"><a href="<?php echo esc_url( add_query_arg( array('wcsl_notification_action' => 'mark_read', '_wpnonce' => $manage_get_nonce), $get_action_base_url ) ); ?>"><?php esc_html_e( 'Mark Read', 'wp-client-support-ledger' ); ?></a> |</span>
                                        <?php else : ?>
                                             <span class="mark-unread"><a href="<?php echo esc_url( add_query_arg( array('wcsl_notification_action' => 'mark_unread', '_wpnonce' => $manage_get_nonce), $get_action_base_url ) ); ?>"><?php esc_html_e( 'Mark Unread', 'wp-client-support-ledger' ); ?></a> |</span>
                                        <?php endif; ?>
                                        <span class="delete">
                                            <a href="#" class="wcsl-notification-action wcsl-delete-notification-ajax" 
                                               data-action="delete" data-nonce="<?php echo esc_attr($ajax_delete_nonce); ?>" data-notification-id="<?php echo esc_attr($notification->id); ?>">
                                                <?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></a>
                                        </span>
                                    </div>
                                </td>
                                <td data-colname="<?php esc_attr_e( 'Type', 'wp-client-support-ledger' ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $notification->type ) ) ); ?></td>
                                <td data-colname="<?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $notification->created_at ) ) ); ?></td>
                                <td data-colname="<?php esc_attr_e( 'Status', 'wp-client-support-ledger' ); ?>"><?php echo ( $notification->is_read == 0 ) ? '<strong>' . esc_html__( 'Unread', 'wp-client-support-ledger' ) . '</strong>' : esc_html__( 'Read', 'wp-client-support-ledger' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="no-items"><td class="colspanchange" colspan="5"><?php esc_html_e( 'No notifications found.', 'wp-client-support-ledger' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                   <tr>
                        <td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox"></td>
                        <th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Notification', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Type', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e( 'Select bulk action' ); ?></label>
                    <select name="action2" id="bulk-action-selector-bottom">
                         <option value="-1"><?php esc_html_e( 'Bulk actions' ); ?></option>
                        <option value="bulk_mark_read"><?php esc_html_e( 'Mark Read', 'wp-client-support-ledger' ); ?></option>
                        <option value="bulk_mark_unread"><?php esc_html_e( 'Mark Unread', 'wp-client-support-ledger' ); ?></option>
                        <option value="bulk_delete"><?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></option>
                    </select>
                    <input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e( 'Apply' ); ?>">
                </div>
                <?php
                if ( $total_items > $items_per_page ) {
                    $total_pages = ceil( $total_items / $items_per_page );
                    echo '<div class="tablenav-pages">';
                    $pagination_base_url_nav = remove_query_arg(array('wcsl_notification_action', 'notification_id', '_wpnonce', 'wcsl_admin_notice', 'notice_type'), $current_page_base_url);
                    echo paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%', $pagination_base_url_nav ),
                        'format'    => '',
                        'current'   => $current_paged_val,
                        'total'     => $total_pages,
                        'prev_text' => '«',
                        'next_text' => '»',
                    ) );
                    echo '</div>';
                }
                ?>
                <br class="clear">
            </div>
        </form>
    </div> <!-- .wrap -->
    <?php
}



// Enqueue admin scripts
function wcsl_admin_enqueue_scripts( $hook_suffix ) {
    // --- Determine the correct hook suffixes for your plugin pages ---
    $main_page_hook = 'toplevel_page_wcsl-main-menu'; 
    $notifications_page_hook = 'support-ledger_page_wcsl-notifications'; 
    $reports_page_hook       = 'support-ledger_page_wcsl-reports';       
    $settings_page_hook      = 'support-ledger_page_wcsl-settings-help'; 

    $add_task_hook = 'post-new.php';
    $edit_task_hook = 'post.php';

    // --- Flags for loading assets ---
    $load_admin_style = false;
    $load_notifications_js = false;
    $load_reports_js_and_chartjs = false;
    $load_settings_js = false;

    $wcsl_admin_pages = array( $main_page_hook, $notifications_page_hook, $reports_page_hook, $settings_page_hook );
    if ( in_array( $hook_suffix, $wcsl_admin_pages ) ) {
        $load_admin_style = true;
    }
    if ( $hook_suffix === $notifications_page_hook ) {
        $load_notifications_js = true;
    }
    if ( $hook_suffix === $reports_page_hook ) {
        $load_reports_js_and_chartjs = true;
    }
    if ( $hook_suffix === $settings_page_hook ) {
        $load_settings_js = true;
    }

    // --- Enqueue Scripts for Add/Edit Task Screen ---
    global $post;
    if ( ($hook_suffix == $add_task_hook && isset($_GET['post_type']) && $_GET['post_type'] == 'client_task') || 
         ($hook_suffix == $edit_task_hook && is_a($post, 'WP_Post') && $post->post_type == 'client_task') ) {
        
        wp_enqueue_media();
        wp_enqueue_script(
            'wcsl-admin-settings-js',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-settings.js',
            array( 'jquery', 'wp-color-picker' ),
            '1.0.3', // Incremented version
            true
        );

        wp_localize_script('wcsl-admin-settings-js', 'wcsl_task_edit_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wcsl_get_task_categories_nonce'),
            'post_id'  => $post ? $post->ID : 0
        ));
    }

    // --- Enqueue General Admin Style ---
    if ( $load_admin_style ) {
        wp_enqueue_style('wcsl-admin-style', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-style.css', array(), '1.0.7');
    }

    // --- Enqueue Notifications JS ---
    if ( $load_notifications_js ) {
        wp_enqueue_script('wcsl-admin-notifications-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-notifications.js', array( 'jquery' ), '1.0.0', true);
        wp_localize_script( 'wcsl-admin-notifications-js', 'wcsl_ajax_object', array('ajax_url' => admin_url( 'admin-ajax.php' ), 'delete_confirm_message' => esc_js(__( 'Are you sure you want to delete this notification?', 'wp-client-support-ledger' ))));
    }
    
    // --- Enqueue Settings Page JS ---
    if ( $load_settings_js ) {
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script('wcsl-admin-settings-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-settings.js', array( 'jquery', 'wp-color-picker' ), '1.0.3', true);
    }

    // --- Enqueue Reports JS and Chart.js ---
    if ( $load_reports_js_and_chartjs ) {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', true);
        wp_enqueue_script('wcsl-admin-reports-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-reports.js', array( 'jquery', 'chartjs' ), '1.0.4', true);

        $today_for_reports = current_time('Y-m-d');
        $default_start_for_reports = date('Y-m-d', strtotime('-29 days', strtotime($today_for_reports)));
        $report_start_date = isset( $_GET['start_date'] ) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date']) ? sanitize_text_field( $_GET['start_date'] ) : $default_start_for_reports;
        $report_end_date   = isset( $_GET['end_date'] ) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date']) ? sanitize_text_field( $_GET['end_date'] ) : $today_for_reports;
        if (strtotime($report_end_date) < strtotime($report_start_date)) { $report_end_date = $report_start_date; }

        $chart_data_hours_per_client = array('labels' => array(), 'data' => array(), 'error' => '', 'current_start_date' => $report_start_date, 'current_end_date' => $report_end_date);
        if ( function_exists('wcsl_get_hours_per_client_for_period') ) {
            $fetched_data = wcsl_get_hours_per_client_for_period( $report_start_date, $report_end_date );
            if ( !empty($fetched_data['labels']) && !empty($fetched_data['data']) ) {
                $chart_data_hours_per_client['labels'] = $fetched_data['labels'];
                $chart_data_hours_per_client['data']   = $fetched_data['data'];
            } else {
                $chart_data_hours_per_client['error'] = sprintf(esc_html__('No data for Hours Per Client in period: %s to %s.', 'wp-client-support-ledger'), esc_html( date_i18n( get_option('date_format'), strtotime($report_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($report_end_date) ) ));
            }
        } else { $chart_data_hours_per_client['error'] = __('Error: wcsl_get_hours_per_client_for_period() is missing.', 'wp-client-support-ledger'); }
        
        $total_billable_data = array( 'value_minutes' => 0, 'value_string'  => '0m', 'error' => '' );
        if ( function_exists('wcsl_get_total_billable_minutes_for_period') && function_exists('wcsl_format_minutes_to_time_string') ) {
            $billable_minutes = wcsl_get_total_billable_minutes_for_period( $report_start_date, $report_end_date );
            $total_billable_data['value_minutes'] = $billable_minutes;
            $total_billable_data['value_string']  = wcsl_format_minutes_to_time_string( $billable_minutes );
        } else { $total_billable_data['error'] = __('Error: Helper functions for total billable hours are missing.', 'wp-client-support-ledger'); }
        
        $chart_data_billable_per_client = array( 'labels' => array(), 'data' => array(), 'error' => '' );
        if ( function_exists('wcsl_get_billable_summary_per_client_for_period') ) {
            $fetched_billable_clients = wcsl_get_billable_summary_per_client_for_period( $report_start_date, $report_end_date );
            if ( !empty($fetched_billable_clients['labels']) && !empty($fetched_billable_clients['data']) ) {
                $chart_data_billable_per_client['labels'] = $fetched_billable_clients['labels'];
                $chart_data_billable_per_client['data']   = $fetched_billable_clients['data'];
            } else {
                $chart_data_billable_per_client['error'] = sprintf(esc_html__('No clients with billable hours in period: %s to %s.', 'wp-client-support-ledger'), esc_html( date_i18n( get_option('date_format'), strtotime($report_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($report_end_date) ) ));
            }
        } else { $chart_data_billable_per_client['error'] = __('Error: wcsl_get_billable_summary_per_client_for_period() is missing.', 'wp-client-support-ledger'); }
        
        $chart_data_hours_by_employee = array( 'labels' => array(), 'data' => array(), 'error' => '' );
        if ( function_exists('wcsl_get_hours_by_employee_for_period') ) {
            $fetched_employee_hours = wcsl_get_hours_by_employee_for_period( $report_start_date, $report_end_date );
            if ( !empty($fetched_employee_hours['labels']) && !empty($fetched_employee_hours['data']) ) {
                $chart_data_hours_by_employee['labels'] = $fetched_employee_hours['labels'];
                $chart_data_hours_by_employee['data']   = $fetched_employee_hours['data'];
            } else {
                 $chart_data_hours_by_employee['error'] = sprintf(esc_html__('No employee hours logged in period: %s to %s.', 'wp-client-support-ledger'), esc_html( date_i18n( get_option('date_format'), strtotime($report_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($report_end_date) ) ));
            }
        } else { $chart_data_hours_by_employee['error'] = __('Error: wcsl_get_hours_by_employee_for_period() is missing.', 'wp-client-support-ledger'); }

        $chart_data_billable_trend = array( 'labels' => array(), 'data' => array(), 'error' => '' );
        if ( function_exists('wcsl_get_billable_hours_for_past_months') ) {
            $fetched_trend_data = wcsl_get_billable_hours_for_past_months( 12 );
            if ( !empty($fetched_trend_data['labels']) && !empty($fetched_trend_data['data']) ) {
                $chart_data_billable_trend['labels'] = $fetched_trend_data['labels'];
                $chart_data_billable_trend['data']   = $fetched_trend_data['data'];
            } else {
                $chart_data_billable_trend['error'] = __('No billable hours trend data found for the past 12 months.', 'wp-client-support-ledger');
            }
        } else { $chart_data_billable_trend['error'] = __('Error: The trend data function is missing.', 'wp-client-support-ledger'); }
        
        // <<< NEW: Data for the Task Analysis charts >>>
        $chart_data_support_tasks = array( 'labels' => array(), 'data' => array(), 'error' => '' );
        if ( function_exists('wcsl_get_task_count_by_category') ) { $chart_data_support_tasks = wcsl_get_task_count_by_category('support', $report_start_date, $report_end_date); }

        $chart_data_fixing_tasks = array( 'labels' => array(), 'data' => array(), 'error' => '' );
        if ( function_exists('wcsl_get_task_count_by_category') ) { $chart_data_fixing_tasks = wcsl_get_task_count_by_category('fixing', $report_start_date, $report_end_date); }

        wp_localize_script('wcsl-admin-reports-js', 'wcsl_report_data_obj', array(
                'hoursPerClient' => $chart_data_hours_per_client,
                'totalBillableHours' => $total_billable_data,
                'billablePerClient' => $chart_data_billable_per_client,
                'hoursByEmployee' => $chart_data_hours_by_employee,
                'billableTrend' => $chart_data_billable_trend,
                'supportTaskAnalysis' => $chart_data_support_tasks,
                'fixingTaskAnalysis'  => $chart_data_fixing_tasks,
                'chartColors' => array('rgba(57, 97, 140, 0.7)', 'rgba(91, 192, 222, 0.7)', 'rgba(240, 173, 78, 0.7)', 'rgba(92, 184, 92, 0.7)', 'rgba(217, 83, 79, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 193, 7, 0.7)', 'rgba(52, 73, 94, 0.7)', 'rgba(26, 188, 156, 0.7)', 'rgba(231, 76, 60, 0.7)'),
                'chartBorderColors' => array('rgb(57, 97, 140)', 'rgb(91, 192, 222)', 'rgb(240, 173, 78)', 'rgb(92, 184, 92)', 'rgb(217, 83, 79)', 'rgb(153, 102, 255)', 'rgb(255, 193, 7)', 'rgb(52, 73, 94)', 'rgb(26, 188, 156)', 'rgb(231, 76, 60)'),
                'report_start_date_formatted' => date_i18n(get_option('date_format'), strtotime($report_start_date)),
                'report_end_date_formatted' => date_i18n(get_option('date_format'), strtotime($report_end_date)),
                'i18n' => array(
                    'hoursSpentByClientTitle' => esc_js(__( 'Hours Spent by Client (%s - %s)', 'wp-client-support-ledger' )),
                    'billableHoursByClientTitle'=> esc_js(__( 'Total Billable Hours by Client (%s - %s)', 'wp-client-support-ledger' )),
                    'hoursByEmployeeTitle' => esc_js(__( 'Hours Logged by Employee (%s - %s)', 'wp-client-support-ledger' )),
                    'hoursLabel' => esc_js(__( 'Hours', 'wp-client-support-ledger' )),
                    'tasksLabel' => esc_js(__( 'Number of Tasks', 'wp-client-support-ledger' ))
                )
        ));
    }
}
add_action( 'admin_enqueue_scripts', 'wcsl_admin_enqueue_scripts' );

// Handler for generating the print page (hooked to admin-post.php)
function wcsl_display_print_report_page_handler() {
    if ( ! isset( $_GET['month'], $_GET['year'], $_GET['_wpnonce'], $_GET['nonce_action'] ) ) {
        wp_die( __( 'Missing parameters for print report.', 'wp-client-support-ledger' ) );
    }

    $month = intval( $_GET['month'] );
    $year  = intval( $_GET['year'] );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] );
    $nonce_action = sanitize_text_field( $_GET['nonce_action'] );

    if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
        wp_die( __( 'Security check failed for print view (handler).', 'wp-client-support-ledger' ) );
    }

       // New, flexible permission check for Admin, Employee, or Client
    if ( ! is_user_logged_in() ) {
        wp_die( __( 'You must be logged in to perform this action.', 'wp-client-support-ledger' ) );
    }
    
    $user = wp_get_current_user();
    $allowed_roles = array('administrator', 'wcsl_employee', 'wcsl_client');
    // array_intersect finds common values between two arrays. If it's not empty, the user has at least one of the allowed roles.
    if ( empty( array_intersect( $allowed_roles, $user->roles ) ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }
    wcsl_display_print_report_page( $month, $year );
}
add_action( 'admin_post_wcsl_generate_print_page', 'wcsl_display_print_report_page_handler' );


/**
 * Displays a print-friendly HTML page for the monthly report.
 */
function wcsl_display_print_report_page( $current_month, $current_year, $client_id_to_print = null ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $current_month );

    // Determine context (frontend or admin)
    // The handler (wcsl_display_print_report_page_handler) should ideally pass this
    // but we can also check $_GET directly if it's reliable here.
    // For robustness, let's assume the handler passes it if it's available.
    // We added 'context' => 'frontend' to the print_url_args in the shortcode.
    $context = isset( $_GET['context'] ) ? sanitize_key( $_GET['context'] ) : 'admin'; // Default to admin

    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $current_month, 1, $current_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $current_month + 1, 0, $current_year ) );

    // -- Client Summary Data --
    $clients_summary_data = array();
    if ( function_exists('wcsl_get_client_summary_data') ) {
        $clients_summary_data = wcsl_get_client_summary_data( $current_month, $current_year, $client_id_to_print );
    }

    // -- Detailed Task Log Data (Conditional) --
    $detailed_tasks_data = array();
    // *** ONLY FETCH AND DISPLAY DETAILED TASKS IF NOT FROM FRONTEND SHORTCODE ***
    if ( 'frontend' !== $context ) { // Or however you want to distinguish, e.g. a new param like 'include_details=false'
        $all_tasks_args_print = array(
            'post_type'      => 'client_task',
            'posts_per_page' => -1,
            'meta_key'       => '_wcsl_task_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_wcsl_task_date',
                    'value'   => array( $first_day_of_month, $last_day_of_month ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE'
                )
            )
        );

        if ( ! is_null( $client_id_to_print ) && $client_id_to_print > 0 ) {
            $all_tasks_args_print['meta_query'][] = array(
                'key'     => '_wcsl_related_client_id',
                'value'   => $client_id_to_print,
                'compare' => '=',
            );
        }

        $all_tasks_query_print = new WP_Query( $all_tasks_args_print );
        if ( $all_tasks_query_print->have_posts() ) {
            while ( $all_tasks_query_print->have_posts() ) : $all_tasks_query_print->the_post();
                $task_id = get_the_ID();
                $related_client_id = get_post_meta( $task_id, '_wcsl_related_client_id', true );
                $detailed_tasks_data[] = array(
                    'date' => get_post_meta( $task_id, '_wcsl_task_date', true ),
                    'client_name' => $related_client_id ? get_the_title( $related_client_id ) : 'N/A',
                    'title' => get_the_title(),
                    'link' => get_post_meta( $task_id, '_wcsl_task_link', true ),
                    'hours_spent_str' => get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true ),
                    'status' => ucfirst( str_replace('-', ' ', get_post_meta( $task_id, '_wcsl_task_status', true ))),
                    'employee' => get_post_meta( $task_id, '_wcsl_employee_name', true ) // Assuming you want the custom field
                                  ?: (get_the_author_meta( 'display_name', get_post_field( 'post_author', $task_id ) ) ?: 'N/A'), // Fallback chain
                    'note' => get_post_meta( $task_id, '_wcsl_task_note', true )

                );
            endwhile;
            wp_reset_postdata();
        }
    } // *** END CONDITIONAL FETCH FOR DETAILED TASKS ***

    // --- Output Print-Specific HTML ---
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php printf( esc_html__( 'Support Report - %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ); ?></title>
        <link rel="stylesheet" id="wcsl-print-style" href="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/print-style.css' ); ?>" type="text/css" media="all">
    </head>
    <body class="wcsl-print-body">
        <div class="print-report-container">
            <div class="report-header">
                <h1><?php bloginfo('name'); ?> - <?php esc_html_e( 'Client Support Ledger', 'wp-client-support-ledger' ); ?></h1>
                <h2><?php printf( esc_html__( 'Monthly Report: %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ); ?></h2>
            </div>

            <h3><?php esc_html_e( 'Client Summary', 'wp-client-support-ledger' ); ?></h3>
            <?php if ( ! empty( $clients_summary_data ) ) : ?>
            <table>
                <thead><tr><th><?php esc_html_e('Client', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Contracted', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Spent', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Billable', 'wp-client-support-ledger'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ( $clients_summary_data as $client ) : ?>
                    <tr>
                        <td><?php echo esc_html( $client['name'] ); ?></td>
                        <td><?php echo esc_html( $client['contracted_str'] ); ?></td>
                        <td><?php echo esc_html( $client['total_spent_str'] ); ?></td>
                        <td><?php echo esc_html( $client['billable_hours_str'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: echo '<p>' . esc_html__('No client summary data for this period.', 'wp-client-support-ledger') . '</p>'; endif; ?>

            <?php // *** CONDITIONALLY DISPLAY DETAILED TASK LOG HTML *** ?>
            <?php if ( 'frontend' !== $context ) : ?>
                <h3><?php esc_html_e( 'Detailed Task Log', 'wp-client-support-ledger' ); ?></h3>
                <?php if ( ! empty( $detailed_tasks_data ) ) : ?>
                <table>
                    <thead><tr><th><?php esc_html_e('Date', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Client', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Task', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Link', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Hours', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Status', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Employee', 'wp-client-support-ledger'); ?></th><th><?php esc_html_e('Note', 'wp-client-support-ledger'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ( $detailed_tasks_data as $task ) : ?>
                        <tr>
                            <td><?php echo esc_html( $task['date'] ); ?></td>
                            <td><?php echo esc_html( $task['client_name'] ); ?></td>
                            <td><?php echo esc_html( $task['title'] ); ?></td>
                            <td>
                                <?php if ( ! empty( $task['link'] ) ) : ?>
                                    <a href="<?php echo esc_url( $task['link'] ); ?>"><?php echo esc_html__( 'View', 'wp-client-support-ledger' ); ?></a>
                                <?php else: echo esc_html__( 'N/A', 'wp-client-support-ledger' ); endif; ?>
                            </td>
                            <td><?php echo esc_html( !empty($task['hours_spent_str']) ? $task['hours_spent_str'] : '0m' ); ?></td>
                            <td><?php echo esc_html( $task['status'] ); ?></td>
                            <td><?php echo esc_html( $task['employee'] ); ?></td>
                            <td><?php echo nl2br( esc_html( $task['note'] ) ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: echo '<p>' . esc_html__('No detailed tasks for this period.', 'wp-client-support-ledger') . '</p>'; endif; ?>
            <?php endif; // End conditional display for detailed task log ?>

        </div> <!-- .print-report-container -->
        <script type="text/javascript">
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}


/**
 * Handles the request to delete all tasks for a specific month and year.
 */
function wcsl_process_delete_month_tasks() {
    // 1. Security and Parameter Checks
    if ( ! isset( $_GET['month'] ) || ! isset( $_GET['year'] ) || ! isset( $_GET['_wpnonce'] ) ) {
        wp_die( __( 'Missing required parameters for deletion.', 'wp-client-support-ledger' ) );
    }

    $month = intval( $_GET['month'] );
    $year  = intval( $_GET['year'] );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] );

    // Verify the nonce
    // *** FIX: Ensure the nonce action string matches what was used in wp_create_nonce ***
    if ( ! wp_verify_nonce( $nonce, 'wcsl_delete_month_tasks_action_' . $year . '_' . $month ) ) {
        wp_die( __( 'Security check failed for deletion.', 'wp-client-support-ledger' ) );
    }

    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to delete these tasks.', 'wp-client-support-ledger' ) );
    }

    // 2. Fetch Tasks to Delete
    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $month, 1, $year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $month + 1, 0, $year ) );

    $tasks_to_delete_args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => '_wcsl_task_date',
                'value'   => array( $first_day_of_month, $last_day_of_month ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ),
        ),
    );
    $task_ids_to_delete = get_posts( $tasks_to_delete_args );

    $deleted_count = 0;
    if ( ! empty( $task_ids_to_delete ) ) {
        foreach ( $task_ids_to_delete as $task_id ) {
            $delete_result = wp_delete_post( $task_id, true );
            if ( $delete_result ) {
                $deleted_count++;
            }
        }
    }

    // 3. Redirect back with a notice
    $redirect_url = admin_url( 'admin.php?page=wcsl-main-menu' );

    if ( $deleted_count > 0 ) {
        $redirect_url = add_query_arg( array(
            'wcsl_notice' => 'tasks_deleted',
            'deleted_count' => $deleted_count,
            'month_deleted' => $month,
            'year_deleted' => $year
        ), $redirect_url );
    } else if ( empty( $task_ids_to_delete) ) {
         $redirect_url = add_query_arg( array(
            'wcsl_notice' => 'no_tasks_found_to_delete',
            'month_deleted' => $month,
            'year_deleted' => $year
        ), $redirect_url );
    }
    else {
        $redirect_url = add_query_arg( 'wcsl_error', 'delete_failed', $redirect_url );
    }

    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_wcsl_handle_delete_month_tasks', 'wcsl_process_delete_month_tasks' );


/**
 * Display admin notices for plugin actions.
 */
function wcsl_admin_notices() {
    if ( ! isset( $_GET['page'] ) || 'wcsl-main-menu' !== $_GET['page'] ) {
        return;
    }

    global $wp_locale;

    if ( isset( $_GET['wcsl_notice'] ) ) {
        if ( $_GET['wcsl_notice'] === 'tasks_deleted' && isset( $_GET['deleted_count'] ) ) {
            $count = intval( $_GET['deleted_count'] );
            $month = isset($_GET['month_deleted']) ? intval($_GET['month_deleted']) : 0;
            $year = isset($_GET['year_deleted']) ? intval($_GET['year_deleted']) : 0;
            $month_name = $month ? $wp_locale->get_month($month) : '';

            $message = sprintf(
                _n(
                    '%d task for %s %s has been permanently deleted.',
                    '%d tasks for %s %s have been permanently deleted.',
                    $count,
                    'wp-client-support-ledger'
                ),
                $count,
                esc_html($month_name),
                esc_html($year)
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
        }
        if ( $_GET['wcsl_notice'] === 'no_tasks_found_to_delete' ) {
             $month = isset($_GET['month_deleted']) ? intval($_GET['month_deleted']) : 0;
            $year = isset($_GET['year_deleted']) ? intval($_GET['year_deleted']) : 0;
            $month_name = $month ? $wp_locale->get_month($month) : '';
            $message = sprintf(
                esc_html__( 'No tasks were found for %s %s to delete.', 'wp-client-support-ledger' ),
                esc_html($month_name),
                esc_html($year)
            );
             echo '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>';
        }
    } elseif ( isset( $_GET['wcsl_error'] ) && $_GET['wcsl_error'] === 'delete_failed' ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'An error occurred while trying to delete tasks.', 'wp-client-support-ledger' ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'wcsl_admin_notices' );




/**
 * Handles the request to generate a single invoice PDF.
 * Hooked to admin-post.php. This function performs security checks.
 */
function wcsl_handle_generate_single_invoice() {
    // 1. Security and Parameter Checks (unchanged)
    if ( ! isset( $_GET['client_id'], $_GET['month'], $_GET['year'], $_GET['_wpnonce'] ) ) {
        wp_die(__( 'Invalid invoice request: Missing parameters.', 'wp-client-support-ledger' ));
    }
    
    $client_id = intval( $_GET['client_id'] );
    $month     = intval( $_GET['month'] );
    $year      = intval( $_GET['year'] );
    $nonce     = sanitize_text_field( $_GET['_wpnonce'] );

    if ( ! wp_verify_nonce( $nonce, 'wcsl_generate_invoice_' . $client_id . '_' . $year . '_' . $month ) ) {
        wp_die( __( 'Security check failed. Please go back and try again.', 'wp-client-support-ledger' ) );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to generate this invoice.', 'wp-client-support-ledger' ) );
    }

    // 2. Get all data using our helper function (unchanged)
    if ( ! function_exists('wcsl_get_data_for_invoice') ) {
        wp_die(__( 'Error: The required invoice data helper function is missing.', 'wp-client-support-ledger' ));
    }
    $invoice_data = wcsl_get_data_for_invoice( $client_id, $month, $year );

    if ( false === $invoice_data ) {
        wp_die(__( 'Could not generate invoice. The specified client may not exist.', 'wp-client-support-ledger' ));
    }
    
    // <<< NEW: Create or update the invoice record in our custom database table >>>
    if ( function_exists('wcsl_create_or_update_invoice_record') ) {
        $record_data = array(
            'client_id'      => $client_id,
            'month'          => $month,
            'year'           => $year,
            'invoice_number' => $invoice_data['invoice']['number'],
            'status'         => 'invoiced',
            'amount'         => $invoice_data['totals']['grand_total'],
            'generated_at'   => current_time('mysql'),
        );
        wcsl_create_or_update_invoice_record( $record_data );
    }
    
    // 3. Call the display function to render the PDF's HTML (unchanged)
    wcsl_display_invoice_pdf_page( $invoice_data );
}
add_action( 'admin_post_wcsl_generate_single_invoice', 'wcsl_handle_generate_single_invoice' );


/**
 * Renders the clean, print-friendly HTML page for a single invoice.
 * This function is called by the handler above and ends with exit;.
 *
 * @param array $invoice_data The complete data array from wcsl_get_data_for_invoice().
 */
function wcsl_display_invoice_pdf_page( $invoice_data ) {
    // Extract data for easier access in the template
    $company = $invoice_data['company'];
    $client  = $invoice_data['client'];
    $invoice = $invoice_data['invoice'];
    $line_items = $invoice_data['line_items'];
    $totals  = $invoice_data['totals'];
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php printf( esc_html__( 'Invoice #%1$s for %2$s', 'wp-client-support-ledger' ), esc_html($invoice['number']), esc_html($client['name']) ); ?></title>
        <link rel="stylesheet" id="wcsl-invoice-style" href="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/invoice-style.css' ); ?>" type="text/css" media="all">
    </head>
    <body>
        <div class="invoice-box">
            <header class="invoice-header">
                <div class="logo">
                    <?php if ( ! empty( $company['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $company['logo_url'] ); ?>" alt="<?php echo esc_attr( $company['name'] ); ?> Logo">
                    <?php else: ?>
                        <h1><?php echo esc_html( $company['name'] ); ?></h1>
                    <?php endif; ?>
                </div>
                <div class="company-details">
                    <strong><?php echo esc_html( $company['name'] ); ?></strong><br>
                    <?php echo nl2br( esc_html( $company['address'] ) ); ?><br>
                    <?php echo esc_html( $company['email'] ); ?><br>
                    <?php echo esc_html( $company['phone'] ); ?>
                </div>
            </header>

            <section class="invoice-meta-details">
                <div class="billing-to">
                    <strong><?php esc_html_e( 'Bill To:', 'wp-client-support-ledger' ); ?></strong><br>
                    <?php echo esc_html( $client['name'] ); ?><br>
                    <?php echo nl2br( esc_html( $client['billing_address'] ) ); ?>
                </div>
                <div class="invoice-info">
                    <table>
                        <tr>
                            <td><?php esc_html_e( 'Invoice #:', 'wp-client-support-ledger' ); ?></td>
                            <td><?php echo esc_html( $invoice['number'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Invoice Date:', 'wp-client-support-ledger' ); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option('date_format'), strtotime($invoice['date']) ) ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Due Date:', 'wp-client-support-ledger' ); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option('date_format'), strtotime($invoice['due_date']) ) ); ?></td>
                        </tr>
                    </table>
                </div>
            </section>

            <table class="line-items-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Description', 'wp-client-support-ledger' ); ?></th>
                        <th style="text-align: center;"><?php esc_html_e( 'Quantity (Hours)', 'wp-client-support-ledger' ); ?></th>
                        <th style="text-align: right;"><?php esc_html_e( 'Unit Price', 'wp-client-support-ledger' ); ?></th>
                        <th style="text-align: right;"><?php esc_html_e( 'Amount', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $line_items as $item ) : ?>
                        <tr>
                            <td><?php echo esc_html( $item['description'] ); ?></td>
                            <td style="text-align: center;"><?php echo esc_html( number_format_i18n( $item['quantity'], 2 ) ); ?></td>
                            <td style="text-align: right;"><?php echo esc_html( $totals['currency'] . number_format_i18n( $item['unit_price'], 2 ) ); ?></td>
                            <td style="text-align: right;"><?php echo esc_html( $totals['currency'] . number_format_i18n( $item['amount'], 2 ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"></td>
                        <td><?php esc_html_e( 'Subtotal', 'wp-client-support-ledger' ); ?></td>
                        <td><?php echo esc_html( $totals['currency'] . number_format_i18n( $totals['subtotal'], 2 ) ); ?></td>
                    </tr>
                    <?php if ( $totals['tax_amount'] > 0 ) : ?>
                    <tr>
                        <td colspan="2"></td>
                        <td><?php printf( esc_html__( 'Tax (%s%%)', 'wp-client-support-ledger' ), esc_html( $client['tax_rate_percent'] ) ); ?></td>
                        <td><?php echo esc_html( $totals['currency'] . number_format_i18n( $totals['tax_amount'], 2 ) ); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="grand-total">
                        <td colspan="2"></td>
                        <td><?php esc_html_e( 'Total Due', 'wp-client-support-ledger' ); ?></td>
                        <td><?php echo esc_html( $totals['currency'] . number_format_i18n( $totals['grand_total'], 2 ) ); ?></td>
                    </tr>
                </tfoot>
            </table>

            <?php if ( ! empty( $company['footer_text'] ) ) : ?>
                <footer class="invoice-footer">
                    <?php echo wp_kses_post( nl2br( $company['footer_text'] ) ); ?>
                </footer>
            <?php endif; ?>
        </div>

         <?php // ***** ADD THIS SCRIPT TO TRIGGER PRINT DIALOG ***** ?>
        <script type="text/javascript">
            window.onload = function() {
                // A short delay can sometimes help ensure all CSS and images are rendered before printing.
                setTimeout(function() {
                    window.print();
                }, 500); // 500ms delay
            }
        </script>
        <?php // ***** END SCRIPT ***** ?>

    </body>
    </html>
    <?php
    exit; // CRITICAL: Stop WordPress from outputting anything else.
}


/**
 * Handler for generating the print page for the BILLABLE CLIENTS LIST.
 * Hooked to admin-post.php. This function performs security checks.
 */
function wcsl_handle_print_billing_list_page() {
    // 1. Security and Parameter Checks
    if ( ! isset( $_GET['month'], $_GET['year'], $_GET['_wpnonce'] ) ) {
        wp_die(__( 'Invalid request: Missing parameters.', 'wp-client-support-ledger' ));
    }

    $month = intval( $_GET['month'] );
    $year  = intval( $_GET['year'] );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] );

    // Verify the nonce against the action we used when creating the button link
    if ( ! wp_verify_nonce( $nonce, 'wcsl_print_billing_list_' . $year . '_' . $month ) ) {
        wp_die( __( 'Security check failed. Please try again.', 'wp-client-support-ledger' ) );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-client-support-ledger' ) );
    }

    // 2. Call the new display function
    wcsl_display_billing_list_print_page( $month, $year );
}
add_action( 'admin_post_wcsl_print_billing_list_page', 'wcsl_handle_print_billing_list_page' );


/**
 * Renders the clean, print-friendly HTML page containing the table of billable clients for a month.
 * This function is called by the handler above and ends with exit;.
 *
 * @param int $current_month The month to display.
 * @param int $current_year The year to display.
 */
function wcsl_display_billing_list_print_page( $current_month, $current_year ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $current_month );

    // --- Fetch ALL billable clients for the PDF (no pagination) ---
    $billing_data = array();
    if ( function_exists('wcsl_get_billable_clients_for_month') ) {
        // Call helper with -1 for per_page to get all results
        $billing_data_full = wcsl_get_billable_clients_for_month( $current_month, $current_year, -1, 1 );
        $billing_data = $billing_data_full['clients']; // We only need the clients array
    }
    
    // --- Output Print-Specific HTML ---
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php printf( esc_html__( 'Billable Clients - %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ); ?></title>
        <link rel="stylesheet" id="wcsl-print-style" href="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/print-style.css' ); ?>" type="text/css" media="all">
    </head>
    <body class="wcsl-print-body">
        <div class="print-report-container">
            <div class="report-header">
                <h1><?php bloginfo('name'); ?> - <?php esc_html_e( 'Client Support Ledger', 'wp-client-support-ledger' ); ?></h1>
                <h2><?php printf( esc_html__( 'Billable Clients: %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ); ?></h2>
            </div>

            <h3><?php esc_html_e( 'Billable Client Summary', 'wp-client-support-ledger' ); ?></h3>
            <?php if ( ! empty( $billing_data ) ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Contracted Hours', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Total Hours Spent', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Billable Hours', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $billing_data as $summary_item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $summary_item['name'] ); ?></td>
                        <td><?php echo esc_html( $summary_item['contracted_str'] ); ?></td>
                        <td><?php echo esc_html( $summary_item['total_spent_str'] ); ?></td>
                        <td><strong><?php echo esc_html( $summary_item['billable_hours_str'] ); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: echo '<p>' . esc_html__('No clients with billable hours found for this period.', 'wp-client-support-ledger') . '</p>'; endif; ?>

        </div> <!-- .print-report-container -->

        <script type="text/javascript">
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit; // CRITICAL: Stop WordPress from rendering anything else.
}

/**
 * Handles the request to export the Detailed Task Log to a CSV file.
 * Hooked to admin-post.php.
 */

function wcsl_handle_export_tasks_csv() {
    // 1. Security and Parameter Checks
    if ( ! isset( $_GET['_wpnonce'], $_GET['month'], $_GET['year'] ) ) {
        wp_die( 'Invalid export request.' );
    }
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcsl_export_tasks_csv_nonce' ) ) {
        wp_die( 'Security check failed.' );
    }
   // New, flexible permission check for Admin, Employee, or Client
    if ( ! is_user_logged_in() ) {
        wp_die( __( 'You must be logged in to perform this action.', 'wp-client-support-ledger' ) );
    }
    
    $user = wp_get_current_user();
    $allowed_roles = array('administrator', 'wcsl_employee', 'wcsl_client');
    // array_intersect finds common values between two arrays. If it's not empty, the user has at least one of the allowed roles.
    if ( empty( array_intersect( $allowed_roles, $user->roles ) ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    // 2. Sanitize all incoming filter parameters
    $current_month = intval( $_GET['month'] );
    $current_year  = intval( $_GET['year'] );
    $search_term_tasks   = isset( $_GET['wcsl_search_tasks'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsl_search_tasks'] ) ) : '';
    $filter_client_id    = isset( $_GET['filter_client'] ) ? intval( $_GET['filter_client'] ) : 0;
    $filter_employee_id  = isset( $_GET['filter_employee'] ) ? intval( $_GET['filter_employee'] ) : 0;
    $filter_status       = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : '';
    $filter_task_type    = isset( $_GET['filter_task_type'] ) ? sanitize_key( $_GET['filter_task_type'] ) : '';
    
    // 3. Build the query to get all results
    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $current_month, 1, $current_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $current_month + 1, 0, $current_year ) );
    $meta_query = array('relation' => 'AND', array('key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE' ));
    if ( $filter_client_id > 0 ) { $meta_query[] = array('key' => '_wcsl_related_client_id', 'value' => $filter_client_id); }
    if ( $filter_employee_id > 0 ) { $meta_query[] = array('key' => '_wcsl_assigned_employee_id', 'value' => $filter_employee_id); }
    if ( !empty($filter_status) ) { $meta_query[] = array('key' => '_wcsl_task_status', 'value' => $filter_status); }
    if ( !empty($filter_task_type) ) { $meta_query[] = array('key' => '_wcsl_task_type', 'value' => $filter_task_type); }
    $all_tasks_args = array('post_type' => 'client_task', 'posts_per_page' => -1, 'post_status' => 'publish', 's' => $search_term_tasks, 'meta_query' => $meta_query);
    $all_tasks_query = new WP_Query( $all_tasks_args );

    // 4. Set HTTP headers for CSV download
    $month_name = date( 'F', mktime(0, 0, 0, $current_month, 10) );
    $filename = 'tasks-export-' . sanitize_title( $month_name ) . '-' . $current_year . '.csv';
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

    // 5. Open output stream and write CSV data
    $output = fopen( 'php://output', 'w' );
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    $header_row = array('Date', 'Client', 'Task Title', 'Task Type', 'Task Link', 'Attachment URL', 'Hours Spent', 'Status', 'Employee', 'Note');
    fputcsv( $output, $header_row );

    if ( $all_tasks_query->have_posts() ) {
        while ( $all_tasks_query->have_posts() ) {
            $all_tasks_query->the_post();
            $task_id = get_the_ID();
            $related_client_id = get_post_meta( $task_id, '_wcsl_related_client_id', true );
            
            $note_raw = get_post_meta( $task_id, '_wcsl_task_note', true );
            $note_sanitized_for_csv = str_replace( array("\r", "\n"), ' ', $note_raw );

            // <<< CHANGE: Use '?: "N/A"' to fill empty cells with "N/A" >>>
            $row_data = array(
                get_post_meta( $task_id, '_wcsl_task_date', true ) ?: 'N/A',
                $related_client_id ? get_the_title( $related_client_id ) : 'N/A',
                get_the_title(),
                get_post_meta( $task_id, '_wcsl_task_type', true ) ?: 'N/A',
                get_post_meta( $task_id, '_wcsl_task_link', true ) ?: 'N/A',
                get_post_meta( $task_id, '_wcsl_task_attachment_url', true ) ?: 'N/A',
                get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true ) ?: 'N/A',
                get_post_meta( $task_id, '_wcsl_task_status', true ) ?: 'N/A',
                get_post_meta( $task_id, '_wcsl_employee_name', true ) ?: 'N/A',
                $note_sanitized_for_csv ?: 'N/A',
            );
            fputcsv( $output, $row_data );
        }
    }
    wp_reset_postdata();

    fclose( $output );
    exit;
}
add_action( 'admin_post_wcsl_export_tasks_csv', 'wcsl_handle_export_tasks_csv' );


/**
 * Handles the request to mark an invoice as 'paid'.
 */
function wcsl_handle_mark_invoice_paid() {
    // Security and Parameter Checks
    if ( ! isset( $_GET['invoice_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
        wp_die( 'Invalid request.' );
    }
    $invoice_id = intval( $_GET['invoice_id'] );
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcsl_change_invoice_status_' . $invoice_id ) ) {
        wp_die( 'Security check failed.' );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    // Update the status in the database using our helper
    if ( function_exists('wcsl_update_invoice_status') ) {
        wcsl_update_invoice_status( $invoice_id, 'paid' );
    }

    // Redirect back to the previous page (the referer)
    wp_safe_redirect( wp_get_referer() );
    exit;
}
add_action( 'admin_post_wcsl_mark_invoice_paid', 'wcsl_handle_mark_invoice_paid' );


/**
 * Handles the request to mark an invoice as 'void'.
 */
function wcsl_handle_mark_invoice_void() {
    // Security and Parameter Checks
    if ( ! isset( $_GET['invoice_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
        wp_die( 'Invalid request.' );
    }
    $invoice_id = intval( $_GET['invoice_id'] );
    if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcsl_change_invoice_status_' . $invoice_id ) ) {
        wp_die( 'Security check failed.' );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }

    // Update the status in the database using our helper
    if ( function_exists('wcsl_update_invoice_status') ) {
        wcsl_update_invoice_status( $invoice_id, 'void' );
    }

    // Redirect back to the previous page (the referer)
    wp_safe_redirect( wp_get_referer() );
    exit;
}
add_action( 'admin_post_wcsl_mark_invoice_void', 'wcsl_handle_mark_invoice_void' );


/**
 * Hides all admin notices from other plugins/themes on our specific plugin pages.
 */
function wcsl_hide_admin_notices_on_plugin_pages() {
    // Get the current screen information
    $screen = get_current_screen();
    
    // An array of our plugin's unique page hooks (the 'id' of the screen)
    $wcsl_pages = array(
        'toplevel_page_wcsl-main-menu',
        'support-ledger_page_wcsl-billing-clients',
        'support-ledger_page_wcsl-reports',
        'support-ledger_page_wcsl-notifications',
        'support-ledger_page_wcsl-settings-help',
    );

    // If the current screen's ID is in our array of pages...
    if ( $screen && in_array( $screen->id, $wcsl_pages ) ) {
        // ...then remove the actions that display the notices.
        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );

        // Optional: You could add a custom notice here if you ever wanted one
        // that ONLY appears on your pages. For example:
        // add_action( 'admin_notices', 'my_custom_plugin_notice_function' );
    }
}
// We hook into 'admin_print_scripts' which runs early enough to catch the notices.
add_action( 'admin_print_scripts', 'wcsl_hide_admin_notices_on_plugin_pages' );


/**
 * ===================================================================
 * PDF Generation for Client Portal "My Tasks"
 * ===================================================================
 */

/**
 * Handles the request to print a client's tasks from the frontend portal.
 * This function performs security checks.
 */
function wcsl_handle_print_client_tasks_request() {
    // 1. Security and Parameter Checks (now matches the working admin pattern)
    if ( ! isset( $_GET['client_id'], $_GET['_wpnonce'], $_GET['nonce_action'] ) ) {
        wp_die( __( 'Invalid request: Missing parameters.', 'wp-client-support-ledger' ) );
    }

    $client_id = intval( $_GET['client_id'] );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] );
    // Get the nonce action from the URL to use for verification.
    $nonce_action = sanitize_text_field( $_GET['nonce_action'] );

    // Verify the nonce against the action string. This is the more robust check.
    if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
        wp_die( __( 'Security check failed. Please try again.', 'wp-client-support-ledger' ) );
    }

    // 2. Permission Check: This logic remains the same.
    if ( ! is_user_logged_in() ) {
        wp_die( __( 'You must be logged in to perform this action.', 'wp-client-support-ledger' ) );
    }
    
    $user_linked_client_id = wcsl_get_client_id_for_user( get_current_user_id() );
    
    if ( $user_linked_client_id !== $client_id ) {
        wp_die( __( 'You do not have permission to view this data.', 'wp-client-support-ledger' ) );
    }

    // 3. If all checks pass, call the display function.
    wcsl_display_client_tasks_print_page( $client_id );
}
// Use the single, correct hook for logged-in users, just like the working admin handlers.
add_action( 'admin_post_wcsl_print_client_tasks', 'wcsl_handle_print_client_tasks_request' );


/**
 * Renders the clean, print-friendly HTML page containing all tasks for a client.
 * This function is called by the handler above and ends with exit;.
 *
 * @param int $client_id The ID of the client CPT.
 */
function wcsl_display_client_tasks_print_page( $client_id ) {
    $client_name = get_the_title( $client_id );

    // Fetch ALL tasks for this client (no pagination for the PDF)
    $tasks_args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => -1, // Get all tasks
        'meta_key'       => '_wcsl_task_date',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'   => '_wcsl_related_client_id',
                'value' => $client_id,
            ),
        ),
    );
    $tasks_query = new WP_Query( $tasks_args );
    
    // --- Output Print-Specific HTML ---
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php printf( esc_html__( 'Task Report for %s', 'wp-client-support-ledger' ), esc_html( $client_name ) ); ?></title>
        <link rel="stylesheet" id="wcsl-print-style" href="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/print-style.css' ); ?>" type="text/css" media="all">
    </head>
    <body class="wcsl-print-body">
        <div class="print-report-container">
            <div class="report-header">
                <h1><?php printf( esc_html__( 'Task Report for %s', 'wp-client-support-ledger' ), esc_html( $client_name ) ); ?></h1>
                <h2><?php printf( esc_html__( 'Generated on: %s', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format') ) ) ); ?></h2>
            </div>

            <h3><?php esc_html_e( 'All Tasks', 'wp-client-support-ledger' ); ?></h3>
            
            <?php if ( $tasks_query->have_posts() ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Task', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Hours Spent', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ( $tasks_query->have_posts() ) : $tasks_query->the_post(); ?>
                        <tr>
                            <td><?php echo esc_html( get_post_meta( get_the_ID(), '_wcsl_task_date', true ) ); ?></td>
                            <td><?php the_title(); ?></td>
                            <td><?php echo esc_html( get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true ) ?: '0m' ); ?></td>
                            <td><?php echo esc_html( ucwords( str_replace('-', ' ', get_post_meta( get_the_ID(), '_wcsl_task_status', true ) ) ) ); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p><?php esc_html__('No tasks found to include in the report.', 'wp-client-support-ledger'); ?></p>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>

        </div> <!-- .print-report-container -->

        <script type="text/javascript">
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit; // CRITICAL: Stop WordPress from rendering anything else.
}



/**
 * Blocks backend access for custom roles and redirects them to the frontend portal.
 */
function wcsl_block_backend_access_for_custom_roles() {
    // Get the current URL's script name
    $current_script = basename( $_SERVER['SCRIPT_NAME'] );

    // NEW: Allow access to admin-post.php for form/action handling
    if ( 'admin-post.php' === $current_script ) {
        return;
    }

    // Get the current user
    $user = wp_get_current_user();

    if ( $user && ( in_array( 'wcsl_client', $user->roles ) || in_array( 'wcsl_employee', $user->roles ) ) ) {
        // Check if we are trying to access an admin page and it's not an AJAX request
        if ( is_admin() && ! wp_doing_ajax() ) {
            // Get the ID of the portal page from our settings
            $portal_settings = get_option('wcsl_portal_settings');
            $portal_page_id = isset($portal_settings['portal_page_id']) ? $portal_settings['portal_page_id'] : 0;
            
            // Get the URL of the portal page
            $portal_url = $portal_page_id ? get_permalink($portal_page_id) : home_url();

            // Redirect them to the portal page
            wp_redirect( $portal_url );
            exit;
        }
    }
}
add_action( 'admin_init', 'wcsl_block_backend_access_for_custom_roles' );


function wcsl_handle_print_employee_my_tasks() {
    // 1. Security and Parameter Checks
    if ( ! isset( $_GET['employee_id'], $_GET['_wpnonce'] ) ) {
        wp_die( __( 'Invalid request: Missing parameters.', 'wp-client-support-ledger' ) );
    }

    $employee_id = intval( $_GET['employee_id'] );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] );

    if ( ! wp_verify_nonce( $nonce, 'wcsl_print_my_tasks_action_' . $employee_id ) ) {
        wp_die( __( 'Security check failed. Please try again.', 'wp-client-support-ledger' ) );
    }

    // 2. Permission Check
    $current_user = wp_get_current_user();
    if ( ! is_user_logged_in() || ! in_array('wcsl_employee', (array) $current_user->roles) ) {
        wp_die( __( 'You do not have permission to view this data.', 'wp-client-support-ledger' ) );
    }
    
    $user_linked_employee_id = wcsl_get_employee_id_for_user( get_current_user_id() );
    if ( $user_linked_employee_id !== $employee_id ) {
        wp_die( __( 'You can only print your own tasks.', 'wp-client-support-ledger' ) );
    }

    // 3. Call the display function
    wcsl_display_employee_tasks_print_page( $employee_id );
}
add_action( 'admin_post_wcsl_print_employee_my_tasks', 'wcsl_handle_print_employee_my_tasks' );

/**
 * Renders the clean, print-friendly HTML page for an employee's tasks.
 */
function wcsl_display_employee_tasks_print_page( $employee_id ) {
    $employee_name = get_the_title( $employee_id );

    $tasks_args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => -1,
        'meta_key'       => '_wcsl_task_date',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'   => '_wcsl_assigned_employee_id',
                'value' => $employee_id,
            ),
        ),
    );
    $tasks_query = new WP_Query( $tasks_args );
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <title><?php printf( esc_html__( 'Task Report for %s', 'wp-client-support-ledger' ), esc_html( $employee_name ) ); ?></title>
        <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/print-style.css' ); ?>" type="text/css" media="all">
    </head>
    <body class="wcsl-print-body">
        <div class="print-report-container">
            <div class="report-header">
                <h1><?php printf( esc_html__( 'Task Report for %s', 'wp-client-support-ledger' ), esc_html( $employee_name ) ); ?></h1>
                <h2><?php printf( esc_html__( 'Generated on: %s', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format') ) ) ); ?></h2>
            </div>
            <h3><?php esc_html_e( 'All Assigned Tasks', 'wp-client-support-ledger' ); ?></h3>
            <?php if ( $tasks_query->have_posts() ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Task', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Hours Spent', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ( $tasks_query->have_posts() ) : $tasks_query->the_post(); ?>
                        <tr>
                            <td><?php echo esc_html( get_post_meta( get_the_ID(), '_wcsl_task_date', true ) ); ?></td>
                            <td><?php the_title(); ?></td>
                            <td><?php echo esc_html( get_the_title( get_post_meta( get_the_ID(), '_wcsl_related_client_id', true ) ) ); ?></td>
                            <td><?php echo esc_html( get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true ) ?: '0m' ); ?></td>
                            <td><?php echo esc_html( ucwords( str_replace('-', ' ', get_post_meta( get_the_ID(), '_wcsl_task_status', true ) ) ) ); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p><?php esc_html__('No tasks found to include in the report.', 'wp-client-support-ledger'); ?></p>
            <?php endif; wp_reset_postdata(); ?>
        </div>
        <script type="text/javascript">
            window.onload = function() { window.print(); }
        </script>
    </body>
    </html>
    <?php
    exit;
}