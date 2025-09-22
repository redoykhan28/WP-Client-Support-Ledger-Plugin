<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parses a time string (e.g., "1h 30m", "45m", "2h") into total minutes.
 *
 * @param string $time_string The time string to parse.
 * @return int Total minutes.
 */
function wcsl_parse_time_string_to_minutes( $time_string ) {
    if ( empty( trim( $time_string ) ) ) {
        return 0;
    }

    $total_minutes = 0;
    $time_string = strtolower( trim( $time_string ) );

    if ( preg_match( '/(\d+)\s*h/', $time_string, $matches_h ) ) {
        $total_minutes += intval( $matches_h[1] ) * 60;
    }

    if ( preg_match( '/(\d+)\s*m/', $time_string, $matches_m ) ) {
        $total_minutes += intval( $matches_m[1] );
    }
    
    if ( !preg_match( '/[hm]/', $time_string ) && is_numeric( $time_string ) ) {
         $total_minutes = intval( $time_string );
    }

    return $total_minutes;
}

/**
 * Formats total minutes into a time string (e.g., "1h 30m").
 *
 * @param int $total_minutes The total minutes.
 * @return string The formatted time string.
 */
function wcsl_format_minutes_to_time_string( $total_minutes ) {
    if ( $total_minutes <= 0 ) {
        return '0m';
    }

    $hours   = floor( $total_minutes / 60 );
    $minutes = $total_minutes % 60;
    $output  = '';

    if ( $hours > 0 ) {
        $output .= $hours . 'h';
    }

    if ( $minutes > 0 ) {
        if ( $hours > 0 ) {
            $output .= ' '; 
        }
        $output .= $minutes . 'm';
    }
    
    if ( empty( $output ) && $total_minutes > 0) { // Handles cases like 0.5 minutes if precision was higher
        return '0m'; // Default to 0m if output is empty but total was >0 (e.g. less than 1 min)
    }

    return empty($output) ? '0m' : $output; // Ensure '0m' if both hours and minutes are zero
}

/**
 * Fetches and processes client summary data for a given month and year.
 *
 * @param int $target_month The month (1-12).
 * @param int $target_year The year.
 * @param int|null $specific_client_id (Optional) ID of a specific client to fetch data for.
 * @return array An array of client summary data, or an empty array.
 */
function wcsl_get_client_summary_data( $target_month, $target_year, $specific_client_id = null ) {
    $summary_data = array();

    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $target_month, 1, $target_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $target_month + 1, 0, $target_year ) );

    $clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish'
    );

    if ( is_numeric( $specific_client_id ) && $specific_client_id > 0 ) {
        $clients_query_args['post__in'] = array( intval( $specific_client_id ) );
    }

    $clients_query = new WP_Query( $clients_query_args );

    if ( $clients_query->have_posts() ) {
        while ( $clients_query->have_posts() ) : $clients_query->the_post();
            $client_id = get_the_ID();
            $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );

            $client_tasks_args = array(
                'post_type'      => 'client_task',
                'posts_per_page' => -1,
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

            $summary_data[] = array(
                'id'                 => $client_id,
                'name'               => get_the_title( $client_id ),
                'contracted_str'     => !empty($contracted_hours_str) ? $contracted_hours_str : 'N/A',
                'total_spent_str'    => wcsl_format_minutes_to_time_string( $total_minutes_spent_this_month ),
                'billable_hours_str' => wcsl_format_minutes_to_time_string( $billable_minutes ),
            );
        endwhile;
        wp_reset_postdata();
    }
    return $summary_data;
}


/**
 * Get the full name of the WCSL notifications table.
 *
 * @return string The notifications table name.
 */
function wcsl_get_notifications_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'wcsl_notifications';
}


/**
 * Adds a new notification to the database and prunes old ones if limit is exceeded.
 *
 * @param string $type Type of notification (e.g., 'hours_exceeded', 'new_client').
 * @param string $message The notification message.
 * @param int    $related_object_id (Optional) ID of the related object.
 * @param string $related_object_type (Optional) Type of the related object (e.g., 'client', 'task').
 * @param int    $user_id (Optional) User ID if notification is user-specific, 0 for general admin.
 * @return bool|int False on failure, Insert ID on success.
 */
function wcsl_add_notification( $type, $message, $related_object_id = 0, $related_object_type = '', $user_id = 0 ) {
    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();

    if ( empty( $type ) || empty( $message ) ) {
        return false;
    }

    $data = array(
        'user_id'             => intval( $user_id ),
        'type'                => sanitize_key( $type ),
        'message'             => wp_kses_post( $message ),
        'related_object_id'   => intval( $related_object_id ),
        'related_object_type' => sanitize_key( $related_object_type ),
        'is_read'             => 0,
        'created_at'          => current_time( 'mysql', 1 ), // GMT time
    );

    $format = array(
        '%d', '%s', '%s', '%d', '%s', '%d', '%s',
    );

    $result = $wpdb->insert( $table_name, $data, $format );

    if ( $result ) {
        $new_notification_id = $wpdb->insert_id;

        // --- Prune old notifications if limit is exceeded ---
        // Use constant defined in main plugin file, or default to 100
        $max_notifications = defined('WCSL_MAX_NOTIFICATIONS') ? WCSL_MAX_NOTIFICATIONS : 100;

        // Get current total count. 
        // If notifications become user-specific, this COUNT query would need a WHERE clause.
        $current_count_sql = "SELECT COUNT(id) FROM {$table_name}";
        // if ($user_id_specific_pruning_enabled) { // Example for user-specific pruning
        //    $current_count_sql .= $wpdb->prepare(" WHERE user_id = %d", $some_user_id_context);
        // }
        $current_count = $wpdb->get_var( $current_count_sql );

        if ( $current_count > $max_notifications ) {
            $num_to_delete = $current_count - $max_notifications;

            // SQL to find the IDs of the oldest notifications to delete
            // If notifications are user-specific, this SELECT query would also need a WHERE clause.
            $oldest_ids_sql = $wpdb->prepare(
                "SELECT id FROM {$table_name} ORDER BY created_at ASC, id ASC LIMIT %d",
                $num_to_delete
            );
            $ids_to_delete = $wpdb->get_col( $oldest_ids_sql );

            if ( ! empty( $ids_to_delete ) ) {
                // Constructing a safe IN clause for deletion
                // e.g., "DELETE FROM table WHERE id IN (%d, %d, %d)"
                $ids_placeholder = implode( ', ', array_fill( 0, count( $ids_to_delete ), '%d' ) );
                $delete_sql = "DELETE FROM {$table_name} WHERE id IN ({$ids_placeholder})";
                // If user-specific, the DELETE query would also need "AND user_id = %d" or similar.
                
                // Prepare the query with the array of IDs.
                // $wpdb->prepare expects scalar values after the query string, not an array directly for IN clause placeholders.
                // So, we pass $ids_to_delete as separate arguments using call_user_func_array or directly if count is fixed/small.
                // However, $wpdb->query can handle this more directly if the $ids_placeholder is built correctly.
                // A safer way with $wpdb->prepare for dynamic IN clauses can be a bit more involved if not using $wpdb->query.
                // For $wpdb->query, it's crucial that $ids_placeholder ONLY contains %d and $ids_to_delete ONLY contains integers.
                
                // Simpler approach with $wpdb->query which is fine since $ids_to_delete contains only integers
                // and $ids_placeholder is built from %d.
                // $wpdb->query( $wpdb->prepare( $delete_sql, $ids_to_delete ) ); // This line is tricky with prepare and array for IN
                
                // More robust way for IN clause with $wpdb->delete or $wpdb->query:
                $wpdb->query( "DELETE FROM {$table_name} WHERE id IN (" . implode( ',', array_map( 'absint', $ids_to_delete ) ) . ")" );
                // error_log("WCSL Pruned Notifications: Deleted " . count($ids_to_delete) . " old notifications.");
            }
        }
        // --- End Pruning ---

        return $new_notification_id;
    }
    return false;
}


/**
 * Gets the count of unread notifications.
 *
 * @return int Count of unread notifications.
 */
/**
 * Gets the count of unread notifications FOR THE CURRENT USER.
 * Admins see notifications for user_id = 0.
 * Other users will see notifications for their own user_id.
 *
 * @return int Count of unread notifications.
 */
function wcsl_get_unread_notification_count() {
    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    
    // <<< CORRECTION: Default to fetching notifications for the admin (user_id = 0) >>>
    $target_user_id = 0; 
    
    // In a future step, if we build a frontend portal where clients/employees
    // need their own unread count, we would add logic here to check their role
    // and use get_current_user_id() instead. But for the admin menu, this is correct.

    $sql = $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_name} WHERE is_read = 0 AND user_id = %d",
        $target_user_id
    ); 
    
    $count = $wpdb->get_var( $sql );
    return intval( $count );
}




/**
 * Fetches a paginated list of ONLY clients with billable hours for a given month/year.
 * Also returns the total count of billable clients for pagination.
 *
 * @param int $target_month The month (1-12).
 * @param int $target_year The year.
 * @param int $clients_per_page Number of clients to show per page.
 * @param int $paged The current page number.
 * @return array An array containing 'clients' (the list for the current page) and 'total_clients' (the total count).
 */
function wcsl_get_billable_clients_for_month( $target_month, $target_year, $clients_per_page = 10, $paged = 1 ) {
    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $target_month, 1, $target_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $target_month + 1, 0, $target_year ) );

    $all_clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => -1, // Get all clients to calculate totals
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish'
    );
    $clients_query = new WP_Query( $all_clients_query_args );

    $billable_clients_data = array();

    if ( $clients_query->have_posts() ) {
        while ( $clients_query->have_posts() ) : $clients_query->the_post();
            $client_id = get_the_ID();
            $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
            $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );

            // Calculate total spent hours for this client for the given month
            $client_tasks_args = array(
                'post_type'      => 'client_task',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array( 'key' => '_wcsl_related_client_id', 'value' => $client_id, 'compare' => '=' ),
                    array( 'key' => '_wcsl_task_date', 'value'   => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE' )
                )
            );
            $client_tasks_query = new WP_Query( $client_tasks_args );
            
            // <<< CHANGE: Initialize two separate counters
            $total_minutes_spent_this_month = 0;
            $total_support_minutes_this_month = 0;

            if ( $client_tasks_query->have_posts() ) {
                while ( $client_tasks_query->have_posts() ) : $client_tasks_query->the_post();
                    $task_id_inner = get_the_ID();
                    $hours_spent_str = get_post_meta( $task_id_inner, '_wcsl_hours_spent_on_task', true );
                    $minutes_for_task = wcsl_parse_time_string_to_minutes( $hours_spent_str );

                    // <<< CHANGE: Get the task type
                    $task_type = get_post_meta( $task_id_inner, '_wcsl_task_type', true );

                    // Always add to the total spent time
                    $total_minutes_spent_this_month += $minutes_for_task;

                    // <<< CHANGE: Only add to the support time if it's a support task
                    if ( $task_type === 'support' || empty( $task_type ) ) {
                        $total_support_minutes_this_month += $minutes_for_task;
                    }
                endwhile;
            }
            wp_reset_postdata(); // Reset inner task loop

            // <<< CHANGE: Calculate billable minutes based ONLY on support hours
            $billable_minutes = 0;
            // The condition to check is if support time exceeds contracted time
            if ( $contracted_minutes >= 0 && $total_support_minutes_this_month > $contracted_minutes ) {
                $billable_minutes = $total_support_minutes_this_month - $contracted_minutes;
            } elseif ( $contracted_minutes < 0 && $total_support_minutes_this_month > 0 ) { // No contract, all support time is billable
                $billable_minutes = $total_support_minutes_this_month;
            }

            // *** CRITICAL STEP: Only add client to our list if they have billable hours ***
            if ( $billable_minutes > 0 ) {
                $billable_clients_data[] = array(
                    'id'                 => $client_id,
                    'name'               => get_the_title( $client_id ),
                    'contracted_str'     => !empty($contracted_hours_str) ? $contracted_hours_str : 'N/A',
                    'total_spent_str'    => wcsl_format_minutes_to_time_string( $total_minutes_spent_this_month ),
                    'billable_hours_str' => wcsl_format_minutes_to_time_string( $billable_minutes ),
                );
            }
        endwhile;
        wp_reset_postdata(); // Reset outer client loop
    }

    // --- Now, handle pagination for the filtered list of billable clients ---
    $total_billable_clients = count( $billable_clients_data );
    
    // Use array_slice to get just the clients for the current page
    $offset = ( $paged - 1 ) * $clients_per_page;
    $paginated_clients = array_slice( $billable_clients_data, $offset, $clients_per_page );

    return array(
        'clients'        => $paginated_clients,      // The clients for the current page
        'total_clients'  => $total_billable_clients, // The total number of billable clients found
        'per_page'       => $clients_per_page,
        'current_page'   => $paged
    );
}


/**
 * Gathers all necessary data for generating a single invoice for a client for a specific month.
 *
 * @param int $client_id The ID of the client.
 * @param int $target_month The month (1-12) for the invoice period.
 * @param int $target_year The year for the invoice period.
 * @return array|false An array of all invoice data, or false if client not found.
 */
function wcsl_get_data_for_invoice( $client_id, $target_month, $target_year ) {
    $client_post = get_post( $client_id );
    if ( ! $client_post || 'client' !== $client_post->post_type ) {
        return false;
    }

    $client_data = array(
        'name'           => $client_post->post_title,
        'billing_address'=> get_post_meta( $client_id, '_wcsl_client_billing_address', true ),
        'contact_email'  => get_post_meta( $client_id, '_wcsl_client_contact_email', true ),
        'hourly_rate'    => floatval( get_post_meta( $client_id, '_wcsl_client_hourly_rate', true ) ),
        'tax_rate_percent' => floatval( get_post_meta( $client_id, '_wcsl_client_tax_rate', true ) ),
    );

    $invoice_settings = get_option( 'wcsl_invoice_settings', array() );
    $company_data = array(
        'name'         => isset( $invoice_settings['company_name'] ) ? $invoice_settings['company_name'] : get_bloginfo('name'),
        'address'      => isset( $invoice_settings['company_address'] ) ? $invoice_settings['company_address'] : '',
        'email'        => isset( $invoice_settings['company_email'] ) ? $invoice_settings['company_email'] : get_option('admin_email'),
        'phone'        => isset( $invoice_settings['company_phone'] ) ? $invoice_settings['company_phone'] : '',
        'logo_url'     => isset( $invoice_settings['invoice_logo'] ) ? $invoice_settings['invoice_logo'] : '',
        'footer_text'  => isset( $invoice_settings['footer_text'] ) ? $invoice_settings['footer_text'] : '',
        'currency_symbol' => isset( $invoice_settings['currency_symbol'] ) && !empty($invoice_settings['currency_symbol']) ? $invoice_settings['currency_symbol'] : '$',
    );

    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $target_month, 1, $target_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $target_month + 1, 0, $target_year ) );
    
    $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
    $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );
    
    $client_tasks_args = array(
        'post_type' => 'client_task', 'posts_per_page' => -1, 'post_status' => 'publish',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_wcsl_related_client_id', 'value' => $client_id ),
            array( 'key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE' ),
        ),
    );
    $client_tasks_query = new WP_Query( $client_tasks_args );
    
    // <<< CHANGE: Initialize two counters >>>
    $total_minutes_spent_this_month = 0;
    $total_support_minutes_this_month = 0;

    if ( $client_tasks_query->have_posts() ) {
        while ( $client_tasks_query->have_posts() ) : $client_tasks_query->the_post();
            $task_id_inner = get_the_ID();
            $hours_spent_str = get_post_meta( $task_id_inner, '_wcsl_hours_spent_on_task', true );
            $minutes_for_task = wcsl_parse_time_string_to_minutes( $hours_spent_str );

            // <<< CHANGE: Get the task type >>>
            $task_type = get_post_meta( $task_id_inner, '_wcsl_task_type', true );

            // Always add to total time spent
            $total_minutes_spent_this_month += $minutes_for_task;

            // <<< CHANGE: Only add to support time if it's a support task >>>
            if ( $task_type === 'support' || empty( $task_type ) ) {
                $total_support_minutes_this_month += $minutes_for_task;
            }
        endwhile;
    }
    wp_reset_postdata();

    // <<< CHANGE: Calculate billable minutes based ONLY on support hours >>>
    $billable_minutes = 0;
    if ( $contracted_minutes >= 0 && $total_support_minutes_this_month > $contracted_minutes ) {
        $billable_minutes = $total_support_minutes_this_month - $contracted_minutes;
    } elseif ( $contracted_minutes < 0 && $total_support_minutes_this_month > 0) { // No contract defined, all support time is billable
        $billable_minutes = $total_support_minutes_this_month;
    }
    
    $billable_hours_decimal = round( $billable_minutes / 60, 2 );

    $subtotal = $billable_hours_decimal * $client_data['hourly_rate'];
    $tax_amount = ( $subtotal * $client_data['tax_rate_percent'] ) / 100;
    $grand_total = $subtotal + $tax_amount;

    $next_invoice_num_from_settings = isset($invoice_settings['next_invoice_number']) ? intval($invoice_settings['next_invoice_number']) : 1001;
    $last_used_invoice_num = get_option( 'wcsl_last_invoice_number', $next_invoice_num_from_settings - 1 );
    $current_invoice_number = $last_used_invoice_num + 1;
    update_option( 'wcsl_last_invoice_number', $current_invoice_number );

    $invoice_date = current_time('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+30 days', strtotime($invoice_date)));

    return array(
        'company' => $company_data,
        'client'  => $client_data,
        'invoice' => array(
            'number'     => $current_invoice_number, 'date' => $invoice_date, 'due_date' => $due_date,
            'period_month_name' => date_i18n( 'F', mktime( 0, 0, 0, $target_month, 1, $target_year ) ),
            'period_year' => $target_year
        ),
        'line_items' => array(
            array(
                'description' => sprintf( __('Overage Support Hours for %s %s', 'wp-client-support-ledger'), date_i18n( 'F', mktime( 0, 0, 0, $target_month, 1, $target_year ) ), $target_year),
                'quantity'    => $billable_hours_decimal, 'unit_price'  => $client_data['hourly_rate'], 'amount' => $subtotal
            ),
        ),
        'totals' => array(
            'subtotal'    => $subtotal, 'tax_amount'  => $tax_amount, 'grand_total' => $grand_total, 'currency' => $company_data['currency_symbol']
        )
    );
}


/**
 * Gets key metrics for the dashboard for a given month and year.
 *
 * @param int $month The month to calculate for.
 *- * @param int $year  The year to calculate for.
 * @return array An array containing total_hours, billable_hours, and active_tasks.
 */
function wcsl_get_dashboard_metrics( $month, $year ) {
    $metrics = array(
        'total_minutes'   => 0,
        'billable_minutes' => 0,
        'active_tasks'    => 0,
    );

    $first_day = date( 'Y-m-d', mktime( 0, 0, 0, $month, 1, $year ) );
    $last_day  = date( 'Y-m-d', mktime( 0, 0, 0, $month + 1, 0, $year ) );

    // Get all clients to loop through for billable calculations
    $clients_query = new WP_Query( array('post_type' => 'client', 'posts_per_page' => -1, 'fields' => 'ids' ) );
    $client_ids = $clients_query->posts;

    if ( ! empty( $client_ids ) ) {
        $total_billable_for_period = 0;

        foreach ( $client_ids as $client_id ) {
            $client_tasks_args = array(
                'post_type' => 'client_task', 'posts_per_page' => -1,
                'meta_query' => array( 'relation' => 'AND',
                    array( 'key' => '_wcsl_related_client_id', 'value' => $client_id ),
                    array( 'key' => '_wcsl_task_date', 'value' => array( $first_day, $last_day ), 'compare' => 'BETWEEN', 'type' => 'DATE' )
                )
            );
            $client_tasks_query = new WP_Query( $client_tasks_args );

            $total_support_minutes = 0;
            if ( $client_tasks_query->have_posts() ) {
                while ( $client_tasks_query->have_posts() ) : $client_tasks_query->the_post();
                    $task_type = get_post_meta( get_the_ID(), '_wcsl_task_type', true );
                    if ( $task_type !== 'fixing' ) {
                        $total_support_minutes += wcsl_parse_time_string_to_minutes( get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true ) );
                    }
                endwhile;
            }
            wp_reset_postdata();

            $contracted_minutes = wcsl_parse_time_string_to_minutes( get_post_meta( $client_id, '_wcsl_contracted_support_hours', true ) );
            $total_billable_for_period += max( 0, $total_support_minutes - $contracted_minutes );
        }
        $metrics['billable_minutes'] = $total_billable_for_period;
    }

    // Get total hours and active tasks for the entire month
    $all_tasks_args = array(
        'post_type' => 'client_task', 'posts_per_page' => -1,
        'meta_query' => array(
            array( 'key' => '_wcsl_task_date', 'value' => array( $first_day, $last_day ), 'compare' => 'BETWEEN', 'type' => 'DATE' )
        )
    );
    $all_tasks_query = new WP_Query( $all_tasks_args );

    if ( $all_tasks_query->have_posts() ) {
        while ( $all_tasks_query->have_posts() ) : $all_tasks_query->the_post();
            $metrics['total_minutes'] += wcsl_parse_time_string_to_minutes( get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true ) );
            $status = get_post_meta( get_the_ID(), '_wcsl_task_status', true );
            
            // <<< CORRECTED LOGIC: An active task is one that is NOT completed or billed.
            if ( ! in_array( $status, array( 'completed', 'billed' ) ) ) {
                $metrics['active_tasks']++;
            }
        endwhile;
    }
    wp_reset_postdata();

    return $metrics;
}

/**
 * Gets a list of clients who are nearing their monthly contracted hours limit.
 *
 * @param int   $month    The month to check.
 * @param int   $year     The year to check.
 * @param float $threshold The percentage threshold (e.g., 80.0 for 80%).
 * @return array An array of client data for the watchlist.
 */
function wcsl_get_clients_nearing_limit( $month, $year, $threshold = 80.0 ) {
    $watchlist = array();
    $first_day = date( 'Y-m-d', mktime( 0, 0, 0, $month, 1, $year ) );
    $last_day  = date( 'Y-m-d', mktime( 0, 0, 0, $month + 1, 0, $year ) );

    $clients_query = new WP_Query( array('post_type' => 'client', 'posts_per_page' => -1) );
    if ( $clients_query->have_posts() ) {
        while ( $clients_query->have_posts() ) : $clients_query->the_post();
            $client_id = get_the_ID();
            // <<< CORRECTION: Get and store the client's name and link HERE, inside the correct loop. >>>
            $client_name = get_the_title();
            $client_link = get_edit_post_link();

            $contracted_minutes = wcsl_parse_time_string_to_minutes( get_post_meta( $client_id, '_wcsl_contracted_support_hours', true ) );

            if ( $contracted_minutes > 0 ) {
                $total_support_minutes_spent = 0;
                $tasks_query = new WP_Query( array(
                    'post_type' => 'client_task', 'posts_per_page' => -1,
                    'meta_query' => array('relation' => 'AND',
                        array('key' => '_wcsl_related_client_id', 'value' => $client_id),
                        array('key' => '_wcsl_task_date', 'value' => array( $first_day, $last_day ), 'compare' => 'BETWEEN', 'type' => 'DATE')
                    )
                ));
                if ( $tasks_query->have_posts() ) {
                    while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
                        $task_id = get_the_ID();
                        $task_type = get_post_meta( $task_id, '_wcsl_task_type', true );
                        if ( $task_type !== 'fixing' ) {
                            $total_support_minutes_spent += wcsl_parse_time_string_to_minutes( get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true ) );
                        }
                    endwhile;
                }
                wp_reset_postdata();

                $percentage_used = round( ( $total_support_minutes_spent / $contracted_minutes ) * 100, 1 );

                if ( $percentage_used >= $threshold ) {
                    // <<< CORRECTION: Use the stored client name and link. >>>
                    $watchlist[] = array(
                        'name' => $client_name,
                        'link' => $client_link,
                        'usage_str' => wcsl_format_minutes_to_time_string($total_support_minutes_spent) . ' / ' . wcsl_format_minutes_to_time_string($contracted_minutes),
                        'percentage' => $percentage_used
                    );
                }
            }
        endwhile;
    }
    wp_reset_postdata();

    usort($watchlist, function($a, $b) {
        return $b['percentage'] <=> $a['percentage'];
    });

    return array_slice( $watchlist, 0, 8 );
}



/**
 * Gets the 5 most recently modified tasks.
 *
 * @return array An array of recent task data.
 */
function wcsl_get_recent_activity() {
    $activity = array();
    $tasks_query = new WP_Query( array(
        'post_type' => 'client_task',
        'posts_per_page' => 5,
        'orderby' => 'modified',
        'order' => 'DESC'
    ));

    if ( $tasks_query->have_posts() ) {
        while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
            $client_id = get_post_meta( get_the_ID(), '_wcsl_related_client_id', true );
            $activity[] = array(
                'task_title' => get_the_title(),
                'task_link' => get_edit_post_link(),
                'client_name' => $client_id ? get_the_title($client_id) : 'N/A',
                'client_link' => $client_id ? get_edit_post_link($client_id) : '#',
                'modified_date' => get_the_modified_date('M j, Y g:i a')
            );
        endwhile;
    }
    wp_reset_postdata();

    return $activity;
}


/**
 * Displays a color-coded status badge.
 *
 * @param string $status The status slug (e.g., 'in-progress').
 */
function wcsl_display_status_badge( $status ) {
    // This correctly handles statuses with spaces like "On Hold"
    $status_label = ucwords( str_replace( '-', ' ', $status ) );
    
    // <<< CORRECTION: Convert the status to a clean, lowercase, hyphenated slug for the CSS class >>>
    $status_slug = strtolower( str_replace( ' ', '-', $status ) );
    $status_class = sanitize_html_class( 'status-' . $status_slug );

    if ( empty( $status ) ) {
        $status_class = 'status-unknown';
        $status_label = 'N/A';
    }

    echo '<span class="wcsl-status-badge ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span>';
}


/**
 * --------------------------------------------------------------------------
 * Invoice Status Helper Functions
 * --------------------------------------------------------------------------
 */

/**
 * Gets the invoice data (including status) for a specific client and month/year.
 *
 * @param int $client_id The ID of the client.
 * @param int $month     The month (1-12).
 * @param int $year      The year.
 * @return object|null The invoice data object from the database, or null if not found.
 */
function wcsl_get_invoice_data( $client_id, $month, $year ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wcsl_invoices';

    $invoice = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE client_id = %d AND month = %d AND year = %d",
        $client_id, $month, $year
    ) );

    return $invoice;
}

/**
 * Creates or updates an invoice record in the database.
 *
 * @param array $data An associative array of data to insert/update.
 * @return bool|int False on failure, ID of the record on success.
 */
function wcsl_create_or_update_invoice_record( $data ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wcsl_invoices';

    // Check if a record already exists for this client/month/year
    $existing_invoice = wcsl_get_invoice_data( $data['client_id'], $data['month'], $data['year'] );

    if ( $existing_invoice ) {
        // Update the existing record
        $where = array( 'id' => $existing_invoice->id );
        $result = $wpdb->update( $table_name, $data, $where );
        return ( false !== $result ) ? $existing_invoice->id : false;
    } else {
        // Insert a new record
        $result = $wpdb->insert( $table_name, $data );
        return ( false !== $result ) ? $wpdb->insert_id : false;
    }
}

/**
 * Updates the status of a specific invoice record.
 *
 * @param int    $invoice_id The ID of the invoice record in our custom table.
 * @param string $new_status The new status (e.g., 'paid', 'void').
 * @return bool True on success, false on failure.
 */
function wcsl_update_invoice_status( $invoice_id, $new_status ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wcsl_invoices';

    $data_to_update = array( 'status' => sanitize_key( $new_status ) );
    
    // If we are marking as paid, also set the paid_at date
    if ( 'paid' === $new_status ) {
        $data_to_update['paid_at'] = current_time('mysql');
    }

    $where = array( 'id' => intval( $invoice_id ) );
    $result = $wpdb->update( $table_name, $data_to_update, $where );

    return ( false !== $result );
}

/**
 * Gets the linked Client CPT ID for a given WordPress user ID.
 *
 * @param int $user_id The WordPress user ID.
 * @return int The Client CPT post ID, or 0 if not found.
 */
function wcsl_get_client_id_for_user( $user_id ) {
    if ( ! $user_id ) {
        return 0;
    }

    // Query the 'client' CPT to find one that has our user ID in its meta field.
    $args = array(
        'post_type'      => 'client',
        'posts_per_page' => 1,
        'meta_key'       => '_wcsl_linked_user_id',
        'meta_value'     => $user_id,
        'fields'         => 'ids', // We only need the ID, which is efficient.
    );

    $client_query = new WP_Query( $args );

    if ( $client_query->have_posts() ) {
        return $client_query->posts[0];
    }

    return 0;
}

/**
 * Gets a paginated list of tasks for a specific client and month/year.
 *
 * @param int    $client_id The ID of the client CPT.
 * @param int    $month     The month to display.
 * @param int    $year      The year to display.
 * @param int    $per_page  Tasks per page.
 * @param int    $paged     Current page number.
 * @return WP_Query The WordPress query object containing the tasks.
 */
function wcsl_get_tasks_for_client_by_month( $client_id, $month, $year, $per_page = 20, $paged = 1 ) {
    $first_day = date( 'Y-m-d', mktime( 0, 0, 0, $month, 1, $year ) );
    $last_day  = date( 'Y-m-d', mktime( 0, 0, 0, $month + 1, 0, $year ) );

    $args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'meta_key'       => '_wcsl_task_date',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_wcsl_related_client_id',
                'value'   => $client_id,
            ),
            array(
                'key'     => '_wcsl_task_date',
                'value'   => array( $first_day, $last_day ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ),
        ),
    );

    return new WP_Query( $args );
}


/**
 * Helper function to generate a dropdown filter for the PORTAL task list.
 *
 * @param string $query_var The GET parameter name (e.g., 'filter_client').
 * @param array  $options   An associative array of value => label for the dropdown.
 * @param string $label     The default label for the dropdown (e.g., 'All Clients').
 * @param string $current_value The currently selected value.
 */
function wcsl_display_portal_task_filter_dropdown( $query_var, $options, $label, $current_value ) {
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
 * Gets and calculates summary data for a single client for a given month.
 *
 * @param int $client_id The ID of the client.
 * @param int $target_month The month (1-12).
 * @param int $target_year The year.
 * @return array An array of summary data for that client.
 */
function wcsl_get_single_client_summary_for_month( $client_id, $target_month, $target_year ) {
    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $target_month, 1, $target_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $target_month + 1, 0, $target_year ) );

    $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );

    $client_tasks_args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array( 'key' => '_wcsl_related_client_id', 'value' => $client_id ),
            array( 'key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE' )
        )
    );
    $client_tasks_query = new WP_Query( $client_tasks_args );
    
    $total_minutes_spent = 0;
    $total_support_minutes = 0;
    $total_fixing_minutes = 0;

    if ( $client_tasks_query->have_posts() ) {
        while ( $client_tasks_query->have_posts() ) : $client_tasks_query->the_post();
            $minutes_for_task = wcsl_parse_time_string_to_minutes( get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true ) );
            $task_type = get_post_meta( get_the_ID(), '_wcsl_task_type', true );
            
            $total_minutes_spent += $minutes_for_task;
            
            if ( $task_type === 'fixing' ) {
                $total_fixing_minutes += $minutes_for_task;
            } else {
                $total_support_minutes += $minutes_for_task;
            }
        endwhile;
    }
    wp_reset_postdata();
    
    $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );
    $billable_minutes = max( 0, $total_support_minutes - $contracted_minutes );

    return array(
        'contracted_str'    => !empty($contracted_hours_str) ? $contracted_hours_str : 'N/A',
        'total_spent_str'   => wcsl_format_minutes_to_time_string( $total_minutes_spent ),
        'fixing_str'        => wcsl_format_minutes_to_time_string( $total_fixing_minutes ),
        'billable_str'      => wcsl_format_minutes_to_time_string( $billable_minutes ),
        'billable_minutes'  => $billable_minutes,
    );
}


/**
 * Gets the count of unread notifications for a specific user ID.
 *
 * @param int $user_id The ID of the user to check.
 * @return int Count of unread notifications.
 */
function wcsl_get_unread_notification_count_for_user( $user_id ) {
    if ( ! $user_id > 0 ) {
        return 0;
    }
    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_name} WHERE is_read = 0 AND user_id = %d",
        $user_id
    ) ); 
    return intval( $count );
}






/**
 * Gets the linked Employee CPT ID for a given WordPress user ID.
 *
 * @param int $user_id The WordPress user ID.
 * @return int The Employee CPT post ID, or 0 if not found.
 */
function wcsl_get_employee_id_for_user( $user_id ) {
    if ( ! $user_id > 0 ) {
        return 0;
    }

    $args = array(
        'post_type'      => 'employee',
        'posts_per_page' => 1,
        'meta_key'       => '_wcsl_linked_user_id',
        'meta_value'     => $user_id,
        'fields'         => 'ids', // We only need the ID for efficiency.
    );

    $employee_query = new WP_Query( $args );

    if ( $employee_query->have_posts() ) {
        return $employee_query->posts[0];
    }

    return 0;
}


/**
 * Handles the submission of the frontend "Edit Task" form.
 */
/**
 * Handles the submission of the frontend "Edit Task" form.
 */
function wcsl_handle_frontend_edit_task_submission() {
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

    if ( ! $task_id ) {
        wp_die( 'Invalid task ID.' );
    }

    // 1. Security Checks
    if ( ! isset( $_POST['wcsl_edit_task_nonce'] ) || ! wp_verify_nonce( $_POST['wcsl_edit_task_nonce'], 'wcsl_edit_task_' . $task_id ) ) {
        wp_die( 'Security check failed.' );
    }

    $current_user = wp_get_current_user();
    if ( ! is_user_logged_in() || ! in_array('wcsl_employee', (array) $current_user->roles) ) {
        wp_die( 'You do not have permission to perform this action.' );
    }
    
    $employee_cpt_id = wcsl_get_employee_id_for_user( $current_user->ID );
    $task_assignee_id = (int) get_post_meta( $task_id, '_wcsl_assigned_employee_id', true );
    if ( ! $employee_cpt_id || $employee_cpt_id !== $task_assignee_id ) {
        wp_die( 'Permission denied: You can only edit tasks assigned to you.' );
    }
    
    // 2. Update the Post Title
    $post_data = array(
        'ID'         => $task_id,
        'post_title' => sanitize_text_field( $_POST['wcsl_task_title'] ),
    );
    wp_update_post( $post_data );

    // 3. *** THE CORE FIX: Save All Meta Fields Directly ***
    if ( isset( $_POST['wcsl_task_type'] ) ) { $task_type = sanitize_key( $_POST['wcsl_task_type'] ); if ( in_array( $task_type, array( 'support', 'fixing' ) ) ) { update_post_meta( $task_id, '_wcsl_task_type', $task_type ); } }
    if ( isset( $_POST['wcsl_task_date'] ) ) { update_post_meta( $task_id, '_wcsl_task_date', sanitize_text_field( $_POST['wcsl_task_date'] ) ); }
    if ( isset( $_POST['wcsl_hours_spent_on_task'] ) ) { update_post_meta( $task_id, '_wcsl_hours_spent_on_task', sanitize_text_field( $_POST['wcsl_hours_spent_on_task'] ) ); }
    if ( isset( $_POST['wcsl_task_status'] ) ) { update_post_meta( $task_id, '_wcsl_task_status', sanitize_key( $_POST['wcsl_task_status'] ) ); }
    if ( isset( $_POST['wcsl_related_client_id'] ) ) { update_post_meta( $task_id, '_wcsl_related_client_id', intval( $_POST['wcsl_related_client_id'] ) ); }
    if ( isset( $_POST['wcsl_task_link'] ) ) { update_post_meta( $task_id, '_wcsl_task_link', esc_url_raw( $_POST['wcsl_task_link'] ) ); }
    if ( isset( $_POST['wcsl_task_note'] ) ) { update_post_meta( $task_id, '_wcsl_task_note', sanitize_textarea_field( $_POST['wcsl_task_note'] ) ); }
    if ( isset( $_POST['wcsl_task_attachment_url'] ) ) { update_post_meta( $task_id, '_wcsl_task_attachment_url', esc_url_raw( $_POST['wcsl_task_attachment_url'] ) ); }
    
    if ( isset( $_POST['wcsl_assigned_employee_id'] ) ) {
        $employee_id = intval( $_POST['wcsl_assigned_employee_id'] );
        if ( $employee_id > 0 ) {
            update_post_meta( $task_id, '_wcsl_assigned_employee_id', $employee_id );
            $employee_post = get_post( $employee_id );
            if ( $employee_post && $employee_post->post_type === 'employee' ) {
                update_post_meta( $task_id, '_wcsl_employee_name', sanitize_text_field( $employee_post->post_title ) );
                $employee_contact_email = get_post_meta( $employee_id, '_wcsl_employee_contact_email', true );
                if ( is_email( $employee_contact_email ) ) { update_post_meta( $task_id, '_wcsl_employee_email', $employee_contact_email ); } else { delete_post_meta( $task_id, '_wcsl_employee_email' ); }
            }
        }
    }

    if ( isset( $_POST['wcsl_task_type'] ) ) {
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
            wp_set_post_terms( $task_id, $term_id_to_set, 'task_category' );
        }
    }

    // 4. Redirect back to the "My Tasks" page
    $portal_settings = get_option('wcsl_portal_settings');
    $portal_page_id = isset($portal_settings['portal_page_id']) ? (int)$portal_settings['portal_page_id'] : 0;
    $redirect_url = $portal_page_id ? get_permalink($portal_page_id) : home_url();

    $redirect_url = add_query_arg( array(
        'wcsl_view'   => 'my_tasks',
        'task_edited' => 'true'
    ), $redirect_url );
    
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_wcsl_frontend_edit_task', 'wcsl_handle_frontend_edit_task_submission' );


/**
 * ===================================================================
 * NEW: Portal UI Enhancements
 * ===================================================================
 */

/**
 * Hides the WordPress Admin Bar on the frontend for the custom portal roles.
 *
 * @param bool $show Whether to show the admin bar.
 * @return bool
 */
function wcsl_hide_admin_bar_for_portal_roles( $show ) {
    // If we are in the admin area, don't do anything.
    if ( is_admin() ) {
        return $show;
    }

    // Get the current user.
    $user = wp_get_current_user();
    if ( ! $user || ! $user->ID ) {
        return $show;
    }

    // Check if the user has either of our custom portal roles.
    $is_portal_user = in_array( 'wcsl_client', (array) $user->roles ) || in_array( 'wcsl_employee', (array) $user->roles );

    if ( $is_portal_user ) {
        // If they are a portal user, force the admin bar to be hidden.
        return false;
    }

    // For all other users (like Administrators), return the default value.
    return $show;
}
add_filter( 'show_admin_bar', 'wcsl_hide_admin_bar_for_portal_roles', 99 );



/**
 * Determines whether to use black or white text based on the brightness of a background hex color.
 *
 * @param string $hex_color The background color in hex format (e.g., '#3E624D').
 * @return string The contrasting color, either '#000000' (black) or '#FFFFFF' (white).
 */
function wcsl_get_contrasting_text_color( $hex_color ) {
    // 1. Sanitize the hex color and handle 3-digit shorthand
    $hex_color = ltrim( $hex_color, '#' );
    if ( strlen( $hex_color ) == 3 ) {
        $hex_color = $hex_color[0] . $hex_color[0] . $hex_color[1] . $hex_color[1] . $hex_color[2] . $hex_color[2];
    }
    if ( strlen( $hex_color ) != 6 ) {
        return '#000000'; // Default to black for invalid colors
    }

    // 2. Convert hex to RGB values
    $r = hexdec( substr( $hex_color, 0, 2 ) );
    $g = hexdec( substr( $hex_color, 2, 2 ) );
    $b = hexdec( substr( $hex_color, 4, 2 ) );

    // 3. Calculate the perceived brightness (luminance) using a standard formula
    $luminance = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;

    // 4. Compare luminance to a threshold and return black or white
    return ( $luminance > 150 ) ? '#000000' : '#FFFFFF';
}