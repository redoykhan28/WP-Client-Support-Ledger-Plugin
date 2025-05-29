<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
 * Display callback for the Monthly Help page.
 */

function wcsl_settings_help_page_display() {
    // Check user capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-client-support-ledger' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        
        <?php // Display saved settings message from WordPress
            settings_errors(); 
        ?>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'wcsl_settings_group' ); // Option group for email settings
            do_settings_sections( 'wcsl_email_settings_section_page' ); // Unique page slug for email settings section
            submit_button( __( 'Save Settings', 'wp-client-support-ledger' ) );
            ?>
        </form>

        <hr style="margin-top: 30px; margin-bottom: 30px;">

        <?php // Your existing help content in postboxes ?>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2"> <?php // Using columns-2 for side-by-side potential ?>
                
                <!-- Main content column for Help Topics -->
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">

                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e( 'Frontend Report Shortcode Usage', 'wp-client-support-ledger' ); ?></span></h2>
                            <div class="inside">
                                <p><?php esc_html_e( 'To display the client support summary on the frontend of your site (for logged-in users), use the following shortcode on any page or post:', 'wp-client-support-ledger' ); ?></p>
                                <p><code>[client_support_report]</code></p>
                                
                                <h4><?php esc_html_e( 'Optional Attributes:', 'wp-client-support-ledger' ); ?></h4>
                                <ul>
                                    <li><code>months_per_page="N"</code>: <?php esc_html_e( 'Set the number of months to display per page on the frontend month index. Default is 12. Example:', 'wp-client-support-ledger' ); ?> <code>[client_support_report months_per_page="6"]</code></li>
                                    <?php /*
                                    If you re-introduce default_month/year for the shortcode's initial view before GET params take over:
                                    <li><code>default_month="N"</code>: <?php esc_html_e( 'Set a default month (1-12) for the initial view. Example:', 'wp-client-support-ledger' ); ?> <code>[client_support_report default_month="1"]</code></li>
                                    <li><code>default_year="YYYY"</code>: <?php esc_html_e( 'Set a default year for the initial view. Example:', 'wp-client-support-ledger' ); ?> <code>[client_support_report default_year="<?php echo date('Y'); ?>"]</code></li>
                                    */ ?>
                                </ul>
                                <p><?php esc_html_e( 'The report page will include month/year selectors for users to change the displayed period. Remember that users must be logged in to view the report.', 'wp-client-support-ledger' ); ?></p>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->

                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e( 'Tips for Printing / Saving as PDF', 'wp-client-support-ledger' ); ?></span></h2>
                            <div class="inside">
                                <p><strong><?php esc_html_e( 'Getting Clickable Links in PDFs:', 'wp-client-support-ledger' ); ?></strong></p>
                                <p>
                                    <?php esc_html_e( 'When you use the "Print/Save PDF" feature, your browser\'s print dialog will appear. To ensure any links (like "View Task" links) in the PDF are clickable, follow these suggestions:', 'wp-client-support-ledger' ); ?>
                                </p>
                                <ul>
                                    <li><?php esc_html_e( 'Choose "Save as PDF" as the destination/printer if your browser offers it directly (common in Chrome, Edge, Firefox). This method usually preserves hyperlinks well.', 'wp-client-support-ledger' ); ?></li>
                                    <li><?php esc_html_e( 'Avoid using "Microsoft Print to PDF" if you need clickable links, as it often flattens the content and links may not be preserved.', 'wp-client-support-ledger' ); ?></li>
                                </ul>

                                <p><strong><?php esc_html_e( 'Removing Headers/Footers (like URL/Date) from PDF:', 'wp-client-support-ledger' ); ?></strong></p>
                                <p>
                                    <?php esc_html_e( 'By default, browsers may add the page title, URL, date, or page numbers to the printed output. To remove these:', 'wp-client-support-ledger' ); ?>
                                </p>
                                <ul>
                                    <li><?php esc_html_e( 'In the browser\'s print dialog, look for "More settings" or an advanced options section.', 'wp-client-support-ledger' ); ?></li>
                                    <li><?php esc_html_e( 'Uncheck the option for "Headers and footers".', 'wp-client-support-ledger' ); ?></li>
                                </ul>
                                <p><small><?php esc_html_e( 'The exact location of these settings can vary slightly between different browsers.', 'wp-client-support-ledger' ); ?></small></p>
                                <hr>
                                <p><strong><?php esc_html_e( 'Important Note on Email Deliverability (SMTP):', 'wp-client-support-ledger' ); ?></strong></p>
                                <p>
                                    <?php esc_html_e( 'For reliable email notifications from this plugin (and your WordPress site in general), it is highly recommended to configure an SMTP service. Emails sent directly from web servers using PHP\'s mail() function are often flagged as spam. Using an SMTP plugin (like WP Mail SMTP, FluentSMTP, Post SMTP) with a dedicated email provider (e.g., SendGrid, Mailgun, Amazon SES, Brevo, or your Gmail/Outlook account via SMTP) will significantly improve deliverability.', 'wp-client-support-ledger' ); ?>
                                </p>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->
                        
                    </div> <!-- .meta-box-sortables -->
                </div> <!-- #post-body-content -->

                <!-- Sidebar column (optional, can be removed if you prefer single column for help) -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e( 'Plugin Support', 'wp-client-support-ledger' ); ?></span></h2>
                            <div class="inside">
                                <p><?php esc_html_e( 'If you need help or find a bug, please reach out via [Your Support Channel/Link Here].', 'wp-client-support-ledger' ); ?></p>
                                <?php // Example: <p><a href="https://example.com/support" target="_blank">Plugin Support Forum</a></p> ?>
                            </div> <!-- .inside -->
                        </div> <!-- .postbox -->
                    </div> <!-- .meta-box-sortables -->
                </div> <!-- #postbox-container-1 -->

            </div> <!-- #post-body -->
            <br class="clear">
        </div> <!-- #poststuff -->
    </div> <!-- .wrap -->
    <?php
}

/**
 * Displays an index of months that have task data.
 */
function wcsl_display_month_index() {
    global $wpdb;

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

    echo '<h2 style="font-size:30px;">' . esc_html__( 'Monthly Data Overview', 'wp-client-support-ledger' ) . '</h2>';
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
        
        // Nonces for actions
        $print_nonce_action = 'wcsl_print_report_action_' . $loop_year . '_' . $loop_month; // Nonce action string
        $print_nonce = wp_create_nonce( $print_nonce_action ); // Create nonce with specific action
        
        // *** FIX: Use $loop_year and $loop_month for delete nonce and URL args ***
        $delete_nonce_action = 'wcsl_delete_month_tasks_action_' . $loop_year . '_' . $loop_month;
        $delete_nonce = wp_create_nonce( $delete_nonce_action );

        $print_url_args = array(
            'action'   => 'wcsl_generate_print_page',
            'month'    => $loop_month,
            'year'     => $loop_year,
            '_wpnonce' => $print_nonce,
            'nonce_action' => $print_nonce_action // Pass the nonce action string
        );
        $print_url = add_query_arg( $print_url_args, admin_url( 'admin-post.php' ) );
        
        $delete_url_args = array(
            'action'   => 'wcsl_handle_delete_month_tasks',
            'month'    => $loop_month,
            'year'     => $loop_year,
            '_wpnonce' => $delete_nonce
            // No need to pass nonce_action separately for delete if using the direct nonce string in verification
        );
        $delete_url = add_query_arg( $delete_url_args, admin_url( 'admin-post.php' ) );

        echo '<tr>';
        echo '<td>' . esc_html( $month_name ) . ' ' . esc_html( $loop_year ) . '</td>';
        echo '<td>';
        echo '<a href="' . esc_url( $view_url ) . '" class="button">' . esc_html__( 'View Details', 'wp-client-support-ledger' ) . '</a> ';
        echo '<a href="' . esc_url( $print_url ) . '" class="button" target="_blank">' . esc_html__( 'Print/Save PDF', 'wp-client-support-ledger' ) . '</a> '; // Consistent Text
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

    // Get search terms
    $search_term_summary = isset( $_GET['wcsl_search_summary'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsl_search_summary'] ) ) : '';
    $search_term_tasks   = isset( $_GET['wcsl_search_tasks'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsl_search_tasks'] ) ) : '';

    // Pagination variables
    $clients_per_page = 5; // Or your desired setting for clients
    $paged_clients    = isset( $_GET['paged_clients'] ) ? max( 1, intval( $_GET['paged_clients'] ) ) : 1;
    $tasks_per_page   = 15; // Or your desired setting for tasks
    $paged_tasks      = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

    echo '<p><a href="' . esc_url( admin_url('admin.php?page=wcsl-main-menu') ) . '">' . esc_html__('« Back to Month Index', 'wp-client-support-ledger') . '</a></p>';
    
    echo '<h2 style="font-size:22px; margin-top: 10px; margin-bottom:10px;">' . sprintf( esc_html__( 'Details for %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $current_year ) ) . '</h2>';
    
    // --- Print Button Area (Placed once at the top) ---
    echo '<div class="wcsl-month-actions" style="margin-bottom: 20px; padding-bottom:15px; border-bottom:1px solid #eee; text-align:right;">';
    $print_nonce_action = 'wcsl_print_report_action_' . $current_year . '_' . $current_month;
    $print_nonce = wp_create_nonce( $print_nonce_action );
    $print_url_args = array(
        'action'   => 'wcsl_generate_print_page',
        'month'    => $current_month,
        'year'     => $current_year,
        '_wpnonce' => $print_nonce,
        'nonce_action' => $print_nonce_action
    );
    // Pass search terms to print page if they are active (optional for print page to use)
    if ( !empty($search_term_summary) ) $print_url_args['search_summary'] = $search_term_summary;
    if ( !empty($search_term_tasks) )   $print_url_args['search_tasks'] = $search_term_tasks;
    $print_url = add_query_arg( $print_url_args, admin_url( 'admin-post.php' ) );
    echo '<a href="' . esc_url( $print_url ) . '" class="button button-secondary" target="_blank" style="margin-right:10px;">' . esc_html__( 'Print/Save PDF (Full)', 'wp-client-support-ledger' ) . '</a>';
    echo '</div>';

    // --- Client Summary Table (Yellow Area) with Search AND PAGINATION ---
    ?>
    <div class="wcsl-section-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h3><?php esc_html_e( 'Client Summary', 'wp-client-support-ledger' ); ?></h3>
        <form method="GET" class="wcsl-table-search-form">
            <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>">
            <input type="hidden" name="action" value="view_month_details">
            <input type="hidden" name="month" value="<?php echo esc_attr( $current_month ); ?>">
            <input type="hidden" name="year" value="<?php echo esc_attr( $current_year ); ?>">
            <?php if ( !empty($search_term_tasks) ) : ?>
                <input type="hidden" name="wcsl_search_tasks" value="<?php echo esc_attr( $search_term_tasks ); ?>">
            <?php endif; ?>
            <?php if ($paged_tasks > 1) : ?>
                <input type="hidden" name="paged" value="<?php echo esc_attr( $paged_tasks ); ?>">
            <?php endif; ?>
            <label for="wcsl_search_summary_input" class="screen-reader-text"><?php esc_html_e( 'Search Clients:', 'wp-client-support-ledger' ); ?></label>
            <input type="search" id="wcsl_search_summary_input" name="wcsl_search_summary" value="<?php echo esc_attr( $search_term_summary ); ?>" placeholder="<?php esc_attr_e('Search Clients...', 'wp-client-support-ledger'); ?>" />
            <input type="submit" class="button" value="<?php esc_attr_e('Search', 'wp-client-support-ledger'); ?>" />
            <?php if ( !empty($search_term_summary) ) : ?>
                <a href="<?php echo esc_url( remove_query_arg(array('wcsl_search_summary', 'paged_clients')) ); ?>" class="button button-link"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a>
            <?php endif; ?>
        </form>
    </div>
    <?php
    
    $clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => $clients_per_page,
        'paged'          => $paged_clients,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish'
    );

    if ( !empty($search_term_summary) ) {
        $clients_query_args['s'] = $search_term_summary; // Add search term to client query
    }
    
    $clients_query = new WP_Query( $clients_query_args );

    if ( $clients_query->have_posts() ) :
        echo '<table class="wp-list-table widefat fixed striped clients-summary-table">';
        echo '<thead><tr><th>' . esc_html__( 'Client', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Contracted Hours', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Total Hours Spent', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Billable Hours', 'wp-client-support-ledger' ) . '</th></tr></thead>';
        echo '<tbody>';
        while ( $clients_query->have_posts() ) : $clients_query->the_post();
            $client_id = get_the_ID();
            $client_name_summary = get_the_title(); // This is now correct for the current client in the loop
            $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );

            // Calculate hours for THIS client for the given month/year
            $client_tasks_args = array(
                'post_type'      => 'client_task',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_wcsl_related_client_id',
                        'value'   => $client_id,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_wcsl_task_date',
                        'value'   => array( $first_day_of_month, $last_day_of_month ),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    ),
                ),
            );
            $client_tasks_query = new WP_Query( $client_tasks_args );
            $total_minutes_spent_this_month = 0;
            if ( $client_tasks_query->have_posts() ) {
                while ( $client_tasks_query->have_posts() ) : $client_tasks_query->the_post();
                    $hours_spent_str = get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true );
                    $total_minutes_spent_this_month += wcsl_parse_time_string_to_minutes( $hours_spent_str );
                endwhile;
            }
            wp_reset_postdata(); // For inner client_tasks_query

            $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );
            $billable_minutes = max( 0, $total_minutes_spent_this_month - $contracted_minutes );

            echo '<tr>';
            echo '<td><a href="' . esc_url( get_edit_post_link( $client_id ) ) . '">' . esc_html( $client_name_summary ) . '</a></td>';
            echo '<td>' . esc_html( !empty($contracted_hours_str) ? $contracted_hours_str : 'N/A' ) . '</td>';
            echo '<td>' . esc_html( wcsl_format_minutes_to_time_string( $total_minutes_spent_this_month ) ) . '</td>';
            echo '<td>' . esc_html( wcsl_format_minutes_to_time_string( $billable_minutes ) ) . '</td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';

        $total_client_pages = $clients_query->max_num_pages;
        if ($total_client_pages > 1){
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">';
            $client_pagination_base_args = array(
                'page'   => $_REQUEST['page'],
                'action' => 'view_month_details',
                'month'  => $current_month,
                'year'   => $current_year,
            );
            if (!empty($search_term_summary)) { // Preserve client search
                $client_pagination_base_args['wcsl_search_summary'] = $search_term_summary;
            }
            if (!empty($search_term_tasks)) { // Preserve task search
                $client_pagination_base_args['wcsl_search_tasks'] = $search_term_tasks;
            }
            if ($paged_tasks > 1) { // Preserve task pagination
                 $client_pagination_base_args['paged'] = $paged_tasks;
            }
            $client_pagination_base_url = add_query_arg($client_pagination_base_args, admin_url('admin.php'));
            echo paginate_links(array(
                'base'      => add_query_arg( 'paged_clients', '%#%', $client_pagination_base_url ),
                'format'    => '',
                'current'   => max(1, $paged_clients),
                'total'     => $total_client_pages,
                'prev_text' => __('« Prev Clients'),
                'next_text' => __('Next Clients »'),
                'add_args'  => false,
            ));
            echo '</div></div>';
        }
        wp_reset_postdata(); // After $clients_query loop
    else :
        echo '<p>' . esc_html__( 'No clients found for this period or matching your search.', 'wp-client-support-ledger' ) . '</p>';
    endif;

    echo '<br><hr/><br>';

    // --- Detailed Task Log (Red Area) with Search AND PAGINATION ---
    ?>
    <div class="wcsl-section-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h3><?php esc_html_e( 'Detailed Task Log', 'wp-client-support-ledger' ); ?></h3>
        <form method="GET" class="wcsl-table-search-form">
            <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>">
            <input type="hidden" name="action" value="view_month_details">
            <input type="hidden" name="month" value="<?php echo esc_attr( $current_month ); ?>">
            <input type="hidden" name="year" value="<?php echo esc_attr( $current_year ); ?>">
            <?php if (!empty($search_term_summary)) : // Preserve client search ?>
                <input type="hidden" name="wcsl_search_summary" value="<?php echo esc_attr( $search_term_summary ); ?>">
            <?php endif; ?>
            <?php if ($paged_clients > 1) : // Preserve client pagination ?>
                <input type="hidden" name="paged_clients" value="<?php echo esc_attr( $paged_clients ); ?>">
            <?php endif; ?>
            <label for="wcsl_search_tasks_input" class="screen-reader-text"><?php esc_html_e( 'Search Tasks:', 'wp-client-support-ledger' ); ?></label>
            <input type="search" id="wcsl_search_tasks_input" name="wcsl_search_tasks" value="<?php echo esc_attr( $search_term_tasks ); ?>" placeholder="<?php esc_attr_e('Search Tasks...', 'wp-client-support-ledger'); ?>" />
            <input type="submit" class="button" value="<?php esc_attr_e('Search', 'wp-client-support-ledger'); ?>" />
             <?php if ( !empty($search_term_tasks) ) : ?>
                <a href="<?php echo esc_url( remove_query_arg(array('wcsl_search_tasks', 'paged')) ); ?>" class="button button-link"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a>
            <?php endif; ?>
        </form>
    </div>
    <?php

    $all_tasks_args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => $tasks_per_page,
        'paged'          => $paged_tasks,
        'post_status'    => 'publish',
        'meta_key'       => '_wcsl_task_date',
        'orderby'        => 'meta_value_date',
        'order'          => 'DESC',
        'meta_query'     => array(
             array(
                'key'     => '_wcsl_task_date',
                'value'   => array( $first_day_of_month, $last_day_of_month ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ),
        ),
    );
    if ( !empty($search_term_tasks) ) {
        $all_tasks_args['s'] = $search_term_tasks;
    }
    $all_tasks_query = new WP_Query( $all_tasks_args );

    if ( $all_tasks_query->have_posts() ) :
        // ... (Task table HTML and loop as before) ...
        echo '<table class="wp-list-table widefat fixed striped tasks-log-table">';
        echo '<thead><tr>
                <th>' . esc_html__( 'Date', 'wp-client-support-ledger' ) . '</th>
                <th>' . esc_html__( 'Client', 'wp-client-support-ledger' ) . '</th>
                <th>' . esc_html__( 'Task Title', 'wp-client-support-ledger' ) . '</th>
                <th>' . esc_html__( 'Task Link', 'wp-client-support-ledger' ) . '</th>
                <th>' . esc_html__( 'Hours Spent', 'wp-client-support-ledger' ) . '</th>
                <th>' . esc_html__( 'Status', 'wp-client-support-ledger' ) . '</th>
                <th>' . esc_html__( 'Employee', 'wp-client-support-ledger' ) . '</th>
                <th>' . esc_html__( 'Note', 'wp-client-support-ledger' ) . '</th>
              </tr></thead>';
        echo '<tbody>';
        while ( $all_tasks_query->have_posts() ) : $all_tasks_query->the_post();
            $task_id = get_the_ID();
            $task_title = get_the_title();
            $task_date = get_post_meta( $task_id, '_wcsl_task_date', true );
            $task_link_url = get_post_meta( $task_id, '_wcsl_task_link', true );
            $hours_spent_str = get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true );
            $task_status = get_post_meta( $task_id, '_wcsl_task_status', true );
            $related_client_id = get_post_meta( $task_id, '_wcsl_related_client_id', true );
            $client_name_task = $related_client_id ? get_the_title( $related_client_id ) : __( 'N/A', 'wp-client-support-ledger' );
            $employee_name_meta = get_post_meta( $task_id, '_wcsl_employee_name', true );
            $employee_name = !empty($employee_name_meta) ? $employee_name_meta : __( 'N/A', 'wp-client-support-ledger' );
            $task_note = get_post_meta( $task_id, '_wcsl_task_note', true );

            echo '<tr>';
            echo '<td>' . esc_html( $task_date ) . '</td>';
            echo '<td>' . esc_html( $client_name_task ) . '</td>';
            echo '<td><a href="' . esc_url( get_edit_post_link( $task_id ) ) . '">' . esc_html( $task_title ) . '</a></td>';
            echo '<td>';
            if ( ! empty( $task_link_url ) ) {
                echo '<a href="' . esc_url( $task_link_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Task', 'wp-client-support-ledger' ) . '</a>';
            } else {
                echo esc_html__( 'N/A', 'wp-client-support-ledger' );
            }
            echo '</td>';
            echo '<td>' . esc_html( !empty($hours_spent_str) ? $hours_spent_str : '0m') . '</td>';
            echo '<td>' . esc_html( ucfirst( str_replace('-', ' ', $task_status) ) ) . '</td>';
            echo '<td>' . esc_html( $employee_name ) . '</td>';
            echo '<td>' . nl2br( esc_html( $task_note ) ) . '</td>';
            echo '</tr>';
        endwhile;
        echo '</tbody></table>';

        $total_task_pages = $all_tasks_query->max_num_pages;
        if ($total_task_pages > 1){
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">';
            $task_pagination_base_args = array(
                'page'   => $_REQUEST['page'],
                'action' => 'view_month_details',
                'month'  => $current_month,
                'year'   => $current_year,
            );
            if (!empty($search_term_summary)) { // Preserve client search
                $task_pagination_base_args['wcsl_search_summary'] = $search_term_summary;
            }
            if (!empty($search_term_tasks)) { // Preserve task search
                $task_pagination_base_args['wcsl_search_tasks'] = $search_term_tasks;
            }
            if ($paged_clients > 1) { // Preserve client pagination
                 $task_pagination_base_args['paged_clients'] = $paged_clients;
            }
            $task_pagination_base_url = add_query_arg($task_pagination_base_args, admin_url('admin.php'));
            echo paginate_links(array(
                'base'      => add_query_arg( 'paged', '%#%', $task_pagination_base_url ),
                'format'    => '',
                'current'   => max(1, $paged_tasks),
                'total'     => $total_task_pages,
                'prev_text' => __('« Prev Tasks'),
                'next_text' => __('Next Tasks »'),
                'add_args'  => false,
            ));
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
            <?php // Block 1: Hours Per Client ?>
            <div class="wcsl-report-block">
                <h2><?php 
                    printf( 
                        esc_html__( 'Hours Per Client (%s - %s)', 'wp-client-support-ledger' ), 
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), 
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) )
                    ); 
                ?></h2>
                <div class="wcsl-chart-container">
                    <canvas id="wcslHoursPerClientChart"></canvas>
                </div>
            </div>

                        
            <?php // Block 2: Billable Hours Per client ?>
            <div class="wcsl-report-block">
                <h2><?php 
                    printf( 
                        esc_html__( 'Total Billable Hours by Client (%s - %s)', 'wp-client-support-ledger' ), // <<< UPDATED TITLE
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), 
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) )
                    ); 
                ?></h2>
                <div class="wcsl-chart-container">
                    <canvas id="wcslBillableHoursPerClientChart"></canvas> <?php // <<< UPDATED CANVAS ID ?>
                </div>
            </div>


             <?php // Block 3: Total Billable Hours ?>
            <div class="wcsl-report-block">
                <h2><?php 
                    printf( 
                        esc_html__( 'Total Billable Hours (%s - %s)', 'wp-client-support-ledger' ),
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), 
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) )
                    );                     
                ?></h2>
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
            <?php  ?>

            <?php // Block 4: Employee Hours (Circular Chart) ?>
            <div class="wcsl-report-block">
                 <h2><?php 
                    printf( 
                        esc_html__( 'Hours by Employee (%s - %s)', 'wp-client-support-ledger' ),
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), 
                        esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) )
                    );                     
                ?></h2>
                <div class="wcsl-chart-container" style="height: 350px;"> <?php // Doughnut/Pie might need more height ?>
                    <canvas id="wcslHoursByEmployeeChart"></canvas>
                </div>
            </div>
        </div> <?php // This is the correct closing tag for .wcsl-reports-grid ?>
    </div> <?php // .wrap ?>
    <?php
}



/**
 * Display callback for the Notifications page.
 */
function wcsl_notifications_page_display() {
    // This capability check runs when the page is loaded.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-client-support-ledger' ) );
    }

    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    $current_page_url = admin_url('admin.php?page=wcsl-notifications'); // Base URL for redirects

    // --- Handle Actions (Mark as Read/Unread, Delete) ---
    if ( isset( $_GET['wcsl_notification_action'] ) && isset( $_GET['notification_id'] ) && isset( $_GET['_wpnonce'] ) ) {
        $action          = sanitize_key( $_GET['wcsl_notification_action'] );
        $notification_id = intval( $_GET['notification_id'] );
        $nonce           = sanitize_text_field( $_GET['_wpnonce'] );
        $redirect_args   = array();

        // Verify the nonce against the specific action for this notification ID
        if ( $notification_id > 0 && wp_verify_nonce( $nonce, 'wcsl_manage_notification_' . $notification_id ) ) {
            $message_text = '';
            $message_type = 'success'; // Default to success

            if ( 'mark_read' === $action ) {
                $wpdb->update( $table_name, array( 'is_read' => 1 ), array( 'id' => $notification_id ), array('%d'), array('%d') );
                $message_text = __( 'Notification marked as read.', 'wp-client-support-ledger' );
            } elseif ( 'mark_unread' === $action ) {
                $wpdb->update( $table_name, array( 'is_read' => 0 ), array( 'id' => $notification_id ), array('%d'), array('%d') );
                $message_text = __( 'Notification marked as unread.', 'wp-client-support-ledger' );
            } elseif ( 'delete' === $action ) {
                $wpdb->delete( $table_name, array( 'id' => $notification_id ), array('%d') );
                $message_text = __( 'Notification deleted.', 'wp-client-support-ledger' );
            } else {
                $message_text = __( 'Invalid notification action.', 'wp-client-support-ledger' );
                $message_type = 'error';
            }
            $redirect_args['wcsl_admin_notice'] = urlencode($message_text);
            $redirect_args['notice_type'] = $message_type;

        } else if ($notification_id > 0) { // Nonce verification failed
            $redirect_args['wcsl_admin_notice'] = urlencode(__( 'Security check failed. Please try again.', 'wp-client-support-ledger' ));
            $redirect_args['notice_type'] = 'error';
        }

        // Preserve pagination if it was set on the original page
        if (isset($_GET['paged']) && intval($_GET['paged']) > 0) {
            $redirect_args['paged'] = intval($_GET['paged']);
        }
        
        // Redirect after processing to avoid re-submission and clear GET params for action
        wp_safe_redirect( add_query_arg( $redirect_args, $current_page_url ) );
        exit;
    }
    // --- End Action Handling ---

    // --- Display Admin Notices (if redirected with them) ---
    // This part is now handled by your separate wcsl_admin_notices function hooked to 'admin_notices'

    // --- Pagination Parameters ---
    $items_per_page = 20;
    $current_paged_val = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1; // Use a different var name
    $offset = ( $current_paged_val - 1 ) * $items_per_page;

    $total_items_sql = "SELECT COUNT(id) FROM {$table_name}";
    // Add user filtering here in future if needed
    $total_items = $wpdb->get_var( $total_items_sql );

    $notifications_sql = $wpdb->prepare(
        "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $items_per_page, $offset
    );
    $notifications = $wpdb->get_results( $notifications_sql );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form method="get"> <?php // Kept for future bulk actions, not strictly needed now ?>
            <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />

            <table class="wp-list-table widefat fixed striped wcsl-notifications-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Notification', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Type', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ( ! empty( $notifications ) ) : ?>
                        <?php foreach ( $notifications as $notification ) : ?>
                            <?php
                            $row_classes = ($notification->is_read == 0) ? 'wcsl-notification-unread' : 'wcsl-notification-read';
                            // Base URL for actions, already includes page=wcsl-notifications
                            $base_action_url = add_query_arg( 'notification_id', $notification->id, $current_page_url );
                            if ($current_paged_val > 1) {
                                $base_action_url = add_query_arg('paged', $current_paged_val, $base_action_url);
                            }
                            ?>
                            <tr class="<?php echo esc_attr($row_classes); ?>">
                                <td class="column-primary has-row-actions" data-colname="<?php esc_attr_e( 'Notification', 'wp-client-support-ledger' ); ?>">
                                    <?php echo wp_kses_post( $notification->message ); ?>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details' ); ?></span></button>
                                    <div class="row-actions">
                                    <?php
                                    // Nonce for AJAX actions for this specific notification
                                    $ajax_nonce = wp_create_nonce('wcsl_ajax_manage_notification_' . $notification->id);
                                    ?>
                                    <?php if ( $notification->is_read == 0 ) : ?>
                                        <span class="mark-read">
                                            <a href="#" class="wcsl-notification-action" 
                                               data-action="mark_read" 
                                               data-nonce="<?php echo esc_attr($ajax_nonce); ?>"
                                               data-notification-id="<?php echo esc_attr($notification->id); ?>">
                                                <?php esc_html_e( 'Mark Read', 'wp-client-support-ledger' ); ?></a> |
                                        </span>
                                    <?php else : ?>
                                         <span class="mark-unread">
                                            <a href="#" class="wcsl-notification-action"
                                               data-action="mark_unread"
                                               data-nonce="<?php echo esc_attr($ajax_nonce); ?>"
                                               data-notification-id="<?php echo esc_attr($notification->id); ?>">
                                                <?php esc_html_e( 'Mark Unread', 'wp-client-support-ledger' ); ?></a> |
                                        </span>
                                    <?php endif; ?>
                                    <span class="delete">
                                        <a href="#" class="wcsl-notification-action wcsl-delete-notification-ajax"
                                           data-action="delete"
                                           data-nonce="<?php echo esc_attr($ajax_nonce); ?>"
                                           data-notification-id="<?php echo esc_attr($notification->id); ?>">
                                            <?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></a>
                                    </span>
                                </div>
                                </td>
                                <td data-colname="<?php esc_attr_e( 'Type', 'wp-client-support-ledger' ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $notification->type ) ) ); ?></td>
                                <td data-colname="<?php esc_attr_e( 'Date', 'wp-client-support-ledger' ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $notification->created_at ) ) ); ?></td>
                                <td data-colname="<?php esc_attr_e( 'Status', 'wp-client-support-ledger' ); ?>"><?php echo ( $notification->is_read == 0 ) ? '<strong>' . esc_html__( 'Unread', 'wp-client-support-ledger' ) . '</strong>' : esc_html__( 'Read', 'wp-client-support-ledger' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="4"><?php esc_html_e( 'No notifications found.', 'wp-client-support-ledger' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Notification', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Type', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </tfoot>
            </table>
        </form>

        <?php
        if ( $total_items > $items_per_page ) {
            $total_pages = ceil( $total_items / $items_per_page );
            echo '<div class="tablenav bottom"><div class="tablenav-pages">';
            $pagination_base_url_nav = remove_query_arg(array('wcsl_notification_action', 'notification_id', '_wpnonce', 'wcsl_admin_notice', 'notice_type'), $current_page_url);
            echo paginate_links( array(
                'base'      => add_query_arg( 'paged', '%#%', $pagination_base_url_nav ),
                'format'    => '',
                'current'   => $current_paged_val,
                'total'     => $total_pages,
                'prev_text' => '«',
                'next_text' => '»',
            ) );
            echo '</div></div>';
        }
        ?>
    </div> <!-- .wrap -->
    <?php
}



// Enqueue admin scripts
function wcsl_admin_enqueue_scripts( $hook_suffix ) {
    // --- Determine the correct hook suffixes for your plugin pages ---
    $main_page_hook = 'toplevel_page_wcsl-main-menu'; 
    // Example submenu hooks (VERIFY THESE for your setup by logging get_current_screen()->id on each page)
    $notifications_page_hook = 'support-ledger_page_wcsl-notifications'; 
    $reports_page_hook       = 'support-ledger_page_wcsl-reports';       
    $settings_page_hook      = 'support-ledger_page_wcsl-settings-help'; 

    // --- Flags for loading assets ---
    $load_admin_style = false;
    $load_notifications_js = false;
    $load_reports_js_and_chartjs = false;

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

    // --- Enqueue General Admin Style ---
    if ( $load_admin_style ) {
        wp_enqueue_style(
            'wcsl-admin-style',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-style.css',
            array(),
            '1.0.6' // Your current version
        );
    }

    // --- Enqueue Notifications JS ---
    if ( $load_notifications_js ) {
        wp_enqueue_script(
            'wcsl-admin-notifications-js',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-notifications.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
        wp_localize_script( 'wcsl-admin-notifications-js', 'wcsl_ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'delete_confirm_message' => esc_js(__( 'Are you sure you want to delete this notification?', 'wp-client-support-ledger' ))
        ) );
    }

    // --- Enqueue Reports JS and Chart.js ---
    if ( $load_reports_js_and_chartjs ) {
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(), 
            '4.4.1', 
            true     
        );
        wp_enqueue_script(
            'wcsl-admin-reports-js',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-reports.js',
            array( 'jquery', 'chartjs' ), 
            '1.0.3', // Your current reports JS version
            true
        );

        if ( $hook_suffix === $settings_page_hook ) { // Assuming $settings_page_hook is correctly defined
            // Enqueue for color picker already done by wcsl_field_color_picker_cb
            // but ensure our custom JS is loaded after wp-color-picker
            wp_enqueue_script(
                'wcsl-admin-settings-js',
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-settings.js',
                array( 'jquery', 'wp-color-picker' ), // Depends on jQuery and wp-color-picker
                '1.0.0',
                true
            );
        }

        // Prepare and Localize Data for Charts
        $today_for_reports = current_time('Y-m-d');
        $default_start_for_reports = date('Y-m-d', strtotime('-29 days', strtotime($today_for_reports)));

        $report_start_date = isset( $_GET['start_date'] ) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date']) 
                               ? sanitize_text_field( $_GET['start_date'] ) 
                               : $default_start_for_reports;
        $report_end_date   = isset( $_GET['end_date'] ) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date'])
                               ? sanitize_text_field( $_GET['end_date'] )
                               : $today_for_reports;
        
        if (strtotime($report_end_date) < strtotime($report_start_date)) {
            $report_end_date = $report_start_date;
        }

        // Data for "Hours Per Client" Chart
        $chart_data_hours_per_client = array(
            'labels' => array(),
            'data' => array(),
            'error' => '',
            'current_start_date' => $report_start_date, // Keep this structure as your JS expects it
            'current_end_date'   => $report_end_date
        );
        if ( function_exists('wcsl_get_hours_per_client_for_period') ) {
            $fetched_data = wcsl_get_hours_per_client_for_period( $report_start_date, $report_end_date );
            if ( !empty($fetched_data['labels']) && !empty($fetched_data['data']) ) {
                $chart_data_hours_per_client['labels'] = $fetched_data['labels'];
                $chart_data_hours_per_client['data']   = $fetched_data['data'];
            } else {
                $chart_data_hours_per_client['error'] = sprintf(
                    esc_html__('No data for Hours Per Client in period: %s to %s.', 'wp-client-support-ledger'),
                    esc_html( date_i18n( get_option('date_format'), strtotime($report_start_date) ) ),
                    esc_html( date_i18n( get_option('date_format'), strtotime($report_end_date) ) )
                );
            }
        } else {
             $chart_data_hours_per_client['error'] = __('Error: wcsl_get_hours_per_client_for_period() is missing.', 'wp-client-support-ledger');
        }

        // Data for "Total Billable Hours" (Metric)
        $total_billable_data = array( 'value_minutes' => 0, 'value_string'  => '0m', 'error' => '' );
        if ( function_exists('wcsl_get_total_billable_minutes_for_period') && function_exists('wcsl_format_minutes_to_time_string') ) {
            $billable_minutes = wcsl_get_total_billable_minutes_for_period( $report_start_date, $report_end_date );
            $total_billable_data['value_minutes'] = $billable_minutes;
            $total_billable_data['value_string']  = wcsl_format_minutes_to_time_string( $billable_minutes );
        } else {
            $total_billable_data['error'] = __('Error: Helper functions for total billable hours are missing.', 'wp-client-support-ledger');
        }

        // *** NEW: Data for "Billable Hours Per Client" Chart (replaces "per task") ***
        $chart_data_billable_per_client = array( 'labels' => array(), 'data' => array(), 'error' => '' );
        if ( function_exists('wcsl_get_billable_summary_per_client_for_period') ) {
            $fetched_billable_clients = wcsl_get_billable_summary_per_client_for_period( $report_start_date, $report_end_date );
            if ( !empty($fetched_billable_clients['labels']) && !empty($fetched_billable_clients['data']) ) {
                $chart_data_billable_per_client['labels'] = $fetched_billable_clients['labels'];
                $chart_data_billable_per_client['data']   = $fetched_billable_clients['data'];
            } else {
                // More specific error message if needed, or reuse a generic one
                $chart_data_billable_per_client['error'] = sprintf(
                    esc_html__('No clients with billable hours in period: %s to %s.', 'wp-client-support-ledger'),
                    esc_html( date_i18n( get_option('date_format'), strtotime($report_start_date) ) ),
                    esc_html( date_i18n( get_option('date_format'), strtotime($report_end_date) ) )
                );
            }
        } else {
             $chart_data_billable_per_client['error'] = __('Error: wcsl_get_billable_summary_per_client_for_period() is missing.', 'wp-client-support-ledger');
        }

        // *** NEW: Data for "Hours by Employee" Chart ***
        $chart_data_hours_by_employee = array( 'labels' => array(), 'data' => array(), 'error' => '' );
        if ( function_exists('wcsl_get_hours_by_employee_for_period') ) {
            $fetched_employee_hours = wcsl_get_hours_by_employee_for_period( $report_start_date, $report_end_date );
            if ( !empty($fetched_employee_hours['labels']) && !empty($fetched_employee_hours['data']) ) {
                $chart_data_hours_by_employee['labels'] = $fetched_employee_hours['labels'];
                $chart_data_hours_by_employee['data']   = $fetched_employee_hours['data'];
            } else {
                 $chart_data_hours_by_employee['error'] = sprintf(
                    esc_html__('No employee hours logged in period: %s to %s.', 'wp-client-support-ledger'),
                    esc_html( date_i18n( get_option('date_format'), strtotime($report_start_date) ) ),
                    esc_html( date_i18n( get_option('date_format'), strtotime($report_end_date) ) )
                );
            }
        } else {
             $chart_data_hours_by_employee['error'] = __('Error: wcsl_get_hours_by_employee_for_period() is missing.', 'wp-client-support-ledger');
        }

        wp_localize_script( 
            'wcsl-admin-reports-js', 
            'wcsl_report_data_obj', 
            array(
                'hoursPerClient'       => $chart_data_hours_per_client, // This includes current_start_date and current_end_date
                'totalBillableHours'   => $total_billable_data,
                'billablePerClient'    => $chart_data_billable_per_client, // <<< Using this key for the new chart
                'hoursByEmployee'      => $chart_data_hours_by_employee,   // <<< Using this key
                'chartColors'          => array( /* Your array of colors */
                    'rgba(57, 97, 140, 0.7)',   // Your primary blue
                    'rgba(91, 192, 222, 0.7)',  // Light blue
                    'rgba(240, 173, 78, 0.7)',  // Orange
                    'rgba(92, 184, 92, 0.7)',   // Green
                    'rgba(217, 83, 79, 0.7)',   // Red
                    'rgba(153, 102, 255, 0.7)', // Purple
                    'rgba(255, 193, 7, 0.7)',   // Yellow
                    'rgba(52, 73, 94, 0.7)',    // Dark grey/blue
                    'rgba(26, 188, 156, 0.7)',  // Teal
                    'rgba(231, 76, 60, 0.7)'    // Alizarin Crimson
                    // Add more if you expect more than 10 items often in pie/doughnut charts
                ),
                'chartBorderColors'  => array( /* Corresponding border colors */
                    'rgb(57, 97, 140)',
                    'rgb(91, 192, 222)',
                    'rgb(240, 173, 78)',
                    'rgb(92, 184, 92)',
                    'rgb(217, 83, 79)',
                    'rgb(153, 102, 255)',
                    'rgb(255, 193, 7)',
                    'rgb(52, 73, 94)',
                    'rgb(26, 188, 156)',
                    'rgb(231, 76, 60)'
                ),
                // These are now general and can be used by the JS title formatter function
                'report_start_date_formatted' => date_i18n(get_option('date_format'), strtotime($report_start_date)),
                'report_end_date_formatted'   => date_i18n(get_option('date_format'), strtotime($report_end_date)),
                'i18n' => array( 
                    'hoursSpentByClientTitle'   => esc_js(__( 'Hours Spent by Client (%s - %s)', 'wp-client-support-ledger' )),
                    'billableHoursByClientTitle'=> esc_js(__( 'Total Billable Hours by Client (%s - %s)', 'wp-client-support-ledger' )),
                    'hoursByEmployeeTitle'      => esc_js(__( 'Hours Logged by Employee (%s - %s)', 'wp-client-support-ledger' )),
                    'hoursLabel'                => esc_js(__( 'Hours', 'wp-client-support-ledger' ))
                )
            ) 
        );
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

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to view this report (handler).', 'wp-client-support-ledger' ) );
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

