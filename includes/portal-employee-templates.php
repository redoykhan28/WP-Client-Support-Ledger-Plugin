<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ===================================================================
 * Main Employee Portal Rendering Function (CORRECTED Jobi Structure)
 * ===================================================================
 */
function wcsl_render_employee_portal_main( $user ) {
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    $current_view = isset( $_GET['wcsl_view'] ) ? sanitize_key( $_GET['wcsl_view'] ) : 'dashboard';
    $unread_count = wcsl_get_unread_notification_count();
    ?>
    <div id="wcsl-portal-app-wrapper">

     <!-- NEW: Global AJAX Loader -->
        <div class="wcsl-global-loader">
            <div class="wcsl-spinner"></div>
        </div>
        
        <div class="wcsl-portal-sidebar">
            <div class="wcsl-sidebar-widget">
                <h4><?php esc_html_e( 'Menu', 'wp-client-support-ledger' ); ?></h4>
               <ul>
                    <li class="<?php echo ($current_view === 'dashboard' || $current_view === 'month_details') ? 'active' : ''; ?>"><a href="<?php echo esc_url( add_query_arg('wcsl_view', 'dashboard', $portal_page_url) ); ?>" class="wcsl-ajax-load-main"><img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/dashboard.png' ); ?>" alt=""><span><?php esc_html_e( 'Dashboard', 'wp-client-support-ledger' ); ?></span></a></li>
                    <li class="<?php echo ($current_view === 'add_task') ? 'active' : ''; ?>"><a href="<?php echo esc_url( add_query_arg('wcsl_view', 'add_task', $portal_page_url) ); ?>" class="wcsl-ajax-load-main"><img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/tab.png' ); ?>" alt=""><span><?php esc_html_e( 'Add Task', 'wp-client-support-ledger' ); ?></span></a></li>
                    <li class="<?php echo ($current_view === 'all_tasks') ? 'active' : ''; ?>"><a href="<?php echo esc_url( add_query_arg('wcsl_view', 'all_tasks', $portal_page_url) ); ?>" class="wcsl-ajax-load-main"><img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/task-list.png' ); ?>" alt=""><span><?php esc_html_e( 'All Tasks', 'wp-client-support-ledger' ); ?></span></a></li>
                    <li class="<?php echo ($current_view === 'my_tasks' || $current_view === 'edit_task') ? 'active' : ''; ?>"><a href="<?php echo esc_url( add_query_arg('wcsl_view', 'my_tasks', $portal_page_url) ); ?>" class="wcsl-ajax-load-main"><img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/task.png' ); ?>" alt=""><span><?php esc_html_e( 'My Tasks', 'wp-client-support-ledger' ); ?></span></a></li>
                    <li class="<?php echo ($current_view === 'reports') ? 'active' : ''; ?>"><a href="<?php echo esc_url( add_query_arg('wcsl_view', 'reports', $portal_page_url) ); ?>" class="wcsl-ajax-load-main"><img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/bar-chart.png' ); ?>" alt=""><span><?php esc_html_e( 'Reports', 'wp-client-support-ledger' ); ?></span></a></li>
                    <li class="<?php echo ($current_view === 'notifications' || $current_view === 'notifications_table') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( add_query_arg('wcsl_view', 'notifications', $portal_page_url) ); ?>" class="wcsl-ajax-load-main">
                            <img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/bell.png' ); ?>" alt=""><span><?php esc_html_e( 'Notifications', 'wp-client-support-ledger' ); ?></span>
                            <?php if ( $unread_count > 0 ) : ?><span class="wcsl-notification-badge"><?php echo esc_html( $unread_count ); ?></span><?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="wcsl-portal-main" id="wcsl-portal-main-content-wrapper">
            
            <div class="wcsl-main-header">
                <div id="wcsl-dynamic-page-title">
                    <?php // This will be populated by JavaScript on page load and AJAX navigation ?>
                </div>
                <div class="wcsl-header-profile-area">
                    <div class="wcsl-profile-image" title="<?php echo esc_attr( $user->display_name ); ?>">
                        <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/man.png' ); ?>" alt="Profile" class="wcsl-avatar-img">
                    </div>
                    <a href="<?php echo esc_url( wp_logout_url( $portal_page_url ) ); ?>" class="wcsl-logout-icon" title="<?php esc_attr_e( 'Log Out', 'wp-client-support-ledger' ); ?>">
                        <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/log-out.png' ); ?>" alt="Logout">
                    </a>
                </div>
            </div>

            <div id="wcsl-portal-main-content">
                <?php
                // Main Router
                if ( 'month_details' === $current_view && isset($_GET['wcsl_month']) && isset($_GET['wcsl_year']) ) {
                    wcsl_render_employee_month_details_content(intval($_GET['wcsl_month']), intval($_GET['wcsl_year']));
                } elseif ( 'add_task' === $current_view ) {
                    wcsl_render_employee_add_task_form();
                } elseif ( 'all_tasks' === $current_view ) {
                    wcsl_render_employee_all_tasks_page();
                } elseif ( 'my_tasks' === $current_view ) {
                    wcsl_render_employee_my_tasks_page();
                } elseif ( 'edit_task' === $current_view && isset( $_GET['task_id'] ) ) {
                    wcsl_render_employee_edit_task_form( intval( $_GET['task_id'] ) );
                } elseif ( 'reports' === $current_view ) {
                    wcsl_render_employee_reports_page();
                } elseif ( 'notifications' === $current_view ) {
                    wcsl_render_employee_notifications_page();
                } else { 
                    wcsl_render_employee_dashboard_content();
                }
                ?>
            </div>
        </div>
        
    </div> <?php // End #wcsl-portal-app-wrapper ?>
    <?php
}

/**
 * Renders the Employee Dashboard Content
 */
function wcsl_render_employee_dashboard_content() {
    global $wpdb;
    $current_month = date( 'n' );
    $current_year  = date( 'Y' );
    $metrics = function_exists('wcsl_get_dashboard_metrics') ? wcsl_get_dashboard_metrics( $current_month, $current_year ) : array();
    $watchlist = function_exists('wcsl_get_clients_nearing_limit') ? wcsl_get_clients_nearing_limit( $current_month, $current_year ) : array();
    $recent_activity = function_exists('wcsl_get_recent_activity') ? wcsl_get_recent_activity() : array();
    ?>
    <?php // The header div that was here has been removed and is now in the main shell function. ?>

    <div class="wcsl-metric-grid">
        <div class="wcsl-card wcsl-metric-card">
            <div class="metric-content">
                <h4><?php esc_html_e( 'Total Hours Logged', 'wp-client-support-ledger' ); ?></h4>
                <p class="metric-value"><?php echo isset($metrics['total_minutes']) ? esc_html( wcsl_format_minutes_to_time_string( $metrics['total_minutes'] ) ) : '0m'; ?></p>
            </div>
            <div class="metric-icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/work-schedule.png' ); ?>" alt="">
            </div>
        </div>
        <div class="wcsl-card wcsl-metric-card">
            <div class="metric-content">
                <h4><?php esc_html_e( 'Billable Hours', 'wp-client-support-ledger' ); ?></h4>
                <p class="metric-value"><?php echo isset($metrics['billable_minutes']) ? esc_html( wcsl_format_minutes_to_time_string( $metrics['billable_minutes'] ) ) : '0m'; ?></p>
            </div>
            <div class="metric-icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/working-time.png' ); ?>" alt="">
            </div>
        </div>
        <div class="wcsl-card wcsl-metric-card">
            <div class="metric-content">
                <h4><?php esc_html_e( 'Active Tasks', 'wp-client-support-ledger' ); ?></h4>
                <p class="metric-value"><?php echo isset($metrics['active_tasks']) ? esc_html( number_format_i18n($metrics['active_tasks']) ) : '0'; ?></p>
            </div>
            <div class="metric-icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/to-do-list.png' ); ?>" alt="">
            </div>
        </div>
    </div>

    <div class="wcsl-dashboard-grid">
        <div class="wcsl-card">
            <div class="wcsl-card-header">
                <h3 class="wcsl-card-title"><?php esc_html_e( 'Clients Nearing Limit (80%+)', 'wp-client-support-ledger' ); ?></h3>
            </div>
             <?php if ( ! empty( $watchlist ) ) : ?>
                <table class="wcsl-dashboard-watchlist-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                            <th><?php esc_html_e( 'Usage', 'wp-client-support-ledger' ); ?></th>
                            <th style="width: 140px;"><?php esc_html_e( 'Percent Used', 'wp-client-support-ledger' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $watchlist as $client ) : ?>
                            <tr>
                                <td><div class="wcsl-client-info"><?php echo esc_html( $client['name'] ); ?></div></td>
                                <td><div class="wcsl-client-usage"><?php echo esc_html( $client['usage_str'] ); ?></div></td>
                                <td>
                                    <div class="wcsl-progress-wrapper">
                                        <div class="wcsl-progress-bar-container">
                                            <div class="wcsl-progress-bar <?php echo $client['percentage'] >= 100 ? 'danger' : ''; ?>" style="width: <?php echo min(100, $client['percentage']); ?>%;"></div>
                                        </div>
                                        <span class="wcsl-percentage-text"><?php echo esc_html( $client['percentage'] ); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="wcsl-panel-notice"><?php esc_html_e( 'No clients have reached the 80% threshold this month.', 'wp-client-support-ledger' ); ?></p>
            <?php endif; ?>
        </div>

        <div class="wcsl-card">
            <div class="wcsl-card-header">
                <h3 class="wcsl-card-title"><?php esc_html_e( 'Recent Task Activity', 'wp-client-support-ledger' ); ?></h3>
            </div>
            <?php if ( ! empty( $recent_activity ) ) : ?>
                <ul class="wcsl-recent-activity-list">
                    <?php foreach ( $recent_activity as $task ) : ?>
                        <li>
                            <div class="activity-title"><?php echo esc_html( $task['task_title'] ); ?> for <?php echo esc_html( $task['client_name'] ); ?></div>
                            <div class="activity-time"><?php printf( esc_html__( 'Last updated: %s', 'wp-client-support-ledger' ), esc_html( $task['modified_date'] ) ); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="wcsl-panel-notice"><?php esc_html_e( 'No recent task activity found.', 'wp-client-support-ledger' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php
    $results = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT YEAR(meta_value) as task_year, MONTH(meta_value) as task_month FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY task_year DESC, task_month DESC", '_wcsl_task_date'));
    if ( ! empty( $results ) ) :
        $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    ?>
    <div class="wcsl-card">
        <div class="wcsl-card-header">
            <h3 class="wcsl-card-title"><?php esc_html_e( 'Monthly Data Archive', 'wp-client-support-ledger' ); ?></h3>
        </div>
        <table class="wcsl-archive-table">
            <thead><tr><th><?php esc_html_e( 'Month / Year', 'wp-client-support-ledger' ); ?></th><th><?php esc_html_e( 'Actions', 'wp-client-support-ledger' ); ?></th></tr></thead>
            <tbody>
            <?php foreach ( $results as $row ) :
                $loop_year  = intval( $row->task_year );
                $loop_month = intval( $row->task_month );
                $month_name = date_i18n( 'F', mktime( 0, 0, 0, $loop_month, 1, $loop_year ) );
                $view_url = add_query_arg( array('wcsl_view' => 'month_details', 'wcsl_month'  => $loop_month, 'wcsl_year' => $loop_year), $portal_page_url );
                $print_nonce_action = 'wcsl_print_report_action_' . $loop_year . '_' . $loop_month;
                $print_nonce = wp_create_nonce($print_nonce_action);
                $print_url_args = array(
                    'action'       => 'wcsl_generate_print_page',
                    'month'        => $loop_month,
                    'year'         => $loop_year,
                    '_wpnonce'     => $print_nonce,
                    'nonce_action' => $print_nonce_action
                );
                $print_url = add_query_arg( $print_url_args, admin_url( 'admin-post.php' ) );
                ?>
                <tr>
                    <td><?php echo esc_html( $month_name ) . ' ' . esc_html( $loop_year ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( $view_url ); ?>" class="wcsl-portal-button wcsl-ajax-load-main"><?php esc_html_e( 'View Details', 'wp-client-support-ledger' ); ?></a>
                        <a href="<?php echo esc_url( $print_url ); ?>" class="wcsl-portal-button" target="_blank"><?php esc_html_e( 'Print/Save PDF', 'wp-client-support-ledger' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php
}

/**
 * Renders the FULL "Month Details" Content for Employees
 */
function wcsl_render_employee_month_details_content( $current_month, $current_year ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $current_month );
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    $back_to_dashboard_url = add_query_arg('wcsl_view', 'dashboard', $portal_page_url);

    // Get filter values for forms
    $search_term_summary = isset( $_GET['search_summary'] ) ? sanitize_text_field( $_GET['search_summary'] ) : '';
    $search_term_tasks   = isset( $_GET['search_tasks'] ) ? sanitize_text_field( $_GET['search_tasks'] ) : '';
    $filter_client_id    = isset( $_GET['filter_client'] ) ? intval( $_GET['filter_client'] ) : 0;
    $filter_employee_id  = isset( $_GET['filter_employee'] ) ? intval( $_GET['filter_employee'] ) : 0;
    $filter_status       = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : '';
    $filter_task_type    = isset( $_GET['filter_task_type'] ) ? sanitize_key( $_GET['filter_task_type'] ) : '';
    $is_filtered = ! empty( $search_term_tasks ) || $filter_client_id > 0 || $filter_employee_id > 0 || ! empty( $filter_status ) || ! empty( $filter_task_type );
    
    $print_nonce_action = 'wcsl_print_report_action_' . $current_year . '_' . $current_month;
    $print_nonce = wp_create_nonce( $print_nonce_action );
    $print_url_args = array('action' => 'wcsl_generate_print_page', 'month' => $current_month, 'year' => $current_year, '_wpnonce' => $print_nonce, 'nonce_action' => $print_nonce_action);
    $print_url = add_query_arg( $print_url_args, admin_url( 'admin-post.php' ) );
    ?>
    
    <div class="wcsl-page-header-wrapper">
        <a href="<?php echo esc_url( $back_to_dashboard_url ); ?>" class="wcsl-back-link wcsl-ajax-load-main">« <?php esc_html_e('Back to Dashboard', 'wp-client-support-ledger'); ?></a>
        <div class="wcsl-main-header">
             <h2 class="wcsl-page-title"><?php printf( esc_html__( 'Details for %s', 'wp-client-support-ledger' ), "{$month_name} {$current_year}" ); ?></h2>
            <a href="<?php echo esc_url($print_url); ?>" class="wcsl-portal-button" target="_blank"><?php esc_html_e('Print/Save PDF (Full)', 'wp-client-support-ledger'); ?></a>
        </div>
    </div>
    
    <?php // --- Client Summary Table --- ?>
    <div class="wcsl-data-table-wrapper">
        <div class="wcsl-data-table-header">
            <h3 class="wcsl-data-table-title"><?php esc_html_e( 'Client Summary', 'wp-client-support-ledger' ); ?></h3>
            <div class="wcsl-data-table-controls">
                <form method="GET" class="wcsl-ajax-form" data-target="#employee-client-summary-container">
                    <input type="hidden" name="wcsl_view" value="month_details_summary_table"><input type="hidden" name="wcsl_month" value="<?php echo esc_attr( $current_month ); ?>"><input type="hidden" name="wcsl_year" value="<?php echo esc_attr( $current_year ); ?>">
                    <div class="wcsl-search-group">
                        <input type="search" name="search_summary" value="<?php echo esc_attr( $search_term_summary ); ?>" placeholder="<?php esc_attr_e('Search Clients...', 'wp-client-support-ledger'); ?>" />
                        <input type="submit" class="wcsl-portal-button" value="<?php esc_attr_e('Search', 'wp-client-support-ledger'); ?>" />
                    </div>
                    <?php if ( ! empty( $search_term_summary ) ) : ?><a href="<?php echo esc_url( add_query_arg( array('wcsl_view' => 'month_details_summary_table', 'wcsl_month' => $current_month, 'wcsl_year' => $current_year), $portal_page_url ) ); ?>" class="wcsl-portal-button-clear wcsl-ajax-load-main"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a><?php endif; ?>
                </form>
            </div>
        </div>
        <div id="employee-client-summary-container" class="wcsl-ajax-container">
            <?php wcsl_render_employee_month_details_summary_table(); ?>
        </div>
    </div>

    <?php // --- Detailed Task Log Table --- ?>
    <div class="wcsl-data-table-wrapper">
        <div class="wcsl-data-table-header">
            <h3 class="wcsl-data-table-title"><?php esc_html_e( 'Detailed Task Log', 'wp-client-support-ledger' ); ?></h3>
            <div class="wcsl-data-table-controls">
                <?php $export_url_args = array('action' => 'wcsl_export_tasks_csv', 'month' => $current_month, 'year' => $current_year, 'filter_client' => $filter_client_id, 'filter_employee' => $filter_employee_id, 'filter_status' => $filter_status, 'filter_task_type' => $filter_task_type, '_wpnonce' => wp_create_nonce('wcsl_export_tasks_csv_nonce')); ?>
                <a href="<?php echo esc_url( add_query_arg( $export_url_args, admin_url('admin-post.php') ) ); ?>" class="wcsl-portal-button" target="_blank"><?php esc_html_e( 'Export to CSV', 'wp-client-support-ledger' ); ?></a>
            </div>
        </div>
        <div class="wcsl-data-table-header">
            <form method="GET" class="wcsl-ajax-form" data-target="#employee-detailed-task-log-container">
                <input type="hidden" name="wcsl_view" value="month_details_tasks_table"><input type="hidden" name="wcsl_month" value="<?php echo esc_attr( $current_month ); ?>"><input type="hidden" name="wcsl_year" value="<?php echo esc_attr( $current_year ); ?>">
                <div class="wcsl-data-table-controls">
                    <?php
                    $clients = get_posts(array('post_type' => 'client', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                    $client_options = array(); foreach ($clients as $client) { $client_options[$client->ID] = $client->post_title; }
                    $employees = get_posts(array('post_type' => 'employee', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                    $employee_options = array(); foreach ($employees as $employee) { $employee_options[$employee->ID] = $employee->post_title; }
                    $status_options = array('pending' => 'Pending', 'in-progress' => 'In Progress', 'in-review' => 'In Review', 'completed' => 'Completed', 'billed' => 'Billed');
                    $type_options = array('support' => 'Support', 'fixing' => 'Fixing');
                    wcsl_display_portal_task_filter_dropdown('filter_client', $client_options, 'All Clients', $filter_client_id);
                    wcsl_display_portal_task_filter_dropdown('filter_employee', $employee_options, 'All Employees', $filter_employee_id);
                    wcsl_display_portal_task_filter_dropdown('filter_status', $status_options, 'All Statuses', $filter_status);
                    wcsl_display_portal_task_filter_dropdown('filter_task_type', $type_options, 'All Task Types', $filter_task_type);
                    ?>
                    <div class="wcsl-search-group">
                        <input type="search" name="search_tasks" value="<?php echo esc_attr( $search_term_tasks ); ?>" placeholder="<?php esc_attr_e('Search Tasks...', 'wp-client-support-ledger'); ?>" />
                        <input type="submit" class="wcsl-portal-button" value="<?php esc_attr_e('Filter', 'wp-client-support-ledger'); ?>" />
                    </div>
                    <?php if ( $is_filtered ) : ?><a href="<?php echo esc_url( add_query_arg( array('wcsl_view' => 'month_details_tasks_table', 'wcsl_month' => $current_month, 'wcsl_year' => $current_year), $portal_page_url ) ); ?>" class="wcsl-portal-button-clear wcsl-ajax-load-main"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a><?php endif; ?>
                </div>
            </form>
        </div>
        <div id="employee-detailed-task-log-container" class="wcsl-ajax-container">
            <?php wcsl_render_employee_month_details_tasks_table(); ?>
        </div>
    </div>
    <?php
}

/**
 * *** NEW ***
 * Renders ONLY the Client Summary table for the Month Details page.
 */
function wcsl_render_employee_month_details_summary_table() {
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    $current_month = isset( $_GET['wcsl_month'] ) ? intval( $_GET['wcsl_month'] ) : date('n');
    $current_year  = isset( $_GET['wcsl_year'] ) ? intval( $_GET['wcsl_year'] ) : date('Y');
    $search_term_summary = isset( $_GET['search_summary'] ) ? sanitize_text_field( $_GET['search_summary'] ) : '';
    $clients_per_page = 5;
    $paged_clients = isset( $_GET['paged_clients'] ) ? max( 1, intval( $_GET['paged_clients'] ) ) : 1;

    $clients_query_args = array('post_type' => 'client', 'posts_per_page' => $clients_per_page, 'paged' => $paged_clients, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish');
    if ( !empty($search_term_summary) ) { $clients_query_args['s'] = $search_term_summary; }
    $clients_query = new WP_Query( $clients_query_args );

    if ( $clients_query->have_posts() ) : ?>
        <table class="wcsl-portal-table">
            <thead><tr><th>Client</th><th>Contracted</th><th>Total Spent</th><th>Fixing</th><th>Billable</th></tr></thead>
            <tbody>
                <?php while ( $clients_query->have_posts() ) : $clients_query->the_post();
                    $client_id = get_the_ID();
                    $client_summary_data = wcsl_get_single_client_summary_for_month( $client_id, $current_month, $current_year );
                    $billable_class = $client_summary_data['billable_minutes'] > 0 ? 'billable' : '';
                ?>
                <tr>
                    <td><strong><?php echo esc_html( get_the_title( $client_id ) ); ?></strong></td>
                    <td><?php echo esc_html( $client_summary_data['contracted_str'] ); ?></td>
                    <td><?php echo esc_html( $client_summary_data['total_spent_str'] ); ?></td>
                    <td><?php echo esc_html( $client_summary_data['fixing_str'] ); ?></td>
                    <td class="<?php echo esc_attr( $billable_class ); ?>"><?php echo esc_html( $client_summary_data['billable_str'] ); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
        $total_client_pages = $clients_query->max_num_pages;
        if ($total_client_pages > 1){
            $base_url_args = $_GET; 
            $base_url_args['wcsl_view'] = 'month_details_summary_table'; // Target this specific table
            unset($base_url_args['paged_clients']);
            $client_pagination_base = add_query_arg( 'paged_clients', '%#%', add_query_arg($base_url_args, $portal_page_url) );
            echo '<div class="wcsl-pagination wcsl-ajax-pagination">' . paginate_links(array('base' => $client_pagination_base, 'format' => '', 'current' => $paged_clients, 'total' => $total_client_pages)) . '</div>';
        }
        wp_reset_postdata();
    else :
        echo '<p class="wcsl-panel-notice">' . esc_html__( 'No clients found.', 'wp-client-support-ledger' ) . '</p>';
    endif;
}

/**
 * *** NEW ***
 * Renders ONLY the Detailed Tasks table for the Month Details page.
 */
function wcsl_render_employee_month_details_tasks_table() {
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    $current_month = isset( $_GET['wcsl_month'] ) ? intval( $_GET['wcsl_month'] ) : date('n');
    $current_year  = isset( $_GET['wcsl_year'] ) ? intval( $_GET['wcsl_year'] ) : date('Y');
    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $current_month, 1, $current_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $current_month + 1, 0, $current_year ) );
    $search_term_tasks   = isset( $_GET['search_tasks'] ) ? sanitize_text_field( $_GET['search_tasks'] ) : '';
    $filter_client_id    = isset( $_GET['filter_client'] ) ? intval( $_GET['filter_client'] ) : 0;
    $filter_employee_id  = isset( $_GET['filter_employee'] ) ? intval( $_GET['filter_employee'] ) : 0;
    $filter_status       = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : '';
    $filter_task_type    = isset( $_GET['filter_task_type'] ) ? sanitize_key( $_GET['filter_task_type'] ) : '';
    $tasks_per_page   = 10;
    $paged_tasks      = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

    $meta_query = array('relation' => 'AND', array('key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE' ));
    if ( $filter_client_id > 0 ) { $meta_query[] = array('key' => '_wcsl_related_client_id', 'value' => $filter_client_id); }
    if ( $filter_employee_id > 0 ) { $meta_query[] = array('key' => '_wcsl_assigned_employee_id', 'value' => $filter_employee_id); }
    if ( !empty($filter_status) ) { $meta_query[] = array('key' => '_wcsl_task_status', 'value' => $filter_status); }
    if ( !empty($filter_task_type) ) { $meta_query[] = array('key' => '_wcsl_task_type', 'value' => $filter_task_type); }
    $all_tasks_args = array('post_type' => 'client_task', 'posts_per_page' => $tasks_per_page, 'paged' => $paged_tasks, 'post_status' => 'publish', 's' => $search_term_tasks, 'meta_query' => $meta_query);
    $all_tasks_query = new WP_Query( $all_tasks_args );
    
    if ( $all_tasks_query->have_posts() ) : ?>
        <table class="wcsl-portal-table">
            <thead><tr><th>Date</th><th>Client</th><th>Task Title</th><th>Task Link</th><th>Hours</th><th>Status</th><th>Employee</th><th>Attachment</th><th>Note</th></tr></thead>
            <tbody>
                <?php while ( $all_tasks_query->have_posts() ) : $all_tasks_query->the_post();
                    $task_id = get_the_ID();
                    $task_type_class = get_post_meta( $task_id, '_wcsl_task_type', true ) === 'fixing' ? 'type-fixing' : '';
                    $related_client_id = get_post_meta( $task_id, '_wcsl_related_client_id', true );
                    $task_link_url = get_post_meta( $task_id, '_wcsl_task_link', true );
                    $attachment_url = get_post_meta( $task_id, '_wcsl_task_attachment_url', true );
                    $task_note = get_post_meta( $task_id, '_wcsl_task_note', true );
                ?>
                <tr class="<?php echo esc_attr($task_type_class); ?>">
                    <td><?php echo esc_html( get_post_meta( $task_id, '_wcsl_task_date', true ) ); ?></td>
                    <td><?php echo $related_client_id ? esc_html( get_the_title( $related_client_id ) ) : 'N/A'; ?></td>
                    <td><strong><?php the_title(); ?></strong></td>
                    <td><?php if ( ! empty( $task_link_url ) ) : ?><a href="<?php echo esc_url($task_link_url); ?>" target="_blank" rel="noopener">View Task</a><?php else: echo 'N/A'; endif; ?></td>
                    <td><?php echo esc_html( get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true ) ); ?></td>
                    <td><?php if (function_exists('wcsl_display_status_badge')) { wcsl_display_status_badge( get_post_meta( $task_id, '_wcsl_task_status', true ) ); } ?></td>
                    <td><?php echo esc_html( get_post_meta( $task_id, '_wcsl_employee_name', true ) ); ?></td>
                    <td><?php if ( ! empty( $attachment_url ) ) : ?><a href="<?php echo esc_url($attachment_url); ?>" target="_blank" rel="noopener">View</a><?php else: echo 'N/A'; endif; ?></td>
                    <td><?php echo nl2br( esc_html( $task_note ) ); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
        $total_task_pages = $all_tasks_query->max_num_pages;
        if ($total_task_pages > 1){
            $base_url_args = $_GET;
            $base_url_args['wcsl_view'] = 'month_details_tasks_table'; // Target this specific table
            unset($base_url_args['paged']);
            $task_pagination_base = add_query_arg( 'paged', '%#%', add_query_arg($base_url_args, $portal_page_url) );
            echo '<div class="wcsl-pagination wcsl-ajax-pagination">' . paginate_links(array('base' => $task_pagination_base, 'format' => '', 'current' => $paged_tasks, 'total' => $total_task_pages)) . '</div>';
        }
        wp_reset_postdata();
    else :
        echo '<p class="wcsl-panel-notice">' . esc_html__( 'No tasks found.', 'wp-client-support-ledger' ) . '</p>';
    endif;
}

/**
 * Renders the "Add Task" Form for Employees
 */
function wcsl_render_employee_add_task_form() {
    $statuses = array('pending' => 'Pending', 'in-progress' => 'In Progress', 'in-review' => 'In Review', 'completed' => 'Completed', 'billed' => 'Billed');
    $task_types = array('support' => 'Support', 'fixing'  => 'Fixing');
    $clients_query = new WP_Query( array('post_type' => 'client', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC') );
    $employees_query = new WP_Query( array('post_type' => 'employee', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC') );

    $current_user = wp_get_current_user();
    $logged_in_employee_cpt_id = 0; // Default to 0
    $is_admin = in_array( 'administrator', (array) $current_user->roles );

    // *** FIX: Only find the linked CPT ID if the user is a Ledger Employee ***
    if ( in_array( 'wcsl_employee', (array) $current_user->roles ) ) {
        $logged_in_employee_cpt_id = wcsl_get_employee_id_for_user( $current_user->ID );
    }
    ?>
    <div class="wcsl-add-task-wrap">
        <div class="wcsl-portal-section">
            <form id="wcsl-frontend-add-task-form">
                <?php wp_nonce_field( 'wcsl_add_task_action', 'wcsl_add_task_nonce' ); ?>
                
                <table class="form-table">
                     <tbody>
                        <tr>
                            <th><label for="wcsl_task_title">Task Title <span class="required">*</span></label></th>
                            <td><input type="text" id="wcsl_task_title" name="wcsl_task_title" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_type">Task Type <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_task_type" name="wcsl_task_type" required>
                                    <option value="" disabled selected>-- Select a Type --</option>
                                    <?php foreach ( $task_types as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr id="wcsl_task_category_row" style="display: none;">
                            <th><label for="wcsl_task_category">Task Category</label></th>
                            <td>
                                <select id="wcsl_task_category" name="wcsl_task_category"></select>
                                <span class="spinner" style="vertical-align: middle; float: none; display: none;"></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_date">Task Date <span class="required">*</span></label></th>
                            <td><input type="date" id="wcsl_task_date" name="wcsl_task_date" value="<?php echo date('Y-m-d'); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_hours_spent_on_task">Hours Spent <span class="required">*</span></label></th>
                            <td><input type="text" id="wcsl_hours_spent_on_task" name="wcsl_hours_spent_on_task" placeholder="e.g., 1h 30m" required></td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_status">Task Status <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_task_status" name="wcsl_task_status" required>
                                    <?php foreach ( $statuses as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_link">Task Link (URL) <span class="required">*</span></label></th>
                            <td><input type="url" id="wcsl_task_link" name="wcsl_task_link" class="regular-text" placeholder="https://example.com/task/123" required></td>
                        </tr>
                         <tr>
                            <th><label for="wcsl_assigned_employee_id">Assigned Employee <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_assigned_employee_id" name="wcsl_assigned_employee_id" required>
                                    <option value="" disabled <?php selected( $logged_in_employee_cpt_id, 0 ); ?>>-- Select Employee --</option>
                                    <?php if ( $employees_query->have_posts() ) : while ( $employees_query->have_posts() ) : $employees_query->the_post(); 
                                        $current_employee_id = get_the_ID();
                                        $is_disabled = ( ! $is_admin && $logged_in_employee_cpt_id !== $current_employee_id );
                                    ?>
                                        <option value="<?php echo esc_attr( $current_employee_id ); ?>" 
                                            <?php selected( $logged_in_employee_cpt_id, $current_employee_id ); ?>
                                            <?php if ( $is_disabled ) echo 'disabled="disabled"'; ?>>
                                            <?php echo esc_html( get_the_title() ); ?>
                                        </option>
                                    <?php endwhile; wp_reset_postdata(); endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_related_client_id">Related Client <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_related_client_id" name="wcsl_related_client_id" required>
                                    <option value="" disabled selected>-- Select Client --</option>
                                    <?php if ( $clients_query->have_posts() ) : while ( $clients_query->have_posts() ) : $clients_query->the_post(); ?>
                                        <option value="<?php echo esc_attr( get_the_ID() ); ?>"><?php echo esc_html( get_the_title() ); ?></option>
                                    <?php endwhile; wp_reset_postdata(); endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_note">Task Note</label></th>
                            <td><textarea id="wcsl_task_note" name="wcsl_task_note" rows="4" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label>Attachment</label></th>
                            <td>
                                <input type="hidden" id="wcsl_task_attachment_url" name="wcsl_task_attachment_url" value="">
                                <button type="button" class="button" id="wcsl_upload_attachment_button">Select Attachment</button>
                                <button type="button" class="button button-secondary" id="wcsl_remove_attachment_button" style="display:none;">Remove Attachment</button>
                                <div id="wcsl_attachment_preview" style="margin-top: 10px;"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div id="wcsl-add-task-messages" class="wcsl-form-messages"></div>
                <p class="submit">
                    <input type="submit" name="wcsl_add_task_submit" class="wcsl-portal-button" value="Submit Task">
                </p>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Renders the "All Tasks" Page for Employees with AJAX search.
 */
function wcsl_render_employee_all_tasks_page() {
    ?>
    <script>
        document.getElementById('wcsl-dynamic-page-title').innerHTML = '<h2 class="wcsl-page-title"><?php esc_html_e( 'All Tasks', 'wp-client-support-ledger' ); ?></h2>';
    </script>
    <div class="wcsl-all-tasks-wrap">
        <div id="all-tasks-ajax-content" class="wcsl-ajax-container">
            <?php wcsl_render_all_tasks_table(); ?>
        </div>
    </div>
    <?php
}

/**
 * Renders ONLY the table part of the "All Tasks" page, for initial load and AJAX refresh.
 */
function wcsl_render_all_tasks_table() {
    $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $search_term = isset( $_GET['search_tasks'] ) ? sanitize_text_field( $_GET['search_tasks'] ) : '';
    $task_args = array( 'post_type' => 'client_task', 'posts_per_page' => 10, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC' );
    if ( ! empty( $search_term ) ) { $task_args['s'] = $search_term; }
    $tasks_query = new WP_Query( $task_args );
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    ?>
    <div class="wcsl-data-table-wrapper">
        <div class="wcsl-data-table-header">
            <h3 class="wcsl-data-table-title"><?php esc_html_e( 'All System Tasks', 'wp-client-support-ledger' ); ?></h3>
            <div class="wcsl-data-table-controls">
                <form method="GET" class="wcsl-ajax-form" data-target="#all-tasks-ajax-content">
                    <input type="hidden" name="wcsl_view" value="all_tasks">
                    <div class="wcsl-search-group">
                        <input type="search" name="search_tasks" class="wcsl-ajax-search-box" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e('Search All Tasks...', 'wp-client-support-ledger'); ?>" />
                        <input type="submit" class="wcsl-portal-button" value="<?php esc_attr_e('Search', 'wp-client-support-ledger'); ?>" />
                    </div>
                    <?php if ( ! empty( $search_term ) ) : ?>
                        <a href="<?php echo esc_url( add_query_arg('wcsl_view', 'all_tasks', $portal_page_url) ); ?>" class="wcsl-portal-button-clear wcsl-ajax-load-main"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if ( $tasks_query->have_posts() ) : ?>
            <table class="wcsl-portal-table">
                 <thead><tr><th>Date</th><th>Task Title</th><th>Client</th><th>Assigned To</th><th>Hours</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while ( $tasks_query->have_posts() ) : $tasks_query->the_post(); ?>
                        <tr class="<?php echo get_post_meta( get_the_ID(), '_wcsl_task_type', true ) === 'fixing' ? 'type-fixing' : ''; ?>">
                            <td><?php echo get_the_date(); ?></td>
                            <td><strong><?php the_title(); ?></strong></td>
                            <td><?php echo esc_html( get_the_title( get_post_meta( get_the_ID(), '_wcsl_related_client_id', true ) ) ?: 'N/A' ); ?></td>
                            <td><?php echo esc_html( get_post_meta( get_the_ID(), '_wcsl_employee_name', true ) ?: 'N/A' ); ?></td>
                            <td><?php echo esc_html( get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true ) ?: '0m' ); ?></td>
                            <td><?php wcsl_display_status_badge( get_post_meta( get_the_ID(), '_wcsl_task_status', true ) ); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php
            $total_pages = $tasks_query->max_num_pages;
            if ( $total_pages > 1 ) {
                echo '<div class="wcsl-data-table-footer">';
                $base_url_args = array('wcsl_view' => 'all_tasks');
                if ( ! empty( $search_term ) ) { $base_url_args['search_tasks'] = $search_term; }
                $pagination_base = add_query_arg( 'paged', '%#%', add_query_arg($base_url_args, $portal_page_url) );
                echo '<div class="wcsl-pagination wcsl-ajax-pagination">';
                echo paginate_links( array( 'base' => $pagination_base, 'format' => '', 'current' => $paged, 'total' => $total_pages, 'prev_text' => '« Previous', 'next_text' => 'Next »' ) );
                echo '</div></div>';
            }
            ?>
        <?php else : ?>
            <p class="wcsl-panel-notice"><?php esc_html_e( 'No tasks found.', 'wp-client-support-ledger' ); ?></p>
        <?php endif; wp_reset_postdata(); ?>
    </div>
    <?php
}

/**
 * Renders the "My Tasks" Page for Employees with AJAX search.
 */
function wcsl_render_employee_my_tasks_page() {
    ?>
    <div class="wcsl-my-tasks-wrap">
        <div id="my-tasks-ajax-content" class="wcsl-ajax-container">
            <?php wcsl_render_my_tasks_table(); ?>
        </div>
    </div>
    <?php
}

/**
 * Renders ONLY the table part of the "My Tasks" page, for initial load and AJAX refresh.
 */
function wcsl_render_my_tasks_table() {
    $current_user = wp_get_current_user();
    $employee_cpt_id = wcsl_get_employee_id_for_user( $current_user->ID );
    if ( ! $employee_cpt_id ) {
        echo '<p class="wcsl-panel-notice">' . esc_html__( 'Your user account is not linked to an employee profile.', 'wp-client-support-ledger' ) . '</p>';
        return;
    }

    $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $search_term = isset( $_GET['search_tasks'] ) ? sanitize_text_field( $_GET['search_tasks'] ) : '';
    $task_args = array( 'post_type' => 'client_task', 'posts_per_page' => 20, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => array( array( 'key' => '_wcsl_assigned_employee_id', 'value' => $employee_cpt_id ) ) );
    if ( ! empty( $search_term ) ) { $task_args['s'] = $search_term; }

    $tasks_query = new WP_Query( $task_args );
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    ?>
    <div class="wcsl-data-table-wrapper">
        <div class="wcsl-data-table-header">
            <h3 class="wcsl-data-table-title"><?php esc_html_e( 'My Assigned Tasks', 'wp-client-support-ledger' ); ?></h3>
            <div class="wcsl-data-table-controls">
                <?php if ( $tasks_query->have_posts() ) {
                    $nonce_action = 'wcsl_print_my_tasks_action_' . $employee_cpt_id;
                    $print_nonce = wp_create_nonce( $nonce_action );
                    $print_url = add_query_arg( array( 'action' => 'wcsl_print_employee_my_tasks', 'employee_id' => $employee_cpt_id, '_wpnonce' => $print_nonce, ), admin_url( 'admin-post.php' ) );
                    echo '<a href="' . esc_url( $print_url ) . '" class="wcsl-portal-button" target="_blank">' . esc_html__( 'Print / Save as PDF', 'wp-client-support-ledger' ) . '</a>';
                } ?>
                <form method="GET" class="wcsl-ajax-form" data-target="#my-tasks-ajax-content">
                    <input type="hidden" name="wcsl_view" value="my_tasks">
                     <div class="wcsl-search-group">
                        <input type="search" name="search_tasks" class="wcsl-ajax-search-box" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e('Search My Tasks...', 'wp-client-support-ledger'); ?>" />
                        <input type="submit" class="wcsl-portal-button" value="<?php esc_attr_e('Search', 'wp-client-support-ledger'); ?>" />
                    </div>
                    <?php if ( ! empty( $search_term ) ) : ?>
                        <a href="<?php echo esc_url( add_query_arg('wcsl_view', 'my_tasks', $portal_page_url) ); ?>" class="wcsl-portal-button-clear wcsl-ajax-load-main"><?php esc_html_e('Clear', 'wp-client-support-ledger'); ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ( $tasks_query->have_posts() ) : ?>
            <table class="wcsl-portal-table">
                <thead><tr><th>Date</th><th>Task Title</th><th>Client</th><th>Task Link</th><th>Hours Spent</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
                        $task_id = get_the_ID();
                        $edit_url = add_query_arg( array('wcsl_view' => 'edit_task', 'task_id' => $task_id), $portal_page_url );
                        $delete_nonce = wp_create_nonce( 'wcsl_delete_task_' . $task_id );
                    ?>
                        <tr class="<?php echo get_post_meta( $task_id, '_wcsl_task_type', true ) === 'fixing' ? 'type-fixing' : ''; ?>">
                            <td><?php echo get_the_date(); ?></td>
                            <td><strong><?php the_title(); ?></strong></td>
                            <td><?php echo esc_html( get_the_title( get_post_meta( $task_id, '_wcsl_related_client_id', true ) ) ?: 'N/A' ); ?></td>
                            <td><?php $task_link_url = get_post_meta( $task_id, '_wcsl_task_link', true ); if ( ! empty( $task_link_url ) ) : ?><a href="<?php echo esc_url($task_link_url); ?>" target="_blank" rel="noopener">View Task</a><?php else: echo 'N/A'; endif; ?></td>
                            <td><?php echo esc_html( get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true ) ?: '0m' ); ?></td>
                            <td><?php wcsl_display_status_badge( get_post_meta( $task_id, '_wcsl_task_status', true ) ); ?></td>
                            <td class="actions">
                                <a href="<?php echo esc_url($edit_url); ?>" class="wcsl-action-link edit wcsl-ajax-load-main"><?php esc_html_e( 'Edit', 'wp-client-support-ledger' ); ?></a>
                                <span class="action-divider">|</span>
                                <a href="#" class="wcsl-action-link delete" data-task-id="<?php echo esc_attr($task_id); ?>" data-nonce="<?php echo esc_attr($delete_nonce); ?>"><?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php
            $total_pages = $tasks_query->max_num_pages;
            if ( $total_pages > 1 ) {
                echo '<div class="wcsl-data-table-footer">';
                $base_url_args = array('wcsl_view' => 'my_tasks');
                if ( ! empty( $search_term ) ) { $base_url_args['search_tasks'] = $search_term; }
                $pagination_base = add_query_arg( 'paged', '%#%', add_query_arg($base_url_args, $portal_page_url) );
                echo '<div class="wcsl-pagination wcsl-ajax-pagination">';
                echo paginate_links( array( 'base' => $pagination_base, 'format' => '', 'current' => $paged, 'total' => $total_pages, 'prev_text' => '« Previous', 'next_text' => 'Next »' ) );
                echo '</div></div>';
            }
            ?>
        <?php else : ?>
            <p class="wcsl-panel-notice"><?php esc_html_e( 'No tasks found.', 'wp-client-support-ledger' ); ?></p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
    <?php
}



/**
 * Renders the "Edit Task" Form for Employees
 */
function wcsl_render_employee_edit_task_form( $task_id ) {
    $post = get_post( $task_id );
    $current_user = wp_get_current_user();
    $employee_cpt_id = wcsl_get_employee_id_for_user( $current_user->ID );
    $task_assignee_id = (int) get_post_meta( $task_id, '_wcsl_assigned_employee_id', true );

    if ( ! $post || 'client_task' !== $post->post_type || $employee_cpt_id !== $task_assignee_id ) {
        echo '<p class="wcsl-panel-notice error">' . esc_html__( 'Error: The requested task could not be found or you do not have permission to edit it.', 'wp-client-support-ledger' ) . '</p>';
        return;
    }
    
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    $back_to_my_tasks_url = add_query_arg('wcsl_view', 'my_tasks', $portal_page_url);
    $task_type = get_post_meta( $task_id, '_wcsl_task_type', true );
    $task_date = get_post_meta( $task_id, '_wcsl_task_date', true );
    $hours_spent = get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true );
    $task_status = get_post_meta( $task_id, '_wcsl_task_status', true );
    $task_link = get_post_meta( $task_id, '_wcsl_task_link', true );
    $assigned_employee_id = get_post_meta( $task_id, '_wcsl_assigned_employee_id', true );
    $related_client_id = get_post_meta( $task_id, '_wcsl_related_client_id', true );
    $task_note = get_post_meta( $task_id, '_wcsl_task_note', true );
    $attachment_url = get_post_meta( $task_id, '_wcsl_task_attachment_url', true );
    $statuses = array('pending' => 'Pending', 'in-progress' => 'In Progress', 'in-review' => 'In Review', 'completed' => 'Completed', 'billed' => 'Billed');
    $task_types = array('support' => 'Support', 'fixing'  => 'Fixing');
    $clients_query = new WP_Query( array('post_type' => 'client', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC') );
    $employees_query = new WP_Query( array('post_type' => 'employee', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC') );
    ?>
    <div class="wcsl-edit-task-wrap">
        <p><a href="<?php echo esc_url( $back_to_my_tasks_url ); ?>" class="wcsl-back-link wcsl-ajax-load-main">« <?php esc_html_e('Back to My Tasks', 'wp-client-support-ledger'); ?></a></p>
        <h3><?php esc_html_e( 'Edit Task', 'wp-client-support-ledger' ); ?>: <?php echo esc_html($post->post_title); ?></h3>
        <div class="wcsl-portal-section">
            <form id="wcsl-frontend-edit-task-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <input type="hidden" name="action" value="wcsl_frontend_edit_task">
                <input type="hidden" name="task_id" value="<?php echo esc_attr($task_id); ?>">
                <?php wp_nonce_field( 'wcsl_edit_task_' . $task_id, 'wcsl_edit_task_nonce' ); ?>
                <table class="form-table">
                     <tbody>
                        <tr>
                            <th><label for="wcsl_task_title">Task Title <span class="required">*</span></label></th>
                            <td><input type="text" id="wcsl_task_title" name="wcsl_task_title" class="regular-text" value="<?php echo esc_attr($post->post_title); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_type">Task Type <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_task_type" name="wcsl_task_type" required>
                                    <?php foreach ( $task_types as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected($task_type, $value); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr id="wcsl_task_category_row">
                            <th><label for="wcsl_task_category">Task Category</label></th>
                            <td>
                                <select id="wcsl_task_category" name="wcsl_task_category"></select>
                                <span class="spinner" style="vertical-align: middle; float: none; display: none;"></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_date">Task Date <span class="required">*</span></label></th>
                            <td><input type="date" id="wcsl_task_date" name="wcsl_task_date" value="<?php echo esc_attr($task_date); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_hours_spent_on_task">Hours Spent <span class="required">*</span></label></th>
                            <td><input type="text" id="wcsl_hours_spent_on_task" name="wcsl_hours_spent_on_task" value="<?php echo esc_attr($hours_spent); ?>" placeholder="e.g., 1h 30m" required></td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_status">Task Status <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_task_status" name="wcsl_task_status" required>
                                    <?php foreach ( $statuses as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected($task_status, $value); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_link">Task Link (URL) <span class="required">*</span></label></th>
                            <td><input type="url" id="wcsl_task_link" name="wcsl_task_link" class="regular-text" value="<?php echo esc_attr($task_link); ?>" placeholder="https://example.com/task/123" required></td>
                        </tr>
                         <tr>
                            <th><label for="wcsl_assigned_employee_id">Assigned Employee <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_assigned_employee_id" name="wcsl_assigned_employee_id" required>
                                    <option value="" disabled>-- Select Employee --</option>
                                    <?php if ( $employees_query->have_posts() ) : while ( $employees_query->have_posts() ) : $employees_query->the_post(); ?>
                                        <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php selected($assigned_employee_id, get_the_ID()); ?>><?php echo esc_html( get_the_title() ); ?></option>
                                    <?php endwhile; wp_reset_postdata(); endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_related_client_id">Related Client <span class="required">*</span></label></th>
                            <td>
                                <select id="wcsl_related_client_id" name="wcsl_related_client_id" required>
                                    <option value="" disabled>-- Select Client --</option>
                                    <?php if ( $clients_query->have_posts() ) : while ( $clients_query->have_posts() ) : $clients_query->the_post(); ?>
                                        <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php selected($related_client_id, get_the_ID()); ?>><?php echo esc_html( get_the_title() ); ?></option>
                                    <?php endwhile; wp_reset_postdata(); endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wcsl_task_note">Task Note</label></th>
                            <td><textarea id="wcsl_task_note" name="wcsl_task_note" rows="4" class="large-text"><?php echo esc_textarea($task_note); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label>Attachment</label></th>
                            <td>
                                <input type="hidden" id="wcsl_task_attachment_url" name="wcsl_task_attachment_url" value="<?php echo esc_attr($attachment_url); ?>">
                                <button type="button" class="button" id="wcsl_upload_attachment_button">Select Attachment</button>
                                <button type="button" class="button button-secondary" id="wcsl_remove_attachment_button" style="<?php echo empty($attachment_url) ? 'display:none;' : ''; ?>">Remove Attachment</button>
                                <div id="wcsl_attachment_preview" style="margin-top: 10px;"><?php if ( ! empty( $attachment_url ) ) { echo '<img src="' . esc_url( $attachment_url ) . '" style="max-width:200px; height:auto; border:1px solid #ddd;" />'; } ?></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="wcsl_edit_task_submit" class="wcsl-portal-button" value="Update Task">
                </p>
            </form>
        </div>
    </div>
    <?php
}



/**
 * *** NEW ***
 * Renders the Reports page for the Employee Portal.
 */
function wcsl_render_employee_reports_page() {
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
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
    <div class="wcsl-reports-wrap">
        
        <div class="wcsl-card">
            <form method="GET" class="wcsl-reports-filter-form">
                <input type="hidden" name="wcsl_view" value="reports">
                <div class="wcsl-form-controls-wrapper">
                    <label for="wcsl_start_date"><?php esc_html_e('Start Date:', 'wp-client-support-ledger'); ?></label>
                    <input type="date" id="wcsl_start_date" name="start_date" value="<?php echo esc_attr($filter_start_date); ?>" max="<?php echo esc_attr($today); ?>">
                    <label for="wcsl_end_date"><?php esc_html_e('End Date:', 'wp-client-support-ledger'); ?></label>
                    <input type="date" id="wcsl_end_date" name="end_date" value="<?php echo esc_attr($filter_end_date); ?>" max="<?php echo esc_attr($today); ?>">
                    <input type="submit" class="wcsl-portal-button" value="<?php esc_attr_e('Filter Report', 'wp-client-support-ledger'); ?>">
                    <?php if ( $filter_start_date !== $default_start_date || $filter_end_date !== $today ) : 
                        $reset_url = add_query_arg('wcsl_view', 'reports', $portal_page_url);
                    ?>
                        <a href="<?php echo esc_url( $reset_url ); ?>" class="wcsl-portal-button-clear wcsl-ajax-load-main">
                            <?php esc_html_e('Reset', 'wp-client-support-ledger'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="wcsl-reports-grid">
            <div class="wcsl-card wcsl-report-block">
                <h3 class="wcsl-card-title"><?php printf( esc_html__( 'Hours Per Client (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h3>
                <div class="wcsl-chart-container"><canvas id="wcslHoursPerClientChart"></canvas></div>
            </div>
            <div class="wcsl-card wcsl-report-block">
                <h3 class="wcsl-card-title"><?php printf( esc_html__( 'Billable Hours by Client (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h3>
                <div class="wcsl-chart-container"><canvas id="wcslBillableHoursPerClientChart"></canvas></div>
            </div>
            <div class="wcsl-card wcsl-report-block">
                 <h3 class="wcsl-card-title"><?php printf( esc_html__( 'Hours by Employee (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h3>
                <div class="wcsl-chart-container"><canvas id="wcslHoursByEmployeeChart"></canvas></div>
            </div>
            <div class="wcsl-card wcsl-report-block">
                <h3 class="wcsl-card-title"><?php printf( esc_html__( 'Total Billable Hours (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h3>
                <div class="wcsl-data-metric-container">
                    <p class="metric-value" id="total-billable-hours-metric">...</p>
                    <p class="wcsl-metric-description"><?php esc_html_e('Total billable hours for the selected period.', 'wp-client-support-ledger'); ?></p>
                </div>
            </div>
        </div>

        <div class="wcsl-reports-grid-full-width">
            <div class="wcsl-card wcsl-report-block">
                <div class="wcsl-report-header-with-tabs">
                    <h3 class="wcsl-card-title"><?php printf( esc_html__( 'Task Analysis (%s - %s)', 'wp-client-support-ledger' ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_start_date) ) ), esc_html( date_i18n( get_option('date_format'), strtotime($filter_end_date) ) ) ); ?></h3>
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

        <div class="wcsl-reports-grid-full-width">
            <div class="wcsl-card wcsl-report-block">
                <h3 class="wcsl-card-title"><?php esc_html_e( 'Billable Hours Trend (Last 12 Months)', 'wp-client-support-ledger' ); ?></h3>
                <div class="wcsl-chart-container">
                    <canvas id="wcslBillableTrendChart"></canvas>
                </div>
            </div>
        </div>

    </div>
    <?php
}



/**
 * *** NEW ***
 * Renders the Notifications page for the Employee Portal.
 */
function wcsl_render_employee_notifications_page() {
    ?>
    <div class="wcsl-notifications-wrap">
        
        <?php // *** FIX: Wrap the content in our main data table component class *** ?>
        <div class="wcsl-data-table-wrapper">
            <div id="employee-notifications-ajax-content" class="wcsl-ajax-container">
                <?php wcsl_render_employee_notifications_table_content(); // Load the table content via the helper ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * *** NEW HELPER FUNCTION ***
 * Renders ONLY the content of the notifications page (the form and table) for AJAX reloads.
 */
function wcsl_render_employee_notifications_table_content() {
    global $wpdb;
    $table_name = wcsl_get_notifications_table_name(); 
    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    $base_url = add_query_arg('wcsl_view', 'notifications', $portal_page_url);

    $items_per_page = 20; 
    $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset = ( $paged - 1 ) * $items_per_page;

    $where_clause = $wpdb->prepare( "WHERE user_id = %d", 0 );
    $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name} {$where_clause}" );
    $notifications = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", $items_per_page, $offset ) );
    ?>
    <form id="wcsl-employee-notifications-form" method="post">
        <input type="hidden" name="wcsl_view" value="notifications">
        <?php if ($paged > 1) : ?>
            <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>" />
        <?php endif; ?>

        <div class="wcsl-portal-section" style="padding: 15px 20px;">
             <div class="wcsl-bulk-actions-wrapper">
                <select name="wcsl_bulk_action" class="wcsl-bulk-action-select">
                    <option value="-1"><?php esc_html_e( 'Bulk actions' ); ?></option>
                    <option value="bulk_mark_read"><?php esc_html_e( 'Mark Read', 'wp-client-support-ledger' ); ?></option>
                    <option value="bulk_mark_unread"><?php esc_html_e( 'Mark Unread', 'wp-client-support-ledger' ); ?></option>
                    <option value="bulk_delete"><?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></option>
                </select>
                <input type="submit" class="wcsl-portal-button" value="<?php esc_attr_e( 'Apply' ); ?>">
            </div>
        </div>

        <div class="wcsl-portal-section" style="padding:0;">
            <table class="wcsl-portal-table" id="wcsl-employee-notifications-table">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" class="wcsl-select-all-checkbox"></td>
                        <th class="notification-message"><?php esc_html_e( 'Notification', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-client-support-ledger' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $notifications ) ) : ?>
                        <?php foreach ( $notifications as $notification ) : 
                            $action_nonce = wp_create_nonce( 'wcsl_employee_manage_notification_' . $notification->id );
                        ?>
                            <tr id="notification-row-<?php echo esc_attr( $notification->id ); ?>" class="<?php echo $notification->is_read ? 'read' : 'unread'; ?>">
                                <th class="check-column">
                                    <input type="checkbox" name="notification_ids[]" class="wcsl-item-checkbox" value="<?php echo esc_attr( $notification->id ); ?>">
                                </th>
                                <td class="notification-message"><?php echo wp_kses_post( $notification->message ); ?></td>
                                <td><?php echo esc_html( ucwords( str_replace( '_', ' ', $notification->type ) ) ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $notification->created_at ) ) ); ?></td>
                                <td><?php echo ( $notification->is_read == 0 ) ? '<strong>' . esc_html__( 'Unread', 'wp-client-support-ledger' ) . '</strong>' : esc_html__( 'Read', 'wp-client-support-ledger' ); ?></td>
                                <td class="notification-actions">
                                    <?php if ( ! $notification->is_read ) : ?>
                                        <a href="#" class="wcsl-notification-action" data-action="mark_read" data-id="<?php echo esc_attr($notification->id); ?>" data-nonce="<?php echo esc_attr($action_nonce); ?>"><?php esc_html_e( 'Mark Read', 'wp-client-support-ledger' ); ?></a>
                                    <?php else : ?>
                                        <a href="#" class="wcsl-notification-action" data-action="mark_unread" data-id="<?php echo esc_attr($notification->id); ?>" data-nonce="<?php echo esc_attr($action_nonce); ?>"><?php esc_html_e( 'Mark Unread', 'wp-client-support-ledger' ); ?></a>
                                    <?php endif; ?>
                                    <span class="action-divider">|</span>
                                    <a href="#" class="wcsl-notification-action delete" data-action="delete" data-id="<?php echo esc_attr($notification->id); ?>" data-nonce="<?php echo esc_attr($action_nonce); ?>"><?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="no-items"><td class="colspanchange" colspan="6"><?php esc_html_e( 'No notifications found.', 'wp-client-support-ledger' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
             <?php
            $total_pages = ceil( $total_items / $items_per_page );
            if ( $total_pages > 1 ) {
                $pagination_base = add_query_arg( 'paged', '%#%', add_query_arg('wcsl_view', 'notifications_table', $portal_page_url) );
                echo '<div class="wcsl-pagination wcsl-ajax-pagination">';
                // *** FIX: Added prev_text and next_text arguments ***
                echo paginate_links( array( 
                    'base' => $pagination_base, 
                    'format' => '', 
                    'current' => $paged, 
                    'total' => $total_pages, 
                    'prev_text' => __( '« Previous', 'wp-client-support-ledger' ), 
                    'next_text' => __( 'Next »', 'wp-client-support-ledger' ) 
                ) );
                echo '</div>';
            }
            ?>
        </div>
    </form>
    <?php
}