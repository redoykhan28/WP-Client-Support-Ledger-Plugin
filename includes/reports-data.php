<?php

/**
 * Gets hours spent per client for a given date range (defaults to last 30 days).
 *
 * @param string $start_date YYYY-MM-DD format.
 * @param string $end_date   YYYY-MM-DD format.
 * @return array An array suitable for Chart.js: ['labels' => [], 'data' => []]
 */
function wcsl_get_hours_per_client_for_period( $start_date = null, $end_date = null ) {
    global $wpdb;

    if ( is_null( $end_date ) ) {
        $end_date = current_time( 'Y-m-d' );
    }
    if ( is_null( $start_date ) ) {
        $start_date = date( 'Y-m-d', strtotime( '-29 days', strtotime( $end_date ) ) ); // Last 30 days inclusive
    }

    $report_data = array(
        'labels' => array(), // Client names
        'data'   => array(), // Total minutes spent
    );

    // Get all clients first
    $clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => -1,
        'fields'         => 'ids', // Only need IDs
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish'
    );
    $client_ids = get_posts( $clients_query_args );

    if ( empty( $client_ids ) ) {
        return $report_data; // No clients, no data
    }

    foreach ( $client_ids as $client_id ) {
        $client_name = get_the_title( $client_id );
        $total_minutes_for_client = 0;

        $tasks_args = array(
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
                    'value'   => array( $start_date, $end_date ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
            ),
        );
        $tasks_query = new WP_Query( $tasks_args );

        if ( $tasks_query->have_posts() ) {
            while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
                $hours_spent_str = get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true );
                $total_minutes_for_client += wcsl_parse_time_string_to_minutes( $hours_spent_str );
            endwhile;
        }
        wp_reset_postdata();

        if ( $total_minutes_for_client > 0 ) { // Only include clients with logged hours in the period
            $report_data['labels'][] = $client_name;
            // Chart.js usually expects numbers for data. Convert minutes to hours for readability.
            $report_data['data'][]   = round( $total_minutes_for_client / 60, 2 ); // Hours, rounded to 2 decimal places
        }
    }
    return $report_data;
}

function wcsl_get_total_billable_minutes_for_period( $start_date, $end_date ) {
    global $wpdb;
    $total_billable_minutes_for_period = 0;

    if ( !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) ) {
        return 0; 
    }
    if (strtotime($end_date) < strtotime($start_date)) {
        return 0; 
    }

    $clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post_status'    => 'publish'
    );
    $client_ids = get_posts( $clients_query_args );

    if ( empty( $client_ids ) ) {
        return 0;
    }

    foreach ( $client_ids as $client_id ) {
        $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
        $contracted_minutes_monthly = wcsl_parse_time_string_to_minutes( $contracted_hours_str );

        $tasks_args = array(
            'post_type'      => 'client_task',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                array( 'key' => '_wcsl_related_client_id', 'value' => $client_id, 'compare' => '=' ),
                array( 'key' => '_wcsl_task_date', 'value' => array( $start_date, $end_date ), 'compare' => 'BETWEEN', 'type' => 'DATE' ),
            ),
        );
        $tasks_query = new WP_Query( $tasks_args );
        
        // <<< CHANGE: We need two counters here as well
        $total_minutes_spent_for_client_in_period = 0; // This will be total time
        $total_support_minutes_in_period = 0;          // This is for billable calculation

        if ( $tasks_query->have_posts() ) {
            while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
                $task_id_inner = get_the_ID();
                $hours_spent_str = get_post_meta( $task_id_inner, '_wcsl_hours_spent_on_task', true );
                $minutes_for_task = wcsl_parse_time_string_to_minutes( $hours_spent_str );

                // <<< CHANGE: Get the task type
                $task_type = get_post_meta( $task_id_inner, '_wcsl_task_type', true );

                $total_minutes_spent_for_client_in_period += $minutes_for_task;

                // <<< CHANGE: Only add to the support counter if it's a support task
                if ( $task_type === 'support' || empty( $task_type ) ) {
                    $total_support_minutes_in_period += $minutes_for_task;
                }
            endwhile;
        }
        wp_reset_postdata();

        // <<< CHANGE: Use the new total_support_minutes_in_period for the calculation
        if ( $contracted_minutes_monthly > 0 && $total_support_minutes_in_period > $contracted_minutes_monthly ) {
             $total_billable_minutes_for_period += ( $total_support_minutes_in_period - $contracted_minutes_monthly );
        } elseif ( $contracted_minutes_monthly <= 0 && $total_support_minutes_in_period > 0) {
            $total_billable_minutes_for_period += $total_support_minutes_in_period;
        }
    }
    return $total_billable_minutes_for_period;
}


/**
 * Gets billable hours per task for a given period.
 *
 * @param string $start_date YYYY-MM-DD format.
 * @param string $end_date   YYYY-MM-DD format.
 * @return array An array suitable for Chart.js: ['labels' => [Task Titles], 'data' => [Billable Minutes]]
 */
function wcsl_get_billable_summary_per_client_for_period( $start_date, $end_date ) {
    $report_data = array(
        'labels' => array(), // Client names
        'data'   => array(), // Billable hours (as decimal)
    );

    $clients_query_args = array(
        'post_type'      => 'client',
        'posts_per_page' => -1,
        'fields'         => 'ids', // We only need IDs
        'post_status'    => 'publish'
    );
    $client_ids = get_posts( $clients_query_args );

    if ( empty( $client_ids ) ) {
        return $report_data;
    }

    foreach ( $client_ids as $client_id ) {
        $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
        $contracted_minutes_monthly = wcsl_parse_time_string_to_minutes( $contracted_hours_str );

        // Fetch all tasks for this client within the specified period
        $tasks_args = array(
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
                    'value'   => array( $start_date, $end_date ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
            ),
        );
        $tasks_query = new WP_Query( $tasks_args );
        
        // <<< CHANGE: Initialize two counters
        $total_minutes_spent_for_client_in_period = 0;
        $total_support_minutes_in_period = 0;

        if ( $tasks_query->have_posts() ) {
            while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
                $task_id_inner = get_the_ID();
                $hours_spent_str = get_post_meta( $task_id_inner, '_wcsl_hours_spent_on_task', true );
                $minutes_for_task = wcsl_parse_time_string_to_minutes( $hours_spent_str );

                // <<< CHANGE: Get the task type
                $task_type = get_post_meta( $task_id_inner, '_wcsl_task_type', true );

                $total_minutes_spent_for_client_in_period += $minutes_for_task;

                // <<< CHANGE: Only add to support counter if it's a support task
                if ( $task_type === 'support' || empty( $task_type ) ) {
                    $total_support_minutes_in_period += $minutes_for_task;
                }
            endwhile;
        }
        wp_reset_postdata();

        $client_billable_minutes_in_period = 0;
        
        // <<< CHANGE: Use the support minutes counter for the calculation
        if ( $contracted_minutes_monthly >= 0 && $total_support_minutes_in_period > $contracted_minutes_monthly ) {
             $client_billable_minutes_in_period = ( $total_support_minutes_in_period - $contracted_minutes_monthly );
        } elseif ( $contracted_minutes_monthly < 0 && $total_support_minutes_in_period > 0) { // No contract or invalid
            $client_billable_minutes_in_period = $total_support_minutes_in_period;
        }


        if ( $client_billable_minutes_in_period > 0 ) {
            $client_name = get_the_title( $client_id );
            if(empty($client_name)) $client_name = __('Client ID: ', 'wp-client-support-ledger') . $client_id;

            $report_data['labels'][] = $client_name;
            $report_data['data'][]   = round( $client_billable_minutes_in_period / 60, 2 ); // Billable hours
        }
    }
    return $report_data;
}



/**
 * Gets total hours logged per employee for a given period.
 *
 * @param string $start_date YYYY-MM-DD format.
 * @param string $end_date   YYYY-MM-DD format.
 * @return array An array suitable for Chart.js: ['labels' => [Employee Names], 'data' => [Total Minutes]]
 */
function wcsl_get_hours_by_employee_for_period( $start_date, $end_date ) {
    // global $wpdb; // Not needed if only using WP_Query and WP functions

    // We'll use an associative array to store original casing for labels,
    // but use lowercase for keys to aggregate correctly.
    $employee_hours_aggregate = array(); // key = lowercase name, value = total_minutes
    $employee_name_display    = array(); // key = lowercase name, value = original cased name for display

    $tasks_args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_wcsl_task_date',
                'value'   => array( $start_date, $end_date ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ),
        ),
    );
    $tasks_query = new WP_Query( $tasks_args );

    if ( $tasks_query->have_posts() ) {
        while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
            $task_id = get_the_ID();
            $current_employee_name = ''; // Store the original cased name
            $employee_name_meta = get_post_meta( $task_id, '_wcsl_employee_name', true );
            
            if ( ! empty( $employee_name_meta ) ) {
                $current_employee_name = $employee_name_meta;
            } else {
                $author_id = get_post_field( 'post_author', $task_id );
                $current_employee_name = get_the_author_meta( 'display_name', $author_id );
            }

            if ( empty( $current_employee_name ) ) {
                $current_employee_name = __( 'Unknown/Unassigned', 'wp-client-support-ledger' );
            }

            // *** NORMALIZE THE CASE FOR AGGREGATION KEY ***
            $employee_key = strtolower( trim( $current_employee_name ) );

            // Store the first encountered casing for display purposes
            if ( !array_key_exists( $employee_key, $employee_name_display ) ) {
                $employee_name_display[$employee_key] = $current_employee_name;
            }
            
            $hours_spent_str = get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true );
            $minutes_spent = wcsl_parse_time_string_to_minutes( $hours_spent_str );

            if ( $minutes_spent > 0 ) {
                if ( ! isset( $employee_hours_aggregate[$employee_key] ) ) {
                    $employee_hours_aggregate[$employee_key] = 0;
                }
                $employee_hours_aggregate[$employee_key] += $minutes_spent;
            }
        endwhile;
    }
    wp_reset_postdata();

    $report_data = array(
        'labels' => array(),
        'data'   => array(),
    );

    if ( ! empty( $employee_hours_aggregate ) ) {
        arsort($employee_hours_aggregate); // Sort by hours (minutes) descending
        
        foreach ( $employee_hours_aggregate as $normalized_name_key => $minutes ) {
            // Use the original casing for the label from our $employee_name_display array
            $report_data['labels'][] = isset($employee_name_display[$normalized_name_key]) ? $employee_name_display[$normalized_name_key] : $normalized_name_key;
            $report_data['data'][]   = round( $minutes / 60, 2 ); // Convert to hours
        }
    }
    return $report_data;
}


/**
 * Gets total billable hours for the last N months for a line chart.
 *
 * @param int $num_months The number of past months to retrieve data for (e.g., 12).
 * @return array An array suitable for Chart.js: ['labels' => [Month-Year], 'data' => [Hours]]
 */
function wcsl_get_billable_hours_for_past_months( $num_months = 12 ) {
    $report_data = array(
        'labels' => array(), // e.g., "Jan 2025", "Feb 2025"
        'data'   => array(), // e.g., [10.5, 15.75, 8.0]
    );

    // Loop backwards from the current month for the last N months.
    for ( $i = 0; $i < $num_months; $i++ ) {
        // Calculate the timestamp for the month we are processing in this loop iteration.
        // strtotime("-{$i} months") handles month and year rollovers correctly.
        $timestamp = strtotime( date( 'Y-m-01' ) . " -{$i} months" );
        
        // Format the label for the chart's X-axis (e.g., "Jul 2025").
        $report_data['labels'][] = date( 'M Y', $timestamp );
        
        // Get the start and end dates for the database query for this specific month.
        $start_date = date( 'Y-m-01', $timestamp );
        $end_date   = date( 'Y-m-t', $timestamp ); // 't' gives the last day of the month.

        // We can reuse our existing helper function to get the billable minutes for this month.
        $total_billable_minutes_for_month = 0;
        if ( function_exists('wcsl_get_total_billable_minutes_for_period') ) {
            $total_billable_minutes_for_month = wcsl_get_total_billable_minutes_for_period( $start_date, $end_date );
        }

        // Convert minutes to decimal hours and add to our data array.
        $report_data['data'][] = round( $total_billable_minutes_for_month / 60, 2 );
    }

    // The loop generates months from newest to oldest, but charts look better oldest to newest.
    // So, we reverse both arrays to get a proper chronological order.
    $report_data['labels'] = array_reverse( $report_data['labels'] );
    $report_data['data']   = array_reverse( $report_data['data'] );

    return $report_data;
}





/**
 * Gets the count of tasks per task category for a given date range and primary type.
 *
 * @param string $primary_type 'support' or 'fixing'.
 * @param string $start_date   YYYY-MM-DD format.
 * @param string $end_date     YYYY-MM-DD format.
 * @return array An array suitable for Chart.js: ['labels' => [Category Names], 'data' => [Task Counts]]
 */
function wcsl_get_task_count_by_category( $primary_type, $start_date, $end_date ) {
    $report_data = array(
        'labels' => array(),
        'data'   => array(),
    );

    // First, get all task categories that match the primary type (e.g., all 'support' categories)
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

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return $report_data; // No categories of this type, so no data.
    }

    // Now, for each category, count the number of tasks within the date range
    foreach ( $terms as $term ) {
        $task_args = array(
            'post_type'      => 'client_task',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids', // We only need to count, so IDs are efficient.
            'tax_query'      => array(
                array(
                    'taxonomy' => 'task_category',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ),
            ),
            'meta_query'     => array(
                array(
                    'key'     => '_wcsl_task_date',
                    'value'   => array( $start_date, $end_date ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
            ),
        );

        $tasks_query = new WP_Query( $task_args );
        
        // We only add the category to the chart if it has one or more tasks
        if ( $tasks_query->found_posts > 0 ) {
            $report_data['labels'][] = $term->name;
            $report_data['data'][]   = $tasks_query->found_posts;
        }
    }

    return $report_data;
}

