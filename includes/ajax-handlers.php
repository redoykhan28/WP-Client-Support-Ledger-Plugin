<?php

function wcsl_manage_notification_ajax_handler() {
    // Check for required parameters
    if ( ! isset( $_POST['notification_id'], $_POST['_ajax_nonce'], $_POST['wcsl_ajax_action'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid AJAX request.', 'wp-client-support-ledger' ) ) );
    }

    $notification_id = intval( $_POST['notification_id'] );
    $nonce = sanitize_text_field( $_POST['_ajax_nonce'] );
    $sub_action = sanitize_key( $_POST['wcsl_ajax_action'] );

    // Verify nonce (nonce was created as 'wcsl_ajax_manage_notification_' . $notification_id)
    if ( ! wp_verify_nonce( $nonce, 'wcsl_ajax_manage_notification_' . $notification_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed (nonce).', 'wp-client-support-ledger' ) ) );
    }

    // Check user capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-client-support-ledger' ) ) );
    }

    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    $message = '';
    $new_status_html = '';
    $new_action_link_html = ''; // To rebuild the opposite action link

    $base_action_url = '#'; // For AJAX, href is # initially
    $ajax_nonce_new = wp_create_nonce('wcsl_ajax_manage_notification_' . $notification_id);


    if ( 'mark_read' === $sub_action ) {
        $wpdb->update( $table_name, array( 'is_read' => 1 ), array( 'id' => $notification_id ), array('%d'), array('%d') );
        $message = __( 'Notification marked as read.', 'wp-client-support-ledger' );
        $new_status_html = esc_html__( 'Read', 'wp-client-support-ledger' );
        // Generate "Mark Unread" link
        $new_action_link_html = '<span class="mark-unread"><a href="#" class="wcsl-notification-action" data-action="mark_unread" data-nonce="'.esc_attr($ajax_nonce_new).'" data-notification-id="'.esc_attr($notification_id).'">'.esc_html__( 'Mark Unread', 'wp-client-support-ledger' ).'</a> | </span>';
    } elseif ( 'mark_unread' === $sub_action ) {
        $wpdb->update( $table_name, array( 'is_read' => 0 ), array( 'id' => $notification_id ), array('%d'), array('%d') );
        $message = __( 'Notification marked as unread.', 'wp-client-support-ledger' );
        $new_status_html = '<strong>' . esc_html__( 'Unread', 'wp-client-support-ledger' ) . '</strong>';
        // Generate "Mark Read" link
        $new_action_link_html = '<span class="mark-read"><a href="#" class="wcsl-notification-action" data-action="mark_read" data-nonce="'.esc_attr($ajax_nonce_new).'" data-notification-id="'.esc_attr($notification_id).'">'.esc_html__( 'Mark Read', 'wp-client-support-ledger' ).'</a> | </span>';
    } elseif ( 'delete' === $sub_action ) {
        $wpdb->delete( $table_name, array( 'id' => $notification_id ), array('%d') );
        $message = __( 'Notification deleted.', 'wp-client-support-ledger' );
        // No new link or status needed for delete
    } else {
        wp_send_json_error( array( 'message' => __( 'Invalid sub-action.', 'wp-client-support-ledger' ) ) );
    }
    
    // Add delete link to the new action HTML if not deleting
    if ($sub_action !== 'delete') {
         $new_action_link_html .= '<span class="delete"><a href="#" class="wcsl-notification-action wcsl-delete-notification-ajax" data-action="delete" data-nonce="'.esc_attr($ajax_nonce_new).'" data-notification-id="'.esc_attr($notification_id).'">'.esc_html__( 'Delete', 'wp-client-support-ledger' ).'</a></span>';
    }


    wp_send_json_success( array( 
        'message' => $message,
        'new_status_html' => $new_status_html,
        'new_action_link_html' => $new_action_link_html
    ) );
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
    $clients_per_page = 10; // You can make this dynamic or a setting if needed

    ob_start(); // Start output buffering to capture HTML

    // --- Build WP_Query arguments for fetching clients ---
    $clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => $clients_per_page,
        'paged'          => $paged,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish'
    );

    // Add search term if provided
    if ( !empty($search_term) ) {
        $clients_query_args['s'] = $search_term;
    }

    // TODO: Implement client-specific filtering based on logged-in user if needed
    // Example:
    // $user_id = get_current_user_id();
    // $associated_client_id = get_user_meta($user_id, '_wcsl_associated_client_id', true);
    // if ($associated_client_id) {
    //    $clients_query_args['post__in'] = array(intval($associated_client_id));
    // }

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
                    $client_id = get_the_ID(); // Get the ID of the current client in the loop
                    $client_name = get_the_title( $client_id ); // *** Use $client_id to get the correct client title ***

                    $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
                    
                    // Inner query to calculate hours for this specific client
                    $client_tasks_args = array(
                        'post_type'      => 'client_task',
                        'posts_per_page' => -1,
                        'post_status'    => 'publish',
                        'meta_query'     => array(
                            'relation' => 'AND',
                            array(
                                'key'     => '_wcsl_related_client_id',
                                'value'   => $client_id, // Filter tasks for this client
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
                    wp_reset_postdata(); // Reset after the inner task query

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
        // Pagination for AJAX loaded content
        $total_pages = $clients_query->max_num_pages;
        if ($total_pages > 1) {
            $pagination_base_url_args = array(
                'wcsl_action'  => 'view_month_details_frontend', // For context if link is copied
                'wcsl_month'   => $target_month,
                'wcsl_year'    => $target_year,
            );
            // Add search term to pagination links if it's active
            if (!empty($search_term)) {
                // Ensure the GET param for search matches what your JS expects or sends.
                // If JS sends 'search_term' and expects 's_clients' in URL for pagination, adjust here.
                // For simplicity, let's assume JS will re-send 'search_term' based on input field.
                // So, we don't strictly need to add search_term to paginate_links base here if JS handles it.
            }

            // The base for paginate_links should be the current page URL.
            // The JS will pick up the 'wcsl_paged_clients' from the href.
            $base_for_paginate = add_query_arg( $pagination_base_url_args, get_permalink( get_queried_object_id() ?: get_the_ID() ) );
            $base_for_paginate = add_query_arg( 'wcsl_paged_clients', '%#%', $base_for_paginate );


            echo '<div class="wcsl-pagination">';
            echo paginate_links( array(
                'base'      => $base_for_paginate,
                'format'    => '', // Using '%#%' in base
                'current'   => $paged,
                'total'     => $total_pages,
                'prev_text' => __( '« Prev', 'wp-client-support-ledger' ),
                'next_text' => __( 'Next »', 'wp-client-support-ledger' ),
                'add_args'  => false // We have built the full base URL
            ) );
            echo '</div>';
        }
        wp_reset_postdata(); // After the main $clients_query loop
    else :
        echo '<p>' . esc_html__( 'No clients found matching your search criteria for this period.', 'wp-client-support-ledger' ) . '</p>';
    endif;

    $html_output = ob_get_clean();
    wp_send_json_success( array( 'html' => $html_output ) );
}
// Add these lines at the end of your includes/shortcodes.php or in a dedicated hooks file.
add_action( 'wp_ajax_wcsl_load_frontend_client_summary', 'wcsl_ajax_load_frontend_client_summary_handler' );
