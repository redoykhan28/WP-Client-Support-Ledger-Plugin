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
        $total_minutes_spent_for_client_in_period = 0;

        if ( $tasks_query->have_posts() ) {
            while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
                $hours_spent_str = get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true );
                $total_minutes_spent_for_client_in_period += wcsl_parse_time_string_to_minutes( $hours_spent_str );
            endwhile;
        }
        wp_reset_postdata();

        if ( $contracted_minutes_monthly > 0 && $total_minutes_spent_for_client_in_period > $contracted_minutes_monthly ) {
             $total_billable_minutes_for_period += ( $total_minutes_spent_for_client_in_period - $contracted_minutes_monthly );
        } elseif ( $contracted_minutes_monthly <= 0 && $total_minutes_spent_for_client_in_period > 0) {
            $total_billable_minutes_for_period += $total_minutes_spent_for_client_in_period;
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
        $total_minutes_spent_for_client_in_period = 0;

        if ( $tasks_query->have_posts() ) {
            while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
                $hours_spent_str = get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true );
                $total_minutes_spent_for_client_in_period += wcsl_parse_time_string_to_minutes( $hours_spent_str );
            endwhile;
        }
        wp_reset_postdata();

        $client_billable_minutes_in_period = 0;
        // Simplified billable calculation for the period:
        // If period is longer than a month, this assumes the contracted amount applies over the whole period.
        // A more complex pro-rata calculation would be needed for true monthly accuracy over custom ranges.
        if ( $contracted_minutes_monthly >= 0 && $total_minutes_spent_for_client_in_period > $contracted_minutes_monthly ) {
             $client_billable_minutes_in_period = ( $total_minutes_spent_for_client_in_period - $contracted_minutes_monthly );
        } elseif ( $contracted_minutes_monthly < 0 && $total_minutes_spent_for_client_in_period > 0) { // No contract or invalid
            $client_billable_minutes_in_period = $total_minutes_spent_for_client_in_period;
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



