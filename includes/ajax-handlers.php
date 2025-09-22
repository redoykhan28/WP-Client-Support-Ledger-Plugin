<?php

function wcsl_manage_notification_ajax_handler() {
    // Check required parameters
    if ( ! isset( $_POST['notification_id'], $_POST['_ajax_nonce'], $_POST['wcsl_ajax_action'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid AJAX request parameters.', 'wp-client-support-ledger' ) ) );
    }

    $notification_id = intval( $_POST['notification_id'] );
    $nonce           = sanitize_text_field( $_POST['_ajax_nonce'] );
    $sub_action      = sanitize_key( $_POST['wcsl_ajax_action'] );

    // Verify nonce - specific to AJAX management of this notification
    if ( ! wp_verify_nonce( $nonce, 'wcsl_ajax_manage_notification_' . $notification_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed (nonce).', 'wp-client-support-ledger' ) ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-client-support-ledger' ) ) );
    }

    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    
    $message_feedback = '';
    $processed_successfully = false;

    if ( 'delete' === $sub_action ) {
        $result = $wpdb->delete( $table_name, array( 'id' => $notification_id ), array('%d') );
         if (false !== $result && $result > 0) { // Check if rows were actually deleted
            $message_feedback = __( 'Notification deleted.', 'wp-client-support-ledger' );
            $processed_successfully = true;
        } elseif (false !== $result && $result === 0) {
            $message_feedback = __('Notification not found or already deleted.', 'wp-client-support-ledger');
            $processed_successfully = true; // Allow JS to remove row
        } else {
             $message_feedback = __('Error deleting notification from database.', 'wp-client-support-ledger');
        }
    } else {
        $message_feedback = __( 'Invalid AJAX action specified. Only delete is supported via AJAX for single notifications.', 'wp-client-support-ledger' );
    }
    
    if ($processed_successfully) {
        wp_send_json_success( array( 'message' => $message_feedback ) );
    } else {
        wp_send_json_error( array( 'message' => $message_feedback ) );
    }
}
// Hook for logged-in users
add_action( 'wp_ajax_wcsl_manage_notification_ajax', 'wcsl_manage_notification_ajax_handler' );




/**
 * AJAX handler to load the client summary table content.
 */
function wcsl_ajax_load_frontend_client_summary_handler() {
    // Check nonce first. The nonce field 'wcsl_frontend_report_nonce' generates a nonce with action 'wcsl_frontend_report_nonce_action'.
    check_ajax_referer( 'wcsl_frontend_report_nonce_action', '_ajax_nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'wp-client-support-ledger' ) ) );
    }

    // Sanitize and retrieve POST data
    $search_term = isset( $_POST['search_term'] ) ? sanitize_text_field( wp_unslash( $_POST['search_term'] ) ) : '';
    $paged       = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
    $target_month= isset( $_POST['month'] ) ? intval( $_POST['month'] ) : date('n');
    $target_year = isset( $_POST['year'] ) ? intval( $_POST['year'] ) : date('Y');
    
    // Define how many clients per page for this AJAX loaded table
    $clients_per_page = 10;

    ob_start();

    // --- Build WP_Query arguments for fetching clients ---
    $clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => $clients_per_page,
        'paged'          => $paged,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish'
    );
    if ( !empty($search_term) ) {
        $clients_query_args['s'] = $search_term;
    }

    $clients_query = new WP_Query( $clients_query_args );

    if ( $clients_query->have_posts() ) :
    ?>
        <table class="wcsl-frontend-table clients-summary-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Client', 'wp-client-support-ledger' ); ?></th>
                    <th><?php esc_html_e( 'Contracted Hours', 'wp-client-support-ledger' ); ?></th>
                    <th><?php esc_html_e( 'Total Hours Spent', 'wp-client-support-ledger' ); ?></th>
                    <th><?php esc_html_e( 'Billable Hours', 'wp-client-support-ledger' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $target_month, 1, $target_year ) );
                $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $target_month + 1, 0, $target_year ) );

                while ( $clients_query->have_posts() ) : $clients_query->the_post();
                    $client_id = get_the_ID();
                    $client_name = get_the_title( $client_id );
                    $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
                    
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
                    wp_reset_postdata();

                    $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );
                    $billable_minutes = max( 0, $total_minutes_spent_this_month - $contracted_minutes );
                ?>
                    <tr>
                        <td><?php echo esc_html( $client_name ); ?></td> 
                        <td><?php echo esc_html( !empty($contracted_hours_str) ? $contracted_hours_str : 'N/A' ); ?></td>
                        <td><?php echo esc_html( wcsl_format_minutes_to_time_string( $total_minutes_spent_this_month ) ); ?></td>
                        <td><?php echo esc_html( wcsl_format_minutes_to_time_string( $billable_minutes ) ); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
        $total_pages = $clients_query->max_num_pages;
        if ($total_pages > 1) {
            $pagination_base_url_args = array(
                'wcsl_action'  => 'view_month_details_frontend',
                'wcsl_month'   => $target_month,
                'wcsl_year'    => $target_year,
            );
            $base_for_paginate = add_query_arg( $pagination_base_url_args, get_permalink( get_queried_object_id() ?: get_the_ID() ) );
            $base_for_paginate = add_query_arg( 'wcsl_paged_clients', '%#%', $base_for_paginate );
            echo '<div class="wcsl-pagination">';
            echo paginate_links( array(
                'base'      => $base_for_paginate, 'format' => '', 'current' => $paged, 'total' => $total_pages,
                'prev_text' => __( '« Prev', 'wp-client-support-ledger' ), 'next_text' => __( 'Next »', 'wp-client-support-ledger' ), 'add_args' => false
            ) );
            echo '</div>';
        }
        wp_reset_postdata();
    else :
        echo '<p>' . esc_html__( 'No clients found matching your search criteria for this period.', 'wp-client-support-ledger' ) . '</p>';
    endif;

    $html_output = ob_get_clean();
    wp_send_json_success( array( 'html' => $html_output ) );
}
add_action( 'wp_ajax_wcsl_load_frontend_client_summary', 'wcsl_ajax_load_frontend_client_summary_handler' );


// <<< NEW AJAX HANDLER FOR TASK CATEGORIES >>>
/**
 * AJAX handler to fetch task categories based on the primary type (support/fixing).
 */

function wcsl_ajax_get_task_categories_handler() {
    check_ajax_referer( 'wcsl_get_task_categories_nonce', 'nonce' );

    if ( ! current_user_can('edit_posts') ) {
        wp_send_json_error( array( 'message' => 'Permission denied.' ) );
    }

    $primary_type = isset( $_POST['primary_type'] ) ? sanitize_key( $_POST['primary_type'] ) : '';
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    
    if ( empty($primary_type) || !in_array($primary_type, array('support', 'fixing')) ) {
        wp_send_json_error( array( 'message' => 'Invalid primary type.' ) );
    }

    $terms = get_terms( array(
        'taxonomy'   => 'task_category',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'   => 'wcsl_billable_type',
                'value' => $primary_type,
            ),
        ),
    ) );
    
    // <<< CORRECTION: Get the currently saved term for this post >>>
    $current_term_id = 0;
    if ( $post_id > 0 ) {
        $current_terms = get_the_terms( $post_id, 'task_category' );
        if ( ! is_wp_error( $current_terms ) && ! empty( $current_terms ) ) {
            // A post can only have one term in our setup, so we get the first one.
            $current_term_id = $current_terms[0]->term_id;
        }
    }

    $html = '<option value="">' . esc_html__('-- Select a Category --', 'wp-client-support-ledger') . '</option>';
    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $term->term_id ),
                selected( $current_term_id, $term->term_id, false ), // This will now correctly pre-select the saved term
                esc_html( $term->name )
            );
        }
    }

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_wcsl_get_task_categories', 'wcsl_ajax_get_task_categories_handler' );


/**
 * AJAX handler for loading all employee portal content dynamically.
 */
function wcsl_ajax_load_employee_portal_content_handler() {
    // ... (Security checks remain the same) ...
    check_ajax_referer( 'wcsl_employee_portal_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) { wp_send_json_error( array('message' => 'You must be logged in.') ); return; }
    $user = wp_get_current_user();
    if ( ! in_array( 'wcsl_employee', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) { wp_send_json_error( array('message' => 'You do not have permission.') ); return; }

    $requested_url = isset($_POST['requested_url']) ? esc_url_raw($_POST['requested_url']) : '';
    if ( empty($requested_url) ) { wp_send_json_error( array('message' => 'Invalid request URL.') ); return; }
    
    $query_params = array();
    parse_str( wp_parse_url( $requested_url, PHP_URL_QUERY ), $query_params );
    $_GET = $query_params;

    $view = isset( $query_params['wcsl_view'] ) ? sanitize_key( $query_params['wcsl_view'] ) : 'dashboard';
    
    $response_data = array( 'html' => '', 'scripts_data' => null );
    ob_start();

    if ( 'reports' === $view ) {
        // ... (reports logic remains the same) ...
        wcsl_render_employee_reports_page();
        $response_data['html'] = ob_get_clean();
        $today_for_reports = current_time('Y-m-d');
        $default_start_for_reports = date('Y-m-d', strtotime('-29 days', strtotime($today_for_reports)));
        $report_start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : $default_start_for_reports;
        $report_end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : $today_for_reports;
        if (strtotime($report_end_date) < strtotime($report_start_date)) { $report_end_date = $report_start_date; }
        $response_data['scripts_data'] = array(
            'hoursPerClient'      => wcsl_get_hours_per_client_for_period( $report_start_date, $report_end_date ),
            'totalBillableHours'  => array( 'value_string' => wcsl_format_minutes_to_time_string( wcsl_get_total_billable_minutes_for_period( $report_start_date, $report_end_date ) ) ),
            'billablePerClient'   => wcsl_get_billable_summary_per_client_for_period( $report_start_date, $report_end_date ),
            'hoursByEmployee'     => wcsl_get_hours_by_employee_for_period( $report_start_date, $report_end_date ),
            'billableTrend'       => wcsl_get_billable_hours_for_past_months( 12 ),
            'supportTaskAnalysis' => wcsl_get_task_count_by_category('support', $report_start_date, $report_end_date),
            'fixingTaskAnalysis'  => wcsl_get_task_count_by_category('fixing', $report_start_date, $report_end_date),
            'chartColors'         => array('rgba(57, 97, 140, 0.7)', 'rgba(91, 192, 222, 0.7)', 'rgba(240, 173, 78, 0.7)', 'rgba(92, 184, 92, 0.7)', 'rgba(217, 83, 79, 0.7)'),
            'chartBorderColors'   => array('rgb(57, 97, 140)', 'rgb(91, 192, 222)', 'rgb(240, 173, 78)', 'rgb(92, 184, 92)', 'rgb(217, 83, 79)'),
            'i18n' => array( 'hoursLabel' => esc_js(__('Hours')), 'tasksLabel' => esc_js(__('Tasks')) )
        );
        wp_send_json_success( $response_data );
        return;
    } 
    
    // Standard handling for all other views
    if ( 'month_details' === $view ) { wcsl_render_employee_month_details_content(intval($_GET['wcsl_month']), intval($_GET['wcsl_year']));
    } elseif ( 'month_details_summary_table' === $view ) { wcsl_render_employee_month_details_summary_table();
    } elseif ( 'month_details_tasks_table' === $view ) { wcsl_render_employee_month_details_tasks_table();
    } elseif ( 'add_task' === $view ) { wcsl_render_employee_add_task_form();
    } elseif ( 'all_tasks' === $view ) { if ( isset($_GET['search_tasks']) || isset($_GET['paged']) ) { wcsl_render_all_tasks_table(); } else { wcsl_render_employee_all_tasks_page(); }
    } elseif ( 'my_tasks' === $view ) { if ( isset($_GET['search_tasks']) || isset($_GET['paged']) ) { wcsl_render_my_tasks_table(); } else { wcsl_render_employee_my_tasks_page(); }
    } elseif ( 'edit_task' === $view && isset( $_GET['task_id'] ) ) { wcsl_render_employee_edit_task_form( intval( $_GET['task_id'] ) );
    } elseif ( 'notifications' === $view ) { wcsl_render_employee_notifications_page();
    } elseif ( 'notifications_table' === $view ) { // *** NEW: Route for table-only refresh ***
        wcsl_render_employee_notifications_table_content();
    } else { wcsl_render_employee_dashboard_content(); }
    
    $response_data['html'] = ob_get_clean();
    wp_send_json_success( $response_data );
}

add_action( 'wp_ajax_wcsl_load_employee_portal_content', 'wcsl_ajax_load_employee_portal_content_handler' );


/**
 * AJAX handler for managing a CLIENT's notifications (Mark Read/Unread/Delete).
 */
/**
 * AJAX handler for managing a CLIENT's notifications (Mark Read/Unread/Delete).
 */
function wcsl_ajax_manage_client_notification_handler() {
    // 1. Security & Parameter Checks
    if ( ! isset( $_POST['notification_id'], $_POST['_ajax_nonce'], $_POST['wcsl_action'] ) ) {
        wp_send_json_error( array( 'message' => 'Invalid request.' ) );
    }

    $notification_id = intval( $_POST['notification_id'] );
    $nonce           = sanitize_text_field( $_POST['_ajax_nonce'] );
    $sub_action      = sanitize_key( $_POST['wcsl_action'] );

    if ( ! wp_verify_nonce( $nonce, 'wcsl_manage_notification_' . $notification_id ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    $current_user_id = get_current_user_id();

    // 2. Verify Ownership: Ensure the notification belongs to the current user
    $notification_owner_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table_name} WHERE id = %d", $notification_id ) );
    if ( (int) $notification_owner_id !== $current_user_id ) {
        wp_send_json_error( array( 'message' => 'Permission denied.' ) );
    }

    // 3. Perform Action
    if ( 'delete' === $sub_action ) {
        $result = $wpdb->delete( $table_name, array( 'id' => $notification_id ), array('%d') );
        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Could not delete notification.' ) );
        }
    } elseif ( 'mark_read' === $sub_action ) {
        $wpdb->update( $table_name, array( 'is_read' => 1 ), array( 'id' => $notification_id ) );
    } elseif ( 'mark_unread' === $sub_action ) {
        $wpdb->update( $table_name, array( 'is_read' => 0 ), array( 'id' => $notification_id ) );
    } else {
        wp_send_json_error( array( 'message' => 'Invalid action.' ) );
    }

    // *** NEW: Calculate the new unread count for the current user ***
    $new_unread_count = 0;
    if (function_exists('wcsl_get_unread_notification_count_for_user')) {
        $new_unread_count = wcsl_get_unread_notification_count_for_user($current_user_id);
    }
    
    // If the action was delete, we just send the count back
    if ( 'delete' === $sub_action ) {
        wp_send_json_success( array( 'new_count' => $new_unread_count ) );
    }
    
    // 4. If not deleting, fetch the updated row HTML and send it back
    $notification = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $notification_id ) );
    if ( ! $notification ) {
        wp_send_json_success( array( 'new_count' => $new_unread_count, 'html' => '' ) );
    }

    // Get the base URL for action links
    $portal_settings = get_option('wcsl_portal_settings');
    $portal_page_id = isset($portal_settings['portal_page_id']) ? $portal_settings['portal_page_id'] : 0;
    $base_url = $portal_page_id ? get_permalink($portal_page_id) : home_url();
    $base_url = add_query_arg('wcsl_view', 'notifications', $base_url);

    // Re-generate the row HTML
    ob_start();
    $mark_read_url = wp_nonce_url( add_query_arg( ['wcsl_action' => 'mark_read', 'notification_id' => $notification->id], $base_url ), 'wcsl_manage_notification_' . $notification->id, '_wcsl_nonce' );
    $mark_unread_url = wp_nonce_url( add_query_arg( ['wcsl_action' => 'mark_unread', 'notification_id' => $notification->id], $base_url ), 'wcsl_manage_notification_' . $notification->id, '_wcsl_nonce' );
    $delete_url = wp_nonce_url( add_query_arg( ['wcsl_action' => 'delete', 'notification_id' => $notification->id], $base_url ), 'wcsl_manage_notification_' . $notification->id, '_wcsl_nonce' );
    ?>
    <tr id="notification-row-<?php echo esc_attr( $notification->id ); ?>" class="<?php echo $notification->is_read ? 'read' : 'unread'; ?>">
        <td class="notification-message"><?php echo wp_kses_post( $notification->message ); ?></td>
        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $notification->created_at ) ) ); ?></td>
        <td class="notification-actions">
            <?php if ( ! $notification->is_read ) : ?><a href="<?php echo esc_url( $mark_read_url ); ?>"><?php esc_html_e( 'Mark Read', 'wp-client-support-ledger' ); ?></a>
            <?php else : ?><a href="<?php echo esc_url( $mark_unread_url ); ?>"><?php esc_html_e( 'Mark Unread', 'wp-client-support-ledger' ); ?></a><?php endif; ?>
            <span class="action-divider">|</span>
            <a href="<?php echo esc_url( $delete_url ); ?>" class="delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this notification?', 'wp-client-support-ledger' ); ?>');"><?php esc_html_e( 'Delete', 'wp-client-support-ledger' ); ?></a>
        </td>
    </tr>
    <?php
    $html = ob_get_clean();

    // *** NEW: Send back both the new HTML and the new count ***
    wp_send_json_success( array( 'html' => $html, 'new_count' => $new_unread_count ) );
}
add_action( 'wp_ajax_wcsl_manage_client_notification_ajax', 'wcsl_ajax_manage_client_notification_handler' );





/**
 * AJAX handler for the frontend "Add Task" form submission.
 */

function wcsl_ajax_frontend_add_task_handler() {
    // 1. Security Checks
    check_ajax_referer( 'wcsl_add_task_action', 'wcsl_add_task_nonce' );

    $user = wp_get_current_user();
    if ( ! is_user_logged_in() || ! in_array('wcsl_employee', (array) $user->roles) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
    }

    // 2. Sanitize and Validate Form Data
    $errors = array();
    $required_fields = array(
        'wcsl_task_title'           => 'Task Title',
        'wcsl_task_type'            => 'Task Type',
        'wcsl_task_date'            => 'Task Date',
        'wcsl_hours_spent_on_task'  => 'Hours Spent',
        'wcsl_task_status'          => 'Task Status',
        'wcsl_task_link'            => 'Task Link',
        'wcsl_assigned_employee_id' => 'Assigned Employee',
        'wcsl_related_client_id'    => 'Related Client'
    );

    foreach ( $required_fields as $key => $label ) {
        if ( empty( $_POST[$key] ) ) {
            $errors[] = esc_html( $label ) . ' is a required field.';
        }
    }

    if ( ! empty( $errors ) ) {
        wp_send_json_error( array( 'message' => 'Please fill in all required fields:<br>- ' . implode('<br>- ', $errors) ) );
    }

    // 3. *** NEW: Prepare Meta and Taxonomy Data FIRST ***
    $meta_input = array();
    $tax_input = array();

    // Prepare standard meta fields
    $meta_input['_wcsl_task_type'] = sanitize_key( $_POST['wcsl_task_type'] );
    $meta_input['_wcsl_task_date'] = sanitize_text_field( $_POST['wcsl_task_date'] );
    $meta_input['_wcsl_hours_spent_on_task'] = sanitize_text_field( $_POST['wcsl_hours_spent_on_task'] );
    $meta_input['_wcsl_task_status'] = sanitize_key( $_POST['wcsl_task_status'] );
    $meta_input['_wcsl_related_client_id'] = intval( $_POST['wcsl_related_client_id'] );
    $meta_input['_wcsl_task_link'] = esc_url_raw( $_POST['wcsl_task_link'] );
    $meta_input['_wcsl_task_note'] = sanitize_textarea_field( $_POST['wcsl_task_note'] );
    $meta_input['_wcsl_task_attachment_url'] = esc_url_raw( $_POST['wcsl_task_attachment_url'] );
    
    // Prepare employee meta
    $employee_id = intval( $_POST['wcsl_assigned_employee_id'] );
    if ( $employee_id > 0 ) {
        $meta_input['_wcsl_assigned_employee_id'] = $employee_id;
        $employee_post = get_post( $employee_id );
        if ( $employee_post && $employee_post->post_type === 'employee' ) {
            $meta_input['_wcsl_employee_name'] = sanitize_text_field( $employee_post->post_title );
            $employee_contact_email = get_post_meta( $employee_id, '_wcsl_employee_contact_email', true );
            if ( is_email( $employee_contact_email ) ) {
                $meta_input['_wcsl_employee_email'] = $employee_contact_email;
            }
        }
    }

    // Prepare task category with default fallback logic
    $term_id_to_set = 0;
    if ( ! empty( $_POST['wcsl_task_category'] ) ) {
        $term_id_to_set = intval( $_POST['wcsl_task_category'] );
    } else {
        $default_term_ids = get_option('wcsl_default_term_ids', array());
        $primary_type = sanitize_key( $_POST['wcsl_task_type'] );
        if ( 'support' === $primary_type && ! empty( $default_term_ids['support'] ) ) {
            $term_id_to_set = $default_term_ids['support'];
        } elseif ( 'fixing' === $primary_type && ! empty( $default_term_ids['fixing'] ) ) {
            $term_id_to_set = $default_term_ids['fixing'];
        }
    }
    if ( $term_id_to_set > 0 ) {
        $tax_input['task_category'] = $term_id_to_set;
    }

    // 4. Prepare the final Post Data array with meta_input and tax_input
    $task_data = array(
        'post_title'   => sanitize_text_field( $_POST['wcsl_task_title'] ),
        'post_status'  => 'publish',
        'post_type'    => 'client_task',
        'post_author'  => $user->ID,
        'meta_input'   => $meta_input,
        'tax_input'    => $tax_input,
    );

    // 5. Create the Post in a single operation
    $post_id = wp_insert_post( $task_data, true ); // Pass true to return WP_Error on failure

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Error: ' . $post_id->get_error_message() ) );
    }

    // 6. Prepare the redirect URL
    $task_date_str = sanitize_text_field( $_POST['wcsl_task_date'] );
    $timestamp = strtotime( $task_date_str );
    $month = date( 'n', $timestamp );
    $year  = date( 'Y', $timestamp );
    
    $portal_settings = get_option('wcsl_portal_settings');
    $portal_page_id = isset($portal_settings['portal_page_id']) ? (int)$portal_settings['portal_page_id'] : 0;
    $redirect_url = $portal_page_id ? get_permalink($portal_page_id) : home_url();

 $redirect_url = add_query_arg( array(
        'wcsl_view'  => 'month_details',
        'wcsl_month' => $month,
        'wcsl_year'  => $year,
        'task_added' => 'true'
    ), $redirect_url );

    // *** NEW: Add the hash to the end of the URL for scrolling ***
    $redirect_url .= '#detailed-task-log';
    
    wp_send_json_success( array( 'redirect_url' => esc_url_raw( $redirect_url ) ) );
}
add_action( 'wp_ajax_wcsl_frontend_add_task_ajax', 'wcsl_ajax_frontend_add_task_handler' );



/**
 * AJAX handler for an employee to delete one of their own tasks.
 */
function wcsl_ajax_employee_delete_task_handler() {
    // 1. Security & Parameter Checks
    if ( ! isset( $_POST['task_id'], $_POST['nonce'] ) ) {
        wp_send_json_error( array( 'message' => 'Invalid request.' ) );
    }

    $task_id = intval( $_POST['task_id'] );
    $nonce   = sanitize_text_field( $_POST['nonce'] );

    if ( ! wp_verify_nonce( $nonce, 'wcsl_delete_task_' . $task_id ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }

    $current_user = wp_get_current_user();
    if ( ! is_user_logged_in() || ! in_array('wcsl_employee', (array) $current_user->roles) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
    }
    
    // 2. Verify Ownership: Ensure the task is actually assigned to this employee.
    $employee_cpt_id = wcsl_get_employee_id_for_user( $current_user->ID );
    $task_author_employee_id = (int) get_post_meta( $task_id, '_wcsl_assigned_employee_id', true );

    if ( ! $employee_cpt_id || $employee_cpt_id !== $task_author_employee_id ) {
        wp_send_json_error( array( 'message' => 'Permission denied: You can only delete tasks assigned to you.' ) );
    }

    // 3. Perform Deletion
    // wp_delete_post() returns the post data on success, false on failure.
    $result = wp_delete_post( $task_id, true ); // true = force delete, bypass trash

    if ( $result ) {
        wp_send_json_success( array( 'message' => 'Task successfully deleted.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Could not delete the task from the database.' ) );
    }
}
add_action( 'wp_ajax_wcsl_employee_delete_task', 'wcsl_ajax_employee_delete_task_handler' );



/**
 * *** NEW ***
 * AJAX handler for an EMPLOYEE managing a single ADMIN notification.
 */
function wcsl_ajax_employee_manage_notification_handler() {
    // 1. Security & Parameter Checks
    if ( ! isset( $_POST['notification_id'], $_POST['nonce'], $_POST['wcsl_action'] ) ) {
        wp_send_json_error( array( 'message' => 'Invalid request.' ) );
    }

    $notification_id = intval( $_POST['notification_id'] );
    $nonce           = sanitize_text_field( $_POST['nonce'] );
    $sub_action      = sanitize_key( $_POST['wcsl_action'] );

    if ( ! wp_verify_nonce( $nonce, 'wcsl_employee_manage_notification_' . $notification_id ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }
    
    // Permission Check: Must be an employee or admin
    $user = wp_get_current_user();
    if ( ! is_user_logged_in() || ! ( in_array('wcsl_employee', (array) $user->roles) || in_array('administrator', (array) $user->roles) ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied.' ) );
    }

    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();

    // 2. Perform Action on the notification
    if ( 'delete' === $sub_action ) {
        $wpdb->delete( $table_name, array( 'id' => $notification_id, 'user_id' => 0 ), array('%d', '%d') );
    } elseif ( 'mark_read' === $sub_action ) {
        $wpdb->update( $table_name, array( 'is_read' => 1 ), array( 'id' => $notification_id, 'user_id' => 0 ) );
    } elseif ( 'mark_unread' === $sub_action ) {
        $wpdb->update( $table_name, array( 'is_read' => 0 ), array( 'id' => $notification_id, 'user_id' => 0 ) );
    } else {
        wp_send_json_error( array( 'message' => 'Invalid action.' ) );
    }

    // 3. Recalculate the new unread count and send it back
    $new_unread_count = wcsl_get_unread_notification_count();
    
    wp_send_json_success( array( 'new_count' => $new_unread_count ) );
}
add_action( 'wp_ajax_wcsl_employee_manage_notification', 'wcsl_ajax_employee_manage_notification_handler' );

/**
 * *** NEW ***
 * AJAX handler for an EMPLOYEE performing BULK actions on notifications.
 */
function wcsl_ajax_employee_bulk_notifications_handler() {
    // 1. Security & Parameter Checks
    check_ajax_referer( 'wcsl_employee_portal_nonce', 'nonce' );

    // Permission Check
    $user = wp_get_current_user();
    if ( ! is_user_logged_in() || ! ( in_array('wcsl_employee', (array) $user->roles) || in_array('administrator', (array) $user->roles) ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied.' ) );
    }

    $bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( $_POST['bulk_action'] ) : '';
    $notification_ids = isset( $_POST['notification_ids'] ) ? array_map('intval', $_POST['notification_ids']) : array();

    if ( '-1' === $bulk_action || empty( $notification_ids ) ) {
        wp_send_json_error( array('message' => 'No action or no items selected.') );
    }

    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    $ids_placeholder = implode( ',', array_fill( 0, count( $notification_ids ), '%d' ) );

    // 2. Perform Bulk Action
    if ( 'bulk_delete' === $bulk_action ) {
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE user_id = 0 AND id IN ({$ids_placeholder})", $notification_ids ) );
    } elseif ( 'bulk_mark_read' === $bulk_action ) {
        $wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET is_read = 1 WHERE user_id = 0 AND id IN ({$ids_placeholder})", $notification_ids ) );
    } elseif ( 'bulk_mark_unread' === $bulk_action ) {
        $wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET is_read = 0 WHERE user_id = 0 AND id IN ({$ids_placeholder})", $notification_ids ) );
    }

    // *** FIX IS HERE: Re-render ONLY the table content, not the whole page ***
    $_GET['wcsl_view'] = 'notifications_table'; // Use the view that points to our helper
    $_GET['paged'] = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
    
    ob_start();
    wcsl_render_employee_notifications_table_content(); // Call the helper function
    $html = ob_get_clean();

    $new_unread_count = wcsl_get_unread_notification_count();

    wp_send_json_success( array(
        'html'      => $html,
        'new_count' => $new_unread_count
    ));
}
add_action( 'wp_ajax_wcsl_employee_bulk_notifications', 'wcsl_ajax_employee_bulk_notifications_handler' );



