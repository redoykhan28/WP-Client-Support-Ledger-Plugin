<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trigger on-site and email notification when a new client is published.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated or not.
 */
function wcsl_trigger_new_client_notification( $post_id, $post, $update ) {
    // Only act on our 'client' CPT and when the post is being published
    if ( 'client' !== $post->post_type || 'publish' !== $post->post_status || !function_exists('wcsl_add_notification') ) {
        return;
    }

    $is_newly_published_for_notification = false;
    // If $update is false, it's a new post insertion.
    // If $update is true, it's an update. We only care if it's transitioning to 'publish' for the first time.
    if ( !$update ) { 
        $is_newly_published_for_notification = true;
    } else {
        // This is an update. Only trigger if we haven't notified about its first publish yet.
        $already_notified = get_post_meta( $post_id, '_wcsl_new_client_notified_published', true );
        if ( empty( $already_notified ) ) { 
            $is_newly_published_for_notification = true;
        }
    }

    if ( $is_newly_published_for_notification ) {
        $client_name = $post->post_title;
        if (empty($client_name)) $client_name = __('Unnamed Client (ID: ', 'wp-client-support-ledger') . $post_id . ')';
        
        $client_link = get_edit_post_link( $post_id );
        
        // On-site notification message
        $on_site_message = sprintf( 
            esc_html__( 'New client published: %s', 'wp-client-support-ledger' ), 
            $client_link ? ('<a href="' . esc_url( $client_link ) . '">' . esc_html( $client_name ) . '</a>') : esc_html( $client_name )
        );

        // Add on-site notification and set flag
        if ( wcsl_add_notification( 'new_client_published', $on_site_message, $post_id, 'client' ) ) {
            update_post_meta( $post_id, '_wcsl_new_client_notified_published', 'yes' );
        }

        // --- Send Email to Admin if setting is enabled ---
        $email_settings = get_option( 'wcsl_email_notification_settings', array() );
        if ( ! empty( $email_settings['enable_email_new_client_admin'] ) ) {
            $admin_recipients_str = !empty( $email_settings['admin_recipients'] ) ? $email_settings['admin_recipients'] : get_option('admin_email');
            
            if( !empty($admin_recipients_str) ){
                $admin_recipients = array_map( 'trim', explode( "\n", $admin_recipients_str ) );
                $admin_recipients = array_filter( $admin_recipients, 'is_email' );

                if ( ! empty( $admin_recipients ) ) {
                    $subject = sprintf( __( '[%s] New Client Published: %s', 'wp-client-support-ledger' ), get_bloginfo('name'), $client_name );
                    $body    = "<p>" . sprintf( __( 'A new client, "%s", has been published on %s.', 'wp-client-support-ledger' ), esc_html( $client_name ), get_bloginfo('name') ) . "</p>";
                    if ($client_link) {
                        $body   .= "<p>" . sprintf( __( 'You can view the client details here: %s', 'wp-client-support-ledger' ), '<a href="' . esc_url( $client_link ) . '">' . esc_url( $client_link ) . '</a>' ) . "</p>";
                    }
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail( $admin_recipients, $subject, $body, $headers );
                }
            }
        }
    }
}
add_action( 'save_post_client', 'wcsl_trigger_new_client_notification', 99, 3 );


/**
 * Trigger on-site and email notification when a new task's client relation is first set and task is published.
 */
function wcsl_trigger_new_task_on_client_relation_added( $meta_id, $object_id, $meta_key, $_meta_value ) {
    if ( '_wcsl_related_client_id' !== $meta_key || 'client_task' !== get_post_type( $object_id ) || !function_exists('wcsl_add_notification') ) {
        return;
    }
    if ( get_post_meta( $object_id, '_wcsl_new_task_notified_published', true ) === 'yes' ) {
        return;
    }
    if ('publish' !== get_post_status($object_id)) {
        return;
    }

    $post_id = $object_id; // Task ID
    $related_client_id_for_new_task = intval( $_meta_value );

    if ( $related_client_id_for_new_task > 0 ) {
        $task_post = get_post($post_id);
        if (!$task_post) return;
        $task_title = $task_post->post_title;
        if (empty($task_title)) $task_title = __('Untitled Task', 'wp-client-support-ledger');

        $client_name_for_new_task = get_the_title( $related_client_id_for_new_task );
        if (empty($client_name_for_new_task)) {
            $client_name_for_new_task = __( 'Client (ID: ', 'wp-client-support-ledger') . $related_client_id_for_new_task . __( ') Name Not Found', 'wp-client-support-ledger');
        }

        $task_link = get_edit_post_link( $post_id );
        $client_link_for_task = get_edit_post_link( $related_client_id_for_new_task );

        $on_site_message = sprintf(
            esc_html__( 'New task published: %1$s for client %2$s.', 'wp-client-support-ledger' ),
            $task_link ? ('<a href="' . esc_url( $task_link ) . '">' . esc_html( $task_title ) . '</a>') : esc_html( $task_title ),
            ($related_client_id_for_new_task > 0 && $client_link_for_task) ? ('<a href="' . esc_url( $client_link_for_task ) . '">' . esc_html( $client_name_for_new_task ) . '</a>') : esc_html( $client_name_for_new_task )
        );
        
        if ( wcsl_add_notification( 'new_task_published', $on_site_message, $post_id, 'client_task' ) ) {
            update_post_meta($post_id, '_wcsl_new_task_notified_published', 'yes');
        }

        // --- Send Email to Employee ---
        $email_settings = get_option( 'wcsl_email_notification_settings', array() );
        if ( ! empty( $email_settings['enable_email_new_task_employee'] ) ) {
            $employee_email = get_post_meta( $post_id, '_wcsl_employee_email', true );
            if ( is_email( $employee_email ) ) {
                $subject = sprintf( __( '[%s] New Task: %s for %s', 'wp-client-support-ledger' ), get_bloginfo('name'), $task_title, $client_name_for_new_task );
                $body    = "<p>" . sprintf( __( 'A new task "%1$s" has been created/assigned for client "%2$s".', 'wp-client-support-ledger' ), esc_html( $task_title ), esc_html( $client_name_for_new_task ) ) . "</p>";
                $task_date_email = get_post_meta( $post_id, '_wcsl_task_date', true );
                if ($task_date_email) {
                     $body .= "<p>" . sprintf( __( 'Task Date: %s', 'wp-client-support-ledger' ), esc_html( date_i18n(get_option('date_format'), strtotime($task_date_email)) ) ) . "</p>";
                }
                if ($task_link) {
                    $body .= "<p>" . sprintf( __( 'View Task: %s', 'wp-client-support-ledger' ), '<a href="' . esc_url( $task_link ) . '">' . esc_url( $task_link ) . '</a>' ) . "</p>";
                }
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail( $employee_email, $subject, $body, $headers );
            }
        }
    }
}
add_action( 'added_post_meta', 'wcsl_trigger_new_task_on_client_relation_added', 10, 4 );
// If a task is created as a draft and then published, or client is assigned later,
// 'save_post_client_task' might be needed too for the "New Task Published" email.
// However, for now, 'added_post_meta' for _wcsl_related_client_id covers the main creation scenario.


/**
 * Trigger on-site and email notifications for hours exceeded when a task is saved/updated.
 */
function wcsl_trigger_hours_exceeded_notification( $post_id, $post, $update ) {
    if ( 'client_task' !== $post->post_type || 'publish' !== $post->post_status ) {
        return;
    }
    if ( !function_exists('wcsl_add_notification') || !function_exists('wcsl_parse_time_string_to_minutes') || !function_exists('wcsl_format_minutes_to_time_string') ) {
        return;
    }
        
    $related_client_id = get_post_meta( $post_id, '_wcsl_related_client_id', true );
    $related_client_id = intval( $related_client_id );

    if ( ! $related_client_id || $related_client_id <= 0 ) {
        return; 
    }

    $task_date_str = get_post_meta( $post_id, '_wcsl_task_date', true );
    if ( ! $task_date_str || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $task_date_str) ) {
        $task_date_str = $post->post_date;
    }
    $task_timestamp = strtotime( $task_date_str );
    if ( !$task_timestamp ) { return; }

    $current_task_month = date( 'n', $task_timestamp );
    $current_task_year  = date( 'Y', $task_timestamp );

    $contracted_hours_str = get_post_meta( $related_client_id, '_wcsl_contracted_support_hours', true );
    $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );

    if ( $contracted_minutes <= 0 ) { return; }

    $first_day_of_month = date( 'Y-m-d', mktime( 0, 0, 0, $current_task_month, 1, $current_task_year ) );
    $last_day_of_month  = date( 'Y-m-d', mktime( 0, 0, 0, $current_task_month + 1, 0, $current_task_year ) );

    $client_tasks_args = array(
        'post_type'      => 'client_task',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array( 'relation' => 'AND',
            array( 'key' => '_wcsl_related_client_id', 'value' => $related_client_id, 'compare' => '=' ),
            array( 'key' => '_wcsl_task_date', 'value' => array( $first_day_of_month, $last_day_of_month ), 'compare' => 'BETWEEN', 'type' => 'DATE')
        )
    );
    $client_tasks_query = new WP_Query( $client_tasks_args );
    $total_minutes_spent_this_month = 0;
    if ( $client_tasks_query->have_posts() ) {
        while ( $client_tasks_query->have_posts() ) : $client_tasks_query->the_post();
            $hours_spent_str_loop = get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true );
            $total_minutes_spent_this_month += wcsl_parse_time_string_to_minutes( $hours_spent_str_loop );
        endwhile;
    }
    wp_reset_postdata();

    if ( $total_minutes_spent_this_month > $contracted_minutes ) {
        $client_post_obj = get_post($related_client_id);
        if(!$client_post_obj) return;
        $client_name_exceeded = $client_post_obj->post_title;
        if(empty($client_name_exceeded)) $client_name_exceeded = __('Client ID: ', 'wp-client-support-ledger') . $related_client_id;
        
        $month_name_for_notification = date_i18n( 'F', $task_timestamp );
        $exceeded_by_minutes = $total_minutes_spent_this_month - $contracted_minutes;
        $exceeded_by_str = wcsl_format_minutes_to_time_string($exceeded_by_minutes);
        $total_spent_str = wcsl_format_minutes_to_time_string($total_minutes_spent_this_month);
        $contracted_hours_display = !empty($contracted_hours_str) ? $contracted_hours_str : ($contracted_minutes . 'm');

        // On-site notification (only once per month to avoid clutter)
        $on_site_notif_key = '_wcsl_hours_exceeded_onsite_notified_' . $current_task_year . '_' . $current_task_month;
        if ( get_post_meta( $related_client_id, $on_site_notif_key, true ) !== 'yes' ) {
            $on_site_message = sprintf(
                esc_html__( 'Support hours exceeded for client %1$s in %2$s. Total spent: %3$s (Contracted: %4$s, Exceeded by: %5$s).', 'wp-client-support-ledger' ),
                '<a href="' . esc_url( get_edit_post_link( $related_client_id ) ) . '">' . esc_html( $client_name_exceeded ) . '</a>',
                esc_html( $month_name_for_notification . ' ' . $current_task_year ),
                esc_html( $total_spent_str ),
                esc_html( $contracted_hours_display ),
                esc_html( $exceeded_by_str )
            );
            if (wcsl_add_notification( 'hours_exceeded_admin', $on_site_message, $related_client_id, 'client' )) {
                 update_post_meta( $related_client_id, $on_site_notif_key, 'yes' );
            }
        }

        $email_settings = get_option( 'wcsl_email_notification_settings', array() );

        // --- Send Email to Admin ---
        if ( ! empty( $email_settings['enable_email_hours_exceeded_admin'] ) ) {
            $admin_recipients_str = !empty( $email_settings['admin_recipients'] ) ? $email_settings['admin_recipients'] : get_option('admin_email');
            if( !empty($admin_recipients_str) ){
                $admin_recipients = array_filter(array_map( 'trim', explode( "\n", $admin_recipients_str ) ), 'is_email');
                if ( ! empty( $admin_recipients ) ) {
                    $subject_admin = sprintf( __( '[%1$s] Alert: Support Hours Exceeded for %2$s (%3$s)', 'wp-client-support-ledger' ), get_bloginfo('name'), $client_name_exceeded, $month_name_for_notification . ' ' . $current_task_year );
                    $body_admin    = "<p>" . sprintf( __( 'Client %1$s has exceeded their contracted support hours for %2$s.', 'wp-client-support-ledger' ), esc_html($client_name_exceeded), esc_html($month_name_for_notification . ' ' . $current_task_year) ) . "</p>";
                    $body_admin   .= "<p>" . sprintf( __( 'Contracted: %1$s | Spent: %2$s | Exceeded by: %3$s', 'wp-client-support-ledger' ), esc_html($contracted_hours_display), esc_html($total_spent_str), esc_html($exceeded_by_str) ) . "</p>";
                    if ($related_client_id > 0) {
                        $body_admin   .= "<p>" . sprintf( __( 'View client: %s', 'wp-client-support-ledger' ), '<a href="' . esc_url( get_edit_post_link( $related_client_id ) ) . '">' . esc_url( get_edit_post_link( $related_client_id ) ) . '</a>' ) . "</p>";
                    }
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail( $admin_recipients, $subject_admin, $body_admin, $headers );
                }
            }
        }

        // --- Send Email to Client ---
        if ( ! empty( $email_settings['enable_email_hours_exceeded_client'] ) ) {
            $client_contact_email = get_post_meta( $related_client_id, '_wcsl_client_contact_email', true );
            if ( is_email( $client_contact_email ) ) {
                $client_email_sent_meta_key = '_wcsl_client_hours_exceeded_emailed_' . $current_task_year . '_' . $current_task_month;
                if ( get_post_meta( $related_client_id, $client_email_sent_meta_key, true ) !== 'yes' ) {
                    $subject_client = sprintf( __( '[%s] Important Update on Your Support Hours for %s', 'wp-client-support-ledger' ), get_bloginfo('name'), $month_name_for_notification . ' ' . $current_task_year );
                    $body_client    = "<p>" . sprintf( __( 'Dear %s,', 'wp-client-support-ledger' ), esc_html($client_name_exceeded) ) . "</p>";
                    $body_client   .= "<p>" . sprintf( __( 'This is an update regarding your support hours for %s. You have currently utilized %s of your contracted %s.', 'wp-client-support-ledger' ), esc_html($month_name_for_notification . ' ' . $current_task_year), esc_html($total_spent_str), esc_html($contracted_hours_display) ) . "</p>";
                    $body_client   .= "<p>" . __( 'If this exceeds your allowance, additional hours may be billable. Please contact us if you have any questions or wish to discuss your support plan.', 'wp-client-support-ledger' ) . "</p>";
                    $body_client   .= "<p>" . __( 'Thank you,', 'wp-client-support-ledger' ) . "<br>" . get_bloginfo('name') . "</p>";
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    if ( wp_mail( $client_contact_email, $subject_client, $body_client, $headers ) ) {
                        update_post_meta( $related_client_id, $client_email_sent_meta_key, 'yes');
                    }
                }
            }
        }
    }
}
add_action( 'save_post_client_task', 'wcsl_trigger_hours_exceeded_notification', 99, 3 );


/**
 * Trigger notification when a client is deleted.
 */
function wcsl_trigger_deleted_client_notification( $post_id ) {
    if ( get_post_type( $post_id ) == 'client' && function_exists('wcsl_add_notification') ) {
        $client_name = get_the_title( $post_id );
        if ( empty($client_name) ) {
            $client_name = __( 'Unknown Client (ID: ', 'wp-client-support-ledger') . $post_id . ')';
        }
        $message = sprintf( esc_html__( 'Client deleted: %s', 'wp-client-support-ledger' ), esc_html( $client_name ) );
        wcsl_add_notification( 'client_deleted', $message, $post_id, 'client_deleted_ref' );
    }
}
add_action( 'before_delete_post', 'wcsl_trigger_deleted_client_notification', 10, 1 );

