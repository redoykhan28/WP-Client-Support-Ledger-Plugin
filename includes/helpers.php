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
function wcsl_get_unread_notification_count() {
    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();

    // TODO: If notifications become user-specific, add WHERE (user_id = %d OR user_id = 0) AND is_read = 0
    $sql = "SELECT COUNT(id) FROM {$table_name} WHERE is_read = 0"; 
    
    $count = $wpdb->get_var( $sql );
    return intval( $count );
}

