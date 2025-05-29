<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the [client_support_report] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output for the shortcode.
 */
function wcsl_client_support_report_shortcode_cb( $atts ) {
    $atts = shortcode_atts( array(
        'months_per_page' => 10,
    ), $atts, 'client_support_report' );

    ob_start();

    if ( ! is_user_logged_in() ) {
        wcsl_display_custom_login_form();
    } else {
        $action       = isset( $_GET['wcsl_action'] ) ? sanitize_key( $_GET['wcsl_action'] ) : 'view_month_index_frontend';
        $target_month = isset( $_GET['wcsl_month'] ) ? intval( $_GET['wcsl_month'] ) : 0; // Month for details view
        $target_year  = isset( $_GET['wcsl_year'] ) ? intval( $_GET['wcsl_year'] ) : 0;  // Year for details view

        // For month index filtering
        $filter_month = isset( $_GET['filter_month'] ) ? intval( $_GET['filter_month'] ) : 0; // 0 for 'All Months'
        $filter_year  = isset( $_GET['filter_year'] ) ? intval( $_GET['filter_year'] ) : date('Y'); // Default to current year

        echo '<div class="wcsl-report-container">';

        if ( 'view_month_details_frontend' === $action && $target_month > 0 && $target_month <=12 && $target_year > 1970 ) {
            wcsl_display_frontend_single_month_details( $target_month, $target_year, $atts );
        } else { // Default or view_month_index_frontend
            wcsl_display_frontend_month_index( $atts, $filter_month, $filter_year );
        }

        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode( 'client_support_report', 'wcsl_client_support_report_shortcode_cb' );


/**
 * Displays an index of months with data for the frontend, with pagination and filters.
 *
 * @param array $shortcode_atts Attributes passed to the main shortcode.
 * @param int   $filter_month The month to filter by (0 for all).
 * @param int   $filter_year The year to filter by.
 */
function wcsl_display_frontend_month_index( $shortcode_atts, $filter_month, $filter_year ) {
    global $wpdb, $wp_locale;

    $months_per_page = isset( $shortcode_atts['months_per_page'] ) ? intval( $shortcode_atts['months_per_page'] ) : 12;
    if ( $months_per_page <= 0 ) $months_per_page = 12;

    $current_paged = isset( $_GET['wcsl_paged'] ) ? intval( $_GET['wcsl_paged'] ) : 1;
    $offset = ( $current_paged - 1 ) * $months_per_page;

    echo '<h2>' . esc_html__( 'Monthly Support Reports', 'wp-client-support-ledger' ) . '</h2>';

    // --- Month/Year Selector Form for Index ---
    ?>
    <form method="GET" action="<?php echo esc_url( get_permalink() ); ?>" class="wcsl-frontend-filter-form">
        <input type="hidden" name="wcsl_action" value="view_month_index_frontend"> <?php // Ensure we stay on index ?>
        <div class="wcsl-filter-group">
            <label for="filter_month_idx"><?php esc_html_e( 'Month:', 'wp-client-support-ledger' ); ?></label>
            <select name="filter_month" id="filter_month_idx">
                <option value="0" <?php selected( $filter_month, 0 ); ?>><?php esc_html_e( 'All Months', 'wp-client-support-ledger' ); ?></option>
                <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                    <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $filter_month, $m ); ?>>
                        <?php echo esc_html( $wp_locale->get_month( $m ) ); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="wcsl-filter-group">
            <label for="filter_year_idx"><?php esc_html_e( 'Year:', 'wp-client-support-ledger' ); ?></label>
            <select name="filter_year" id="filter_year_idx">
                <?php for ( $y = date('Y') + 2; $y >= date('Y') - 5; $y-- ) : ?>
                    <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $filter_year, $y ); ?>>
                        <?php echo esc_html( $y ); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <input type="submit" value="<?php esc_attr_e( 'Filter Reports', 'wp-client-support-ledger' ); ?>" class="button">
    </form>
    <hr class="wcsl-filter-hr">
    <?php

    // --- Build SQL Query with Filters ---
    $base_query_from = "FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID";
    $base_query_where = $wpdb->prepare( "WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = %s", '_wcsl_task_date', 'client_task', 'publish' );

    $params = array();
    if ( $filter_month > 0 ) {
        $base_query_where .= " AND MONTH(pm.meta_value) = %d";
        $params[] = $filter_month;
    }
    if ( $filter_year > 0 ) { // Year should always be filtered
        $base_query_where .= " AND YEAR(pm.meta_value) = %d";
        $params[] = $filter_year;
    }

    // Total count query
    $total_months_sql = "SELECT COUNT(DISTINCT CONCAT(YEAR(pm.meta_value), '-', MONTH(pm.meta_value))) " . $base_query_from . " " . $base_query_where;
    if ( !empty($params) ) {
        $total_unique_months = $wpdb->get_var( $wpdb->prepare( $total_months_sql, $params ) );
    } else {
        $total_unique_months = $wpdb->get_var( $total_months_sql );
    }


    if ( $total_unique_months == 0 ) { // Use == for numeric comparison from db
        echo '<p>' . esc_html__( 'No monthly data available for the selected filter.', 'wp-client-support-ledger' ) . '</p>';
        return;
    }

    // Paginated results query
    $results_sql = "SELECT DISTINCT YEAR(pm.meta_value) as task_year, MONTH(pm.meta_value) as task_month " . $base_query_from . " " . $base_query_where . " ORDER BY task_year DESC, task_month DESC LIMIT %d OFFSET %d";
    $params[] = $months_per_page;
    $params[] = $offset;
    $results = $wpdb->get_results( $wpdb->prepare( $results_sql, $params ) );

    if ( empty( $results ) && $current_paged > 1 ) {
         echo '<p>' . esc_html__( 'No more monthly data available.', 'wp-client-support-ledger' ) . '</p>';
    } elseif ( empty( $results ) ) {
         echo '<p>' . esc_html__( 'No monthly data available for the selected filter.', 'wp-client-support-ledger' ) . '</p>';
         return;
    } else {
        echo '<p>' . esc_html__( 'Please select a month below to view the support summary.', 'wp-client-support-ledger' ) . '</p>';

        echo '<table class="wcsl-frontend-table wcsl-month-index-frontend-table">';
        echo '<thead><tr><th>' . esc_html__( 'Month / Year', 'wp-client-support-ledger' ) . '</th><th>' . esc_html__( 'Actions', 'wp-client-support-ledger' ) . '</th></tr></thead>';
        echo '<tbody>';

        $current_page_url = get_permalink();

        foreach ( $results as $row ) {
            $loop_year  = intval( $row->task_year );
            $loop_month = intval( $row->task_month );
            $month_name = $wp_locale->get_month( $loop_month );

            // Preserve current filters and pagination when linking to details
            $view_details_url_args = array(
                'wcsl_action'  => 'view_month_details_frontend',
                'wcsl_month'   => $loop_month,
                'wcsl_year'    => $loop_year,
                'filter_month' => $filter_month, // Preserve filter
                'filter_year'  => $filter_year,  // Preserve filter
                'wcsl_paged'   => $current_paged // Preserve current index page
            );
            $view_details_url = add_query_arg( $view_details_url_args, $current_page_url );

            echo '<tr>';
            echo '<td>' . esc_html( $month_name ) . ' ' . esc_html( $loop_year ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url( $view_details_url ) . '" class="button wcsl-view-details-button">' . esc_html__( 'View Details', 'wp-client-support-ledger' ) . '</a>';
            // PDF button removed from here
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        $total_pages = ceil( $total_unique_months / $months_per_page );
        if ( $total_pages > 1 ) {
            $pagination_base_url_args = array(
                'wcsl_action'  => 'view_month_index_frontend', // Stay on index
                'filter_month' => $filter_month,
                'filter_year'  => $filter_year,
            );
            $pagination_base_url = add_query_arg($pagination_base_url_args, $current_page_url);

            echo '<div class="wcsl-pagination">';
            echo paginate_links( array(
                'base'      => add_query_arg( 'wcsl_paged', '%#%', $pagination_base_url ),
                'format'    => '',
                'current'   => $current_paged,
                'total'     => $total_pages,
                'prev_text' => __( '« Previous', 'wp-client-support-ledger' ),
                'next_text' => __( 'Next »', 'wp-client-support-ledger' ),
                'add_args'  => false,
            ) );
            echo '</div>';
        }
    }
}

/**
 * Displays the Client Summary for a single month on the frontend.
 *
 * @param int   $target_month
 * @param int   $target_year
 * @param array $shortcode_atts Attributes passed to the main shortcode.
 */
function wcsl_display_frontend_single_month_details( $target_month, $target_year, $shortcode_atts ) {
    global $wp_locale;
    $month_name = $wp_locale->get_month( $target_month );

    // For "Back to Report Index" link - preserve original filters/paged state of index
    $original_filter_month = isset($_GET['filter_month']) ? intval($_GET['filter_month']) : 0;
    $original_filter_year  = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : date('Y');
    $original_paged_index  = isset($_GET['wcsl_paged']) ? intval($_GET['wcsl_paged']) : 1;

    $back_to_index_args = array( 'wcsl_action'  => 'view_month_index_frontend' );
    if ($original_filter_month > 0) $back_to_index_args['filter_month'] = $original_filter_month;
    if ($original_filter_year !== date('Y') || $original_filter_month > 0) $back_to_index_args['filter_year'] = $original_filter_year; // Only add if not current year default or if month is filtered
    if ($original_paged_index > 1) $back_to_index_args['wcsl_paged'] = $original_paged_index;
    $back_to_index_url = add_query_arg( $back_to_index_args, get_permalink() );

    echo '<p><a href="' . esc_url( $back_to_index_url ) . '" class="wcsl-back-to-index">« ' . esc_html__('Back to Report Index', 'wp-client-support-ledger') . '</a></p>';

    echo '<h2>' . sprintf( esc_html__( 'Client Support Summary - %s %s', 'wp-client-support-ledger' ), esc_html( $month_name ), esc_html( $target_year ) ) . '</h2>';

    // Search form for Client Summary (will trigger AJAX)
    ?>
    <div class="wcsl-frontend-client-search-form">
        <input type="search" id="wcsl_frontend_client_search_input" 
               placeholder="<?php esc_attr_e('Search Clients...', 'wp-client-support-ledger'); ?>"
               data-month="<?php echo esc_attr($target_month); ?>"
               data-year="<?php echo esc_attr($target_year); ?>" />
        <span class="spinner" style="display: none; vertical-align: middle; float: none;"></span>
        <?php // Nonce for AJAX search/pagination - generated once for the page ?>
        <?php wp_nonce_field( 'wcsl_frontend_report_nonce_action', 'wcsl_frontend_report_nonce' ); ?>
    </div>

    <?php
    // Container for AJAX results (table + pagination)
    echo '<div id="wcsl_client_summary_ajax_container">';
    // We can load the first page of results here via PHP on initial page load,
    // or let AJAX load it. For simplicity with AJAX search, let's have AJAX load it.
    // OR, call a function that generates the initial table content based on default (no search, page 1)
    echo '<p class="wcsl-loading-message">' . esc_html__('Loading client summary...', 'wp-client-support-ledger') . '</p>';
    echo '</div>'; // End #wcsl_client_summary_ajax_container

    // Print/Save PDF Button for THIS specific view
    // This print will now need to be smarter if it's to respect the current client search term
    // For now, it prints ALL clients for the month as before (or filtered by client_id_to_print if that's implemented)
    $specific_client_id_for_report = null; // TODO: Implement if needed
    $print_nonce_action_details = 'wcsl_print_report_action_' . $target_year . '_' . $target_month;
    $print_nonce_details = wp_create_nonce( $print_nonce_action_details );
    $print_url_args_details = array(
        'action'       => 'wcsl_generate_print_page',
        'month'        => $target_month,
        'year'         => $target_year,
        '_wpnonce'     => $print_nonce_details,
        'nonce_action' => $print_nonce_action_details,
        'context'      => 'frontend'
    );
    if ( ! is_null( $specific_client_id_for_report ) && $specific_client_id_for_report > 0 ) {
        $print_url_args_details['client_id_to_print'] = $specific_client_id_for_report;
    }
    // If you want print to respect current client search term:
    // $current_search_term = isset($_GET['s_clients']) ? sanitize_text_field($_GET['s_clients']) : '';
    // if (!empty($current_search_term)) {
    //    $print_url_args_details['search_clients'] = $current_search_term;
    // }
    $print_url_details = add_query_arg( $print_url_args_details, admin_url( 'admin-post.php' ) );
    ?>
    <div class="wcsl-report-actions">
        <a href="<?php echo esc_url( $print_url_details ); ?>" class="button wcsl-print-button" target="_blank">
            <?php esc_html_e( 'Print / Save Summary as PDF', 'wp-client-support-ledger' ); ?>
        </a>
    </div>
    <?php
}

function wcsl_handle_custom_login_submission() {
    // Check if our specific login form was submitted
    if ( isset( $_POST['wcsl_login_submit'] ) ) {
        // Verify nonce
        if ( ! isset( $_POST['_wcsl_login_nonce_field'] ) || ! wp_verify_nonce( $_POST['_wcsl_login_nonce_field'], 'wcsl-login-nonce' ) ) {
            // Nonce is invalid, set an error query arg and redirect back to the page with the form
            // It's better to redirect than to output an error here directly if possible, to avoid headers issues.
            // However, if we can't redirect, we might have to store the error in a session/transient or handle differently.
            // For now, let's try redirecting with an error code.
            $login_page_url = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url(); // Get original page
            $login_page_url = add_query_arg( 'wcsl_login_error', 'nonce_fail', $login_page_url );
            wp_safe_redirect( esc_url_raw( $login_page_url ) );
            exit;
        }

        $creds = array();
        $creds['user_login']    = isset( $_POST['log'] ) ? sanitize_user( $_POST['log'] ) : '';
        $creds['user_password'] = isset( $_POST['pwd'] ) ? $_POST['pwd'] : ''; // wp_signon handles password
        $creds['remember']      = isset( $_POST['rememberme'] );
        
        $user = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $user ) ) {
            // Login failed, redirect back to the login page with error codes
            $error_codes = implode( ',', $user->get_error_codes() );
            $login_page_url = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url();
            $login_page_url = add_query_arg( array(
                'wcsl_login_error' => 'failed',
                'wcsl_error_codes' => $error_codes 
            ), $login_page_url );
            wp_safe_redirect( esc_url_raw( $login_page_url ) );
            exit;
        } else {
            // Successful login, redirect to the 'redirect_to' URL or current page
            $redirect_url = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : get_permalink();
            // Remove any login error parameters from URL before redirecting
            $redirect_url = remove_query_arg( array('wcsl_login_error', 'wcsl_error_codes', 'loggedout'), $redirect_url );
            wp_safe_redirect( esc_url_raw( $redirect_url ) );
            exit;
        }
    }
}
// Hook to an action that fires before headers are sent
add_action( 'template_redirect', 'wcsl_handle_custom_login_submission' );
// Alternatively, 'init' can also be used, but 'template_redirect' is often good for page-based forms.


/**
 * Displays a custom login form.
 * Now also displays error messages passed via GET parameters.
 */
function wcsl_display_custom_login_form() {
    // Display any login errors passed back via query arguments
    if ( isset( $_GET['wcsl_login_error'] ) ) {
        echo '<div class="wcsl-login-error">';
        if ( $_GET['wcsl_login_error'] === 'nonce_fail' ) {
            esc_html_e( 'Security check failed. Please try logging in again.', 'wp-client-support-ledger' );
        } elseif ( $_GET['wcsl_login_error'] === 'failed' && isset($_GET['wcsl_error_codes']) ) {
            $error_codes = explode(',', sanitize_text_field($_GET['wcsl_error_codes']));
            $messages = array();
            foreach ($error_codes as $code) {
                // You can map specific WordPress error codes to user-friendly messages
                // For now, just use the default WordPress messages if wp_login_failed hook isn't enough
                // Or retrieve messages from a transient if you set them.
                // This part is tricky as wp_signon doesn't directly give you displayable messages easily
                // after a redirect. It's often better to use the default wp-login.php for full error handling.
                // A simpler method for this custom form is to just show a generic error.
                $messages[] = apply_filters( 'login_errors', esc_html(ucfirst(str_replace('_', ' ', $code))) );
            }
            echo implode('<br />', $messages);
            // A simpler generic error:
            // esc_html_e( 'Login failed. Please check your username and password and try again.', 'wp-client-support-ledger' );
        }
        echo '</div>';
    }
    
    // Display the login form HTML (this part remains largely the same)
    ?>
    <div class="wcsl-login-form-container">
        <h3><?php esc_html_e( 'Please Log In', 'wp-client-support-ledger' ); ?></h3>
        <p><?php esc_html_e( 'You need to be logged in to view this report.', 'wp-client-support-ledger' ); ?></p>
        <form name="wcsl_loginform" id="wcsl_loginform" action="<?php echo esc_url( get_permalink() ); // Post back to the current page ?>" method="post">
            <p>
                <label for="wcsl_user_login"><?php esc_html_e( 'Username or Email Address', 'wp-client-support-ledger' ); ?></label>
                <input type="text" name="log" id="wcsl_user_login" class="input" value="<?php echo esc_attr(isset($_POST['log']) && isset($_GET['wcsl_login_error']) ? $_POST['log'] : ''); ?>" size="20" />
            </p>
            <p>
                <label for="wcsl_user_pass"><?php esc_html_e( 'Password', 'wp-client-support-ledger' ); ?></label>
                <input type="password" name="pwd" id="wcsl_user_pass" class="input" value="" size="20" />
            </p>
            <?php do_action( 'login_form' ); ?>
            <p class="wcsl-login-remember">
                <label><input name="rememberme" type="checkbox" id="wcsl_rememberme" value="forever" /> <?php esc_html_e( 'Remember Me', 'wp-client-support-ledger' ); ?></label>
            </p>
            <p class="wcsl-login-submit">
                <input type="submit" name="wcsl_login_submit" id="wcsl_wp-submit" class="button button-primary wcsl-login-button" value="<?php esc_attr_e( 'Log In', 'wp-client-support-ledger' ); ?>" />
                <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); // Redirect back to current page ?>" />
            </p>
            <?php wp_nonce_field( 'wcsl-login-nonce', '_wcsl_login_nonce_field' ); ?>
        </form>
        <p class="wcsl-lost-password">
            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'wp-client-support-ledger' ); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Enqueue frontend styles for the plugin if the shortcode is present.
 */
function wcsl_enqueue_frontend_assets() {
    global $post;

    // Only proceed if it's a singular page/post and the content contains our shortcode
    if ( is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'client_support_report' ) ) {
        
        // Enqueue the main frontend stylesheet
        wp_enqueue_style(
            'wcsl-frontend-style',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/frontend-style.css',
            array(),
            '1.0.7' // Increment version if you made changes to frontend-style.css
        );

        // Enqueue the frontend JavaScript file (for AJAX search/pagination)
        wp_enqueue_script(
            'wcsl-frontend-report-js',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/frontend-report.js',
            array( 'jquery' ),
            '1.0.1', // Increment version if JS changes
            true    // Load in footer
        );

        // Localize script to pass AJAX URL and other necessary data to JS
        wp_localize_script( 'wcsl-frontend-report-js', 'wcsl_frontend_ajax', array(
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'loading_message' => esc_js(__('Loading...', 'wp-client-support-ledger')),
            // Nonce for AJAX calls is now generated via wp_nonce_field() in the shortcode HTML
        ) );

        // --- Add Inline Styles for Custom Colors ---
        $settings = get_option( 'wcsl_email_notification_settings', array() ); 
        
        // Define default colors (these MUST match the defaults in your settings registration for consistency)
        $default_container_bg           = '#39618C';
        $default_button_bg              = '#39618C'; // General buttons now use primary
        $default_button_text            = '#FFFFFF';
        $default_pagination_active_bg   = '#2c4a6b'; // Darker primary for active pagination
        $default_pagination_active_text = '#FFFFFF';

        // Get saved colors, or use defaults if not set or invalid
        $container_bg = !empty( $settings['frontend_container_bg_color'] ) && preg_match( '/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $settings['frontend_container_bg_color'] )
                        ? $settings['frontend_container_bg_color'] 
                        : $default_container_bg;
        
        $button_bg    = !empty( $settings['frontend_button_bg_color'] ) && preg_match( '/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $settings['frontend_button_bg_color'] )
                        ? $settings['frontend_button_bg_color'] 
                        : $default_button_bg;
        
        $button_text  = !empty( $settings['frontend_button_text_color'] ) && preg_match( '/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $settings['frontend_button_text_color'] )
                        ? $settings['frontend_button_text_color'] 
                        : $default_button_text;

        $pagination_active_bg = !empty( $settings['frontend_pagination_active_bg'] ) && preg_match( '/^#([a-f0-9]{6}|[a-f0_9]{3})$/i', $settings['frontend_pagination_active_bg'] )
                                ? $settings['frontend_pagination_active_bg'] 
                                : $default_pagination_active_bg;
        
        $pagination_active_text = !empty( $settings['frontend_pagination_active_text'] ) && preg_match( '/^#([a-f0-9]{6}|[a-f0_9]{3})$/i', $settings['frontend_pagination_active_text'] )
                                ? $settings['frontend_pagination_active_text'] 
                                : $default_pagination_active_text;
        
        // Sanitize them one last time before outputting
        $container_bg           = sanitize_hex_color( $container_bg );
        $button_bg              = sanitize_hex_color( $button_bg );
        $button_text            = sanitize_hex_color( $button_text );
        $pagination_active_bg   = sanitize_hex_color( $pagination_active_bg );
        $pagination_active_text = sanitize_hex_color( $pagination_active_text );

        $custom_css = "
            :root {
                --wcsl-container-bg: {$container_bg};
                --wcsl-button-bg: {$button_bg};
                --wcsl-button-text: {$button_text};
                --wcsl-pagination-active-bg: {$pagination_active_bg};
                --wcsl-pagination-active-text: {$pagination_active_text};
            }
        ";
        
        wp_add_inline_style( 'wcsl-frontend-style', $custom_css );
    }
}
add_action( 'wp_enqueue_scripts', 'wcsl_enqueue_frontend_assets' );


