<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Enqueues scripts and styles for the frontend portal page.
 */
function wcsl_enqueue_portal_scripts() {
    // Get the page ID that is designated as our portal page.
    $portal_settings = get_option('wcsl_portal_settings');
    $portal_page_id = isset($portal_settings['portal_page_id']) ? (int) $portal_settings['portal_page_id'] : 0;

    // Only load our scripts on the designated portal page.
    if ( is_page( $portal_page_id ) ) {
        
        $user = wp_get_current_user();
        if ( $user && $user->ID > 0 ) {
            
            // Enqueue assets for the CLIENT
            if ( in_array( 'wcsl_client', (array) $user->roles ) ) {
                wp_enqueue_script( 'wcsl-portal-client-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/portal-client.js', array( 'jquery' ), '1.0.2', true );
                wp_localize_script( 'wcsl-portal-client-js', 'wcsl_client_portal_obj', array( 'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('wcsl_client_portal_nonce') ) );
            }
            
            // Enqueue assets for the EMPLOYEE
            elseif ( in_array( 'wcsl_employee', (array) $user->roles ) ) {
                $current_view = isset( $_GET['wcsl_view'] ) ? sanitize_key( $_GET['wcsl_view'] ) : 'dashboard';

                // --- NEW: Enqueue Toastify library from CDN ---
                wp_enqueue_style( 'toastify-css', 'https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css', array(), '1.12.0' );
                wp_enqueue_script( 'toastify-js', 'https://cdn.jsdelivr.net/npm/toastify-js', array(), '1.12.0', true );

                // --- ALWAYS LOAD ALL EMPLOYEE SCRIPTS ---
                // MODIFIED: Added 'toastify-js' as a dependency for our main script
                wp_enqueue_script( 'wcsl-portal-employee-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/portal-employee.js', array( 'jquery', 'toastify-js' ), '1.0.4', true );
                wp_localize_script( 'wcsl-portal-employee-js', 'wcsl_employee_portal_obj', array( 'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('wcsl_employee_portal_nonce') ) );

                wp_enqueue_media();
                wp_enqueue_script( 'wcsl-portal-add-task-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/portal-add-task.js', array( 'jquery' ), '1.0.2', true );
                
                wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', true);
                wp_enqueue_script('wcsl-portal-reports-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/portal-reports.js', array( 'jquery', 'chartjs' ), '1.0.1', true);

                // --- LOCALIZE SCRIPT DATA CONDITIONALLY ---
                
                $task_id_for_js = ('edit_task' === $current_view && isset($_GET['task_id'])) ? intval($_GET['task_id']) : 0;
                wp_localize_script('wcsl-portal-add-task-js', 'wcsl_add_task_obj', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('wcsl_get_task_categories_nonce'),
                    'post_id'  => $task_id_for_js
                ));
                
                if ( 'reports' === $current_view ) {
                    $today_for_reports = current_time('Y-m-d');
                    $default_start_for_reports = date('Y-m-d', strtotime('-29 days', strtotime($today_for_reports)));
                    $report_start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : $default_start_for_reports;
                    $report_end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : $today_for_reports;
                    if (strtotime($report_end_date) < strtotime($report_start_date)) { $report_end_date = $report_start_date; }

                    $chart_data = array(
                        'hoursPerClient'      => wcsl_get_hours_per_client_for_period( $report_start_date, $report_end_date ),
                        'totalBillableHours'  => array( 'value_string' => wcsl_format_minutes_to_time_string( wcsl_get_total_billable_minutes_for_period( $report_start_date, $report_end_date ) ) ),
                        'billablePerClient'   => wcsl_get_billable_summary_per_client_for_period( $report_start_date, $report_end_date ),
                        'hoursByEmployee'     => wcsl_get_hours_by_employee_for_period( $report_start_date, $report_end_date ),
                        'billableTrend'       => wcsl_get_billable_hours_for_past_months( 12 ),
                        'supportTaskAnalysis' => wcsl_get_task_count_by_category('support', $report_start_date, $report_end_date),
                        'fixingTaskAnalysis'  => wcsl_get_task_count_by_category('fixing', $report_start_date, $report_end_date),
                        'chartColors'         => array('rgba(57, 97, 140, 0.7)', 'rgba(91, 192, 222, 0.7)', 'rgba(240, 173, 78, 0.7)', 'rgba(92, 184, 92, 0.7)', 'rgba(217, 83, 79, 0.7)'),
                        'chartBorderColors'   => array('rgb(57, 97, 140)', 'rgb(91, 192, 222)', 'rgb(240, 173, 78)', 'rgb(92, 184, 92)', 'rgb(217, 83, 79)'),
                        'i18n' => array(
                            'hoursLabel' => esc_js(__( 'Hours', 'wp-client-support-ledger' )),
                            'tasksLabel' => esc_js(__( 'Number of Tasks', 'wp-client-support-ledger' ))
                        )
                    );
                    wp_localize_script('wcsl-portal-reports-js', 'wcsl_report_data_obj', $chart_data);
                }
            }
        }
    }
}
add_action( 'wp_enqueue_scripts', 'wcsl_enqueue_portal_scripts' );

/**
 * ===================================================================
 * Main Portal Shortcode and Content Rendering
 * ===================================================================
 */

/**
 * The main portal shortcode.
 * This function validates if the shortcode is on the correct page.
 */
function wcsl_portal_shortcode_cb( $atts ) {
    // We only want the shortcode to render its content on the specific page assigned in the settings.
    $portal_settings = get_option( 'wcsl_portal_settings', array() );
    $settings_portal_page_id = isset( $portal_settings['portal_page_id'] ) ? (int) $portal_settings['portal_page_id'] : 0;

    // First, handle the case where no page is set in settings.
    if ( empty( $settings_portal_page_id ) ) {
        if ( current_user_can('manage_options') ) {
            $settings_url = admin_url( 'admin.php?page=wcsl-settings-help' );
            return sprintf(
                '<div class="wcsl-shortcode-error">' . wp_kses_post( __( '<strong>WCSL Plugin Notice:</strong> The portal page has not been set. Please go to <a href="%s">Ledger Settings</a>.', 'wp-client-support-ledger' ) ) . '</div>',
                esc_url( $settings_url )
            );
        }
        return ''; // Show nothing for non-admins.
    }
    
    // Next, check if the shortcode is on the wrong page.
    if ( get_the_ID() !== $settings_portal_page_id ) {
        if( current_user_can('manage_options') ) {
            $settings_url = admin_url( 'admin.php?page=wcsl-settings-help' );
            return sprintf(
                '<div class="wcsl-shortcode-error">' . wp_kses_post( __( '<strong>WCSL Plugin Notice:</strong> The <code>[wcsl_portal]</code> shortcode is on the wrong page. You have selected "%s" in your <a href="%s">Ledger Settings</a>.', 'wp-client-support-ledger' ) ) . '</div>',
                esc_html( get_the_title( $settings_portal_page_id ) ),
                esc_url( $settings_url )
            );
        }
        return ''; // Return empty for non-admins on the wrong page.
    }
    
    // If we are on the correct page, render the actual portal content.
    return wcsl_render_portal_content();
}
add_shortcode( 'wcsl_portal', 'wcsl_portal_shortcode_cb' );

/**
 * Helper function that contains all the portal rendering logic.
 * This is only called when the shortcode is on the correct page.
 */
function wcsl_render_portal_content() {
    $user = wp_get_current_user();
    ob_start();

    if ( current_user_can( 'manage_options' ) ) {
        echo wcsl_client_support_report_shortcode_cb( array() );
        return ob_get_clean();
    }
    
    wp_enqueue_style( 'wcsl-portal-style', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/portal-style.css', array(), '1.0.6' );
    
    // NOTE: The old inline style block has been moved to the wp_head action in settings.php, so it is removed from here.

    echo '<div class="wcsl-portal-wrapper">';

    if ( ! is_user_logged_in() ) {
        wcsl_display_portal_login_form();
    } 
    else {
        // --- NEW: Handle the success message for task creation ---
        if ( ( in_array( 'wcsl_employee', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) && isset( $_GET['task_added'] ) && $_GET['task_added'] === 'true' ) {
            $toast_message = esc_js( __( 'Task successfully created!', 'wp-client-support-ledger' ) );
            $inline_script = "
                document.addEventListener('DOMContentLoaded', function() {
                    Toastify({
                        text: '{$toast_message}',
                        duration: 5000,
                        close: true,
                        gravity: 'top',
                        position: 'right',
                        backgroundColor: '#5cb85c', // A standard success green
                        stopOnFocus: true
                    }).showToast();
                });
            ";
            // We attach this inline script to our main employee script handle
            wp_add_inline_script( 'wcsl-portal-employee-js', $inline_script );
        }
        // --- END NEW BLOCK ---

        if ( in_array( 'wcsl_client', (array) $user->roles ) ) {
            wcsl_render_client_portal_main( $user );
        } elseif ( in_array( 'wcsl_employee', (array) $user->roles ) ) {
            wcsl_render_employee_portal_main( $user );
        } else {
            echo '<p>You do not have permission to view this content.</p>';
        }
    }
    echo '</div>';
    return ob_get_clean();
}

/**
 * ===================================================================
 * Login Form Handlers
 * ===================================================================
 */

function wcsl_handle_portal_login_submission() {
    if ( isset( $_POST['wcsl_portal_login_submit'] ) ) {
        if ( ! isset( $_POST['_wcsl_portal_login_nonce'] ) || ! wp_verify_nonce( $_POST['_wcsl_portal_login_nonce'], 'wcsl-portal-login-nonce' ) ) { wp_die('Security check failed.'); }
        $creds = array('user_login' => isset( $_POST['log'] ) ? sanitize_user( $_POST['log'] ) : '', 'user_password' => isset( $_POST['pwd'] ) ? $_POST['pwd'] : '', 'remember' => true );
        $user = wp_signon( $creds, is_ssl() );
        $redirect_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
        if ( is_wp_error( $user ) ) { $redirect_url = add_query_arg( 'wcsl_login_error', 'failed', $redirect_url ); }
        wp_safe_redirect( esc_url_raw( $redirect_url ) );
        exit;
    }
}
add_action( 'template_redirect', 'wcsl_handle_portal_login_submission' );

function wcsl_display_portal_login_form() {
    // Get Employee Portal settings
    $portal_settings = get_option('wcsl_portal_settings', array());
    $main_bg = isset($portal_settings['emp_main_bg']) ? $portal_settings['emp_main_bg'] : '#CCD8D6';
    $primary_color = isset($portal_settings['emp_primary_color']) ? $portal_settings['emp_primary_color'] : '#3E624D';

    // --- NEW: Automatically determine the best contrasting color for text and icons ---
    $text_or_icon_color = wcsl_get_contrasting_text_color( $primary_color );
    
    // --- NEW: Set the CSS filter style for the icon based on the contrast color ---
    // If the contrast color is white, we invert the icon. If black, we just make it black.
    $icon_filter_style = ($text_or_icon_color === '#FFFFFF') ? 'brightness(0) invert(1)' : 'brightness(0)';
    ?>
    <div class="wcsl-login-page-wrapper" style="background-color: <?php echo esc_attr($main_bg); ?>;">
        <div class="wcsl-login-form-container">

            <div class="wcsl-login-icon-wrapper" style="background-color: <?php echo esc_attr($primary_color); ?>;">
                <!-- MODIFIED: Added inline style for the filter -->
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/people.png' ); ?>" alt="" style="filter: <?php echo esc_attr($icon_filter_style); ?>;">
            </div>

            <?php if ( isset( $_GET['wcsl_login_error'] ) ) : ?>
                <div class="wcsl-login-error"><?php esc_html_e( 'Login failed. Please check your username and password and try again.', 'wp-client-support-ledger' ); ?></div>
            <?php endif; ?>

            <form name="wcsl_portalloginform" method="post">
                <div class="wcsl-login-input-group">
                    <img class="input-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/user.png' ); ?>" alt="">
                    <input type="text" name="log" id="wcsl_user_login" class="input" value="" size="20" placeholder="<?php esc_attr_e( 'Username or Email', 'wp-client-support-ledger' ); ?>" />
                </div>
                <div class="wcsl-login-input-group">
                    <img class="input-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/padlock.png' ); ?>" alt="">
                    <input type="password" name="pwd" id="wcsl_user_pass" class="input" value="" size="20" placeholder="<?php esc_attr_e( 'Password', 'wp-client-support-ledger' ); ?>" />
                </div>
                <p class="wcsl-login-submit">
                    <!-- MODIFIED: Added inline style for both background and text color -->
                    <input type="submit" name="wcsl_portal_login_submit" class="wcsl-login-button" value="<?php esc_attr_e( 'Log In', 'wp-client-support-ledger' ); ?>" style="background-color: <?php echo esc_attr($primary_color); ?>; color: <?php echo esc_attr($text_or_icon_color); ?>;" />
                </p>
                <?php wp_nonce_field( 'wcsl-portal-login-nonce', '_wcsl_portal_login_nonce' ); ?>
            </form>
        </div>
        <p class="wcsl-lost-password"><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'wp-client-support-ledger' ); ?></a></p>
    </div>
    <?php
}


/**
 * ===================================================================
 * Legacy Shortcode & Admin View Helpers
 * ===================================================================
 */

function wcsl_client_support_report_shortcode_cb( $atts ) {
    wp_enqueue_style('wcsl-frontend-style', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/frontend-style.css', array(), '1.0.8');
    wp_enqueue_script('wcsl-frontend-report-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/frontend-report.js', array( 'jquery' ), '1.0.1', true);
    wp_localize_script( 'wcsl-frontend-report-js', 'wcsl_frontend_ajax', array('ajax_url' => admin_url( 'admin-ajax.php' ), 'loading_message' => esc_js(__('Loading...', 'wp-client-support-ledger'))));
    $settings = get_option( 'wcsl_email_notification_settings', array() ); 
    $container_bg = !empty( $settings['frontend_container_bg_color'] ) ? $settings['frontend_container_bg_color'] : '#39618C';
    $button_bg = !empty( $settings['frontend_button_bg_color'] ) ? $settings['frontend_button_bg_color'] : '#39618C';
    $button_text = !empty( $settings['frontend_button_text_color'] ) ? $settings['frontend_button_text_color'] : '#FFFFFF';
    $pagination_active_bg = !empty( $settings['frontend_pagination_active_bg'] ) ? $settings['frontend_pagination_active_bg'] : '#2c4a6b';
    $pagination_active_text = !empty( $settings['frontend_pagination_active_text'] ) ? $settings['frontend_pagination_active_text'] : '#FFFFFF';
    $custom_css = ":root { --wcsl-container-bg: ".sanitize_hex_color($container_bg)."; --wcsl-button-bg: ".sanitize_hex_color($button_bg)."; --wcsl-button-text: ".sanitize_hex_color($button_text)."; --wcsl-pagination-active-bg: ".sanitize_hex_color($pagination_active_bg)."; --wcsl-pagination-active-text: ".sanitize_hex_color($pagination_active_text)."; }";
    wp_add_inline_style( 'wcsl-frontend-style', $custom_css );
    return wcsl_render_admin_support_report( $atts );
}
add_shortcode( 'client_support_report', 'wcsl_client_support_report_shortcode_cb' );

function wcsl_render_admin_support_report( $atts ) {
    $atts = shortcode_atts( array('months_per_page' => 10), $atts, 'client_support_report' );
    ob_start();
    $action = isset( $_GET['wcsl_action'] ) ? sanitize_key( $_GET['wcsl_action'] ) : 'view_month_index_frontend';
    $target_month = isset( $_GET['wcsl_month'] ) ? intval( $_GET['wcsl_month'] ) : 0;
    $target_year  = isset( $_GET['wcsl_year'] ) ? intval( $_GET['wcsl_year'] ) : 0;
    $filter_month = isset( $_GET['filter_month'] ) ? intval( $_GET['filter_month'] ) : 0;
    $filter_year  = isset( $_GET['filter_year'] ) ? intval( $_GET['filter_year'] ) : date('Y');
    echo '<div class="wcsl-report-container">';
    if ( 'view_month_details_frontend' === $action && $target_month > 0 && $target_month <=12 && $target_year > 1970 ) {
        wcsl_display_frontend_single_month_details( $target_month, $target_year, $atts );
    } else {
        wcsl_display_frontend_month_index( $atts, $filter_month, $filter_year );
    }
    echo '</div>';
    return ob_get_clean();
}

function wcsl_display_frontend_month_index( $shortcode_atts, $filter_month, $filter_year ) {
    global $wpdb, $wp_locale;
    $months_per_page = isset( $shortcode_atts['months_per_page'] ) ? intval( $shortcode_atts['months_per_page'] ) : 12;
    if ( $months_per_page <= 0 ) $months_per_page = 12;
    $current_paged = isset( $_GET['wcsl_paged'] ) ? intval( $_GET['wcsl_paged'] ) : 1;
    $offset = ( $current_paged - 1 ) * $months_per_page;
    echo '<h2>' . esc_html__( 'Monthly Support Reports', 'wp-client-support-ledger' ) . '</h2>';
    ?>
    <form method="GET" action="<?php echo esc_url( get_permalink() ); ?>" class="wcsl-frontend-filter-form">
        <input type="hidden" name="wcsl_action" value="view_month_index_frontend">
        <div class="wcsl-filter-group">
            <label for="filter_month_idx"><?php esc_html_e( 'Month:', 'wp-client-support-ledger' ); ?></label>
            <select name="filter_month" id="filter_month_idx">
                <option value="0" <?php selected( $filter_month, 0 ); ?>><?php esc_html_e( 'All Months', 'wp-client-support-ledger' ); ?></option>
                <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                    <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $filter_month, $m ); ?>><?php echo esc_html( $wp_locale->get_month( $m ) ); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="wcsl-filter-group">
            <label for="filter_year_idx"><?php esc_html_e( 'Year:', 'wp-client-support-ledger' ); ?></label>
            <select name="filter_year" id="filter_year_idx">
                <?php for ( $y = date('Y') + 2; $y >= date('Y') - 5; $y-- ) : ?>
                    <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $filter_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <input type="submit" value="<?php esc_attr_e( 'Filter Reports', 'wp-client-support-ledger' ); ?>" class="button">
    </form>
    <hr class="wcsl-filter-hr">
    <?php
    $base_query_from = "FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID";
    $base_query_where = $wpdb->prepare( "WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = %s", '_wcsl_task_date', 'client_task', 'publish' );
    $params = array();
    if ( $filter_month > 0 ) { $base_query_where .= " AND MONTH(pm.meta_value) = %d"; $params[] = $filter_month; }
    if ( $filter_year > 0 ) { $base_query_where .= " AND YEAR(pm.meta_value) = %d"; $params[] = $filter_year; }
    $total_months_sql = "SELECT COUNT(DISTINCT CONCAT(YEAR(pm.meta_value), '-', MONTH(pm.meta_value))) " . $base_query_from . " " . $base_query_where;
    $total_unique_months = $wpdb->get_var( !empty($params) ? $wpdb->prepare( $total_months_sql, $params ) : $total_months_sql );
    if ( $total_unique_months == 0 ) { echo '<p>' . esc_html__( 'No monthly data available for the selected filter.', 'wp-client-support-ledger' ) . '</p>'; return; }
    $results_sql = "SELECT DISTINCT YEAR(pm.meta_value) as task_year, MONTH(pm.meta_value) as task_month " . $base_query_from . " " . $base_query_where . " ORDER BY task_year DESC, task_month DESC LIMIT %d OFFSET %d";
    $params[] = $months_per_page; $params[] = $offset;
    $results = $wpdb->get_results( $wpdb->prepare( $results_sql, $params ) );
    if ( empty( $results ) ) { echo '<p>' . esc_html__( 'No monthly data available for the selected filter.', 'wp-client-support-ledger' ) . '</p>'; return; }
    echo '<p>' . esc_html__( 'Please select a month below to view the support summary.', 'wp-client-support-ledger' ) . '</p>';
    echo '<table class="wcsl-frontend-table wcsl-month-index-frontend-table"><thead><tr><th>' . esc_html__( 'Month / Year', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Actions', 'wp-client-support-ledger' ) . '</th></tr></thead><tbody>';
    $current_page_url = get_permalink();
    foreach ( $results as $row ) {
        $loop_year  = intval( $row->task_year ); $loop_month = intval( $row->task_month ); $month_name = $wp_locale->get_month( $loop_month );
        $view_details_url_args = array('wcsl_action' => 'view_month_details_frontend', 'wcsl_month' => $loop_month, 'wcsl_year' => $loop_year, 'filter_month' => $filter_month, 'filter_year' => $filter_year, 'wcsl_paged' => $current_paged);
        $view_details_url = add_query_arg( $view_details_url_args, $current_page_url );
        echo '<tr><td>' . esc_html( $month_name ) . ' ' . esc_html( $loop_year ) . '</td><td><a href="' . esc_url( $view_details_url ) . '" class="button wcsl-view-details-button">' . esc_html__( 'View Details', 'wp-client-support-ledger' ) . '</a></td></tr>';
    }
    echo '</tbody></table>';
    $total_pages = ceil( $total_unique_months / $months_per_page );
    if ( $total_pages > 1 ) {
        $pagination_base_url_args = array('wcsl_action'  => 'view_month_index_frontend', 'filter_month' => $filter_month, 'filter_year'  => $filter_year);
        $pagination_base_url = add_query_arg($pagination_base_url_args, $current_page_url);
        echo '<div class="wcsl-pagination">' . paginate_links( array('base' => add_query_arg( 'wcsl_paged', '%#%', $pagination_base_url ), 'format' => '', 'current' => $current_paged, 'total' => $total_pages, 'prev_text' => __( '« Previous', 'wp-client-support-ledger' ), 'next_text' => __( 'Next »', 'wp-client-support-ledger' ), 'add_args' => false) ) . '</div>';
    }
}

function wcsl_display_frontend_single_month_details( $target_month, $target_year, $shortcode_atts ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $target_month );
    $original_filter_month = isset($_GET['filter_month']) ? intval($_GET['filter_month']) : 0;
    $original_filter_year  = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : date('Y');
    $original_paged_index  = isset($_GET['wcsl_paged']) ? intval($_GET['wcsl_paged']) : 1;
    $back_to_index_args = array( 'wcsl_action'  => 'view_month_index_frontend' );
    if ($original_filter_month > 0) $back_to_index_args['filter_month'] = $original_filter_month;
    if ($original_filter_year !== date('Y') || $original_filter_month > 0) $back_to_index_args['filter_year'] = $original_filter_year;
    if ($original_paged_index > 1) $back_to_index_args['wcsl_paged'] = $original_paged_index;
    $back_to_index_url = add_query_arg( $back_to_index_args, get_permalink() );
    echo '<p><a href="' . esc_url( $back_to_index_url ) . '" class="wcsl-back-to-index">« ' . esc_html__('Back to Report Index', 'wp-client-support-ledger') . '</a></p>';
    echo '<h2>' . sprintf( esc_html__( 'Client Support Summary - %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $target_year ) ) . '</h2>';
    ?>
    <div class="wcsl-frontend-client-search-form">
        <input type="search" id="wcsl_frontend_client_search_input" placeholder="<?php esc_attr_e('Search Clients...', 'wp-client-support-ledger'); ?>" data-month="<?php echo esc_attr($target_month); ?>" data-year="<?php echo esc_attr($target_year); ?>" />
        <span class="spinner" style="display: none; vertical-align: middle; float: none;"></span>
        <?php wp_nonce_field( 'wcsl_frontend_report_nonce_action', 'wcsl_frontend_report_nonce' ); ?>
    </div>
    <?php
    echo '<div id="wcsl_client_summary_ajax_container"><p class="wcsl-loading-message">' . esc_html__('Loading client summary...', 'wp-client-support-ledger') . '</p></div>';
    $print_nonce_action_details = 'wcsl_print_report_action_' . $target_year . '_' . $target_month;
    $print_nonce_details = wp_create_nonce( $print_nonce_action_details );
    $print_url_args_details = array('action' => 'wcsl_generate_print_page', 'month' => $target_month, 'year' => $target_year, '_wpnonce' => $print_nonce_details, 'nonce_action' => $print_nonce_action_details, 'context' => 'frontend');
    $print_url_details = add_query_arg( $print_url_args_details, admin_url( 'admin-post.php' ) );
    ?>
    <div class="wcsl-report-actions"><a href="<?php echo esc_url( $print_url_details ); ?>" class="button wcsl-print-button" target="_blank"><?php esc_html_e( 'Print / Save Summary as PDF', 'wp-client-support-ledger' ); ?></a></div>
    <?php
}



