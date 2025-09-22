<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ===================================================================
 * Main Client Portal Rendering Function (NEW Jobi Structure)
 * ===================================================================
 */
function wcsl_render_client_portal_main( $user ) {
    $client_id = wcsl_get_client_id_for_user( $user->ID );
    if ( ! $client_id ) {
        echo '<p>' . esc_html__( 'Your user account is not linked to a client profile. Please contact the site administrator.', 'wp-client-support-ledger' ) . '</p>';
        return;
    }

    $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
    $current_view = isset( $_GET['wcsl_view'] ) ? sanitize_key( $_GET['wcsl_view'] ) : 'dashboard';
    $unread_count = 0;
    if (function_exists('wcsl_get_unread_notification_count_for_user')) {
        $unread_count = wcsl_get_unread_notification_count_for_user($user->ID);
    }
    ?>
    <div id="wcsl-portal-app-wrapper" class="wcsl-client-portal">
        
        <div class="wcsl-portal-sidebar">
            <div class="wcsl-sidebar-widget">
                <h4><?php esc_html_e( 'My Account', 'wp-client-support-ledger' ); ?></h4>
               <ul>
                    <li class="<?php echo ($current_view === 'dashboard') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( $portal_page_url ); ?>">
                            <img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/dashboard.png' ); ?>" alt="">
                            <span><?php esc_html_e( 'Dashboard', 'wp-client-support-ledger' ); ?></span>
                        </a>
                    </li>
                    <li class="<?php echo ($current_view === 'my_tasks') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( add_query_arg('wcsl_view', 'my_tasks', $portal_page_url) ); ?>">
                            <img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/task.png' ); ?>" alt="">
                            <span><?php esc_html_e( 'My Tasks', 'wp-client-support-ledger' ); ?></span>
                        </a>
                    </li>
                    <li class="<?php echo ($current_view === 'notifications') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( add_query_arg('wcsl_view', 'notifications', $portal_page_url) ); ?>">
                            <img class="wcsl-menu-icon" src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/bell.png' ); ?>" alt="">
                            <span><?php esc_html_e( 'Notifications', 'wp-client-support-ledger' ); ?></span>
                            <?php if ( $unread_count > 0 ) : ?><span class="wcsl-notification-badge"><?php echo esc_html( $unread_count ); ?></span><?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="wcsl-portal-main" id="wcsl-portal-main-content-wrapper">
            
            <div class="wcsl-main-header">
                <div id="wcsl-dynamic-client-page-title"></div>
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
                if ( 'my_tasks' === $current_view ) {
                    wcsl_render_client_portal_tasks_page( $client_id );
                } elseif ( 'notifications' === $current_view ) {
                    wcsl_render_client_portal_notifications_page( $user->ID );
                } else {
                    wcsl_render_client_portal_dashboard_content( $client_id );
                }
                ?>
            </div>
        </div>
        
    </div> <?php // End #wcsl-portal-app-wrapper ?>
    <?php
}

/**
 * Renders the content for the Client Dashboard view (NEW Jobi Structure)
 */
function wcsl_render_client_portal_dashboard_content( $client_id ) {
    $current_month = date('n');
    $current_year = date('Y');
    
    $contracted_hours_str = get_post_meta( $client_id, '_wcsl_contracted_support_hours', true );
    $contracted_minutes = wcsl_parse_time_string_to_minutes( $contracted_hours_str );

    $first_day = date( 'Y-m-d', mktime( 0, 0, 0, $current_month, 1, $current_year ) );
    $last_day  = date( 'Y-m-d', mktime( 0, 0, 0, $current_month + 1, 0, $current_year ) );

    $tasks_query = new WP_Query( array('post_type' => 'client_task', 'posts_per_page' => -1, 'meta_query' => array('relation' => 'AND', array('key' => '_wcsl_related_client_id', 'value' => $client_id), array('key' => '_wcsl_task_date', 'value' => array( $first_day, $last_day ), 'compare' => 'BETWEEN', 'type' => 'DATE'))) );
    
    $total_support_minutes_spent = 0; 
    $active_tasks = 0;

    if ( $tasks_query->have_posts() ) {
        while ( $tasks_query->have_posts() ) : $tasks_query->the_post();
            if (get_post_meta( get_the_ID(), '_wcsl_task_type', true ) !== 'fixing') {
                $total_support_minutes_spent += wcsl_parse_time_string_to_minutes( get_post_meta( get_the_ID(), '_wcsl_hours_spent_on_task', true ) );
            }
            $status = get_post_meta( get_the_ID(), '_wcsl_task_status', true );
            if ( ! in_array( $status, array( 'completed', 'billed' ) ) ) { $active_tasks++; }
        endwhile;
    }
    wp_reset_postdata();

    $percentage_used = 0;
    if ( $contracted_minutes > 0 ) {
        $percentage_used = round( ( $total_support_minutes_spent / $contracted_minutes ) * 100, 1 );
    }
    ?>
    <script>
        document.getElementById('wcsl-dynamic-client-page-title').innerHTML = '<h2 class="wcsl-page-title"><?php printf( esc_js( __( 'Dashboard for %s', 'wp-client-support-ledger' ) ), esc_js( date_i18n('F Y') ) ); ?></h2>';
    </script>
    
    <div class="wcsl-metric-grid">
        <div class="wcsl-card wcsl-metric-card">
            <div class="metric-content">
                <h4><?php esc_html_e( 'Support Hours Used', 'wp-client-support-ledger' ); ?></h4>
                <p class="metric-value"><?php echo esc_html( wcsl_format_minutes_to_time_string($total_support_minutes_spent) ); ?></p>
            </div>
            <?php // *** NEW: Added Icon *** ?>
            <div class="metric-icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/work-schedule.png' ); ?>" alt="">
            </div>
        </div>
        <div class="wcsl-card wcsl-metric-card">
            <div class="metric-content">
                <h4><?php esc_html_e( 'Contracted Hours', 'wp-client-support-ledger' ); ?></h4>
                <p class="metric-value"><?php echo esc_html( $contracted_hours_str ?: 'N/A' ); ?></p>
            </div>
            <?php // *** NEW: Added Icon *** ?>
            <div class="metric-icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/working-time.png' ); ?>" alt="">
            </div>
        </div>
        <div class="wcsl-card wcsl-metric-card">
            <div class="metric-content">
                <h4><?php esc_html_e( 'Active Tasks', 'wp-client-support-ledger' ); ?></h4>
                <p class="metric-value"><?php echo esc_html( $active_tasks ); ?></p>
            </div>
            <?php // *** NEW: Added Icon *** ?>
            <div class="metric-icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/to-do-list.png' ); ?>" alt="">
            </div>
        </div>
    </div>

    <?php if ( $contracted_minutes > 0 ): ?>
    <div class="wcsl-card">
        <div class="wcsl-card-header">
            <h3 class="wcsl-card-title"><?php esc_html_e( 'Monthly Usage', 'wp-client-support-ledger' ); ?></h3>
        </div>
        <table class="wcsl-dashboard-watchlist-table">
            <tbody>
                <tr>
                    <td>
                        <div class="wcsl-client-info"><?php echo esc_html( get_the_title($client_id) ); ?></div>
                        <div class="wcsl-client-usage"><?php echo wcsl_format_minutes_to_time_string($total_support_minutes_spent) . ' / ' . wcsl_format_minutes_to_time_string($contracted_minutes); ?></div>
                        <div class="wcsl-progress-bar-container">
                            <div class="wcsl-progress-bar <?php echo $percentage_used >= 100 ? 'danger' : ''; ?>" style="width: <?php echo min(100, $percentage_used); ?>%;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="wcsl-percentage-text"><?php echo esc_html($percentage_used); ?>%</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php
}

/**
 * Renders the content for the "My Tasks" page in the Client Portal (NEW Jobi Structure)
 */
function wcsl_render_client_portal_tasks_page( $client_id ) {
    $paged = isset( $_GET['tasks_page'] ) ? max( 1, intval( $_GET['tasks_page'] ) ) : 1;
    $tasks_per_page = 15;

    $args = array( 'post_type' => 'client_task', 'posts_per_page' => $tasks_per_page, 'paged' => $paged, 'meta_key' => '_wcsl_task_date', 'orderby' => 'meta_value', 'order' => 'DESC', 'meta_query' => array( array( 'key' => '_wcsl_related_client_id', 'value' => $client_id, ), ), );
    $tasks_query = new WP_Query( $args );
    ?>
    <script>
        document.getElementById('wcsl-dynamic-client-page-title').innerHTML = '<h2 class="wcsl-page-title"><?php esc_html_e( 'My Tasks', 'wp-client-support-ledger' ); ?></h2>';
    </script>
    
    <div class="wcsl-data-table-wrapper">
        <div class="wcsl-data-table-header">
            <h3 class="wcsl-data-table-title"><?php esc_html_e( 'All Logged Tasks', 'wp-client-support-ledger' ); ?></h3>
            <div class="wcsl-data-table-controls">
            <?php
            if ( $tasks_query->have_posts() ) {
                $nonce_action = 'wcsl_print_client_tasks_action_' . $client_id;
                $print_nonce = wp_create_nonce( $nonce_action );
                $print_url = add_query_arg( array( 'action' => 'wcsl_print_client_tasks', 'client_id' => $client_id, '_wpnonce' => $print_nonce, 'nonce_action' => $nonce_action, ), admin_url( 'admin-post.php' ) );
                echo '<a href="' . esc_url( $print_url ) . '" class="wcsl-portal-button" target="_blank">' . esc_html__( 'Print / Save as PDF', 'wp-client-support-ledger' ) . '</a>';
            }
            ?>
            </div>
        </div>

        <?php if ( $tasks_query->have_posts() ) : ?>
            <table class="wcsl-portal-table">
                <thead><tr><th><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th><th><?php esc_html_e( 'Task', 'wp-client-support-ledger' ); ?></th><th><?php esc_html_e( 'Task Type', 'wp-client-support-ledger' ); ?></th><th><?php esc_html_e( 'Hours Spent', 'wp-client-support-ledger' ); ?></th><th><?php esc_html_e( 'Status', 'wp-client-support-ledger' ); ?></th></tr></thead>
                <tbody>
                    <?php while ( $tasks_query->have_posts() ) : $tasks_query->the_post(); ?>
                        <?php
                            $task_id = get_the_ID();
                            $task_status = get_post_meta( $task_id, '_wcsl_task_status', true );
                            $task_date = get_post_meta( $task_id, '_wcsl_task_date', true );
                            $hours_spent = get_post_meta( $task_id, '_wcsl_hours_spent_on_task', true );
                            
                            // *** FIX: Get the primary "Task Type" (support or fixing) from post meta ***
                            $primary_task_type = get_post_meta( $task_id, '_wcsl_task_type', true );
                        ?>
                        <tr class="<?php echo $primary_task_type === 'fixing' ? 'type-fixing' : ''; ?>">
                            <td><?php echo esc_html( date_i18n( get_option('date_format'), strtotime($task_date) ) ); ?></td>
                            <td><strong><?php the_title(); ?></strong></td>
                            <td><?php echo esc_html( ucfirst( $primary_task_type ) ); ?></td>
                            <td><?php echo esc_html( $hours_spent ?: '0m' ); ?></td>
                            <td><?php if ( function_exists('wcsl_display_status_badge') ) { wcsl_display_status_badge( $task_status ); } ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php
            $total_pages = $tasks_query->max_num_pages;
            if ( $total_pages > 1 ) {
                echo '<div class="wcsl-data-table-footer">';
                $portal_page_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
                $pagination_base = add_query_arg( 'tasks_page', '%#%', add_query_arg('wcsl_view', 'my_tasks', $portal_page_url) );
                echo '<div class="wcsl-pagination">';
                echo paginate_links( array( 'base' => $pagination_base, 'format' => '', 'current' => $paged, 'total' => $total_pages, 'prev_text' => __( '« Previous', 'wp-client-support-ledger' ), 'next_text' => __( 'Next »', 'wp-client-support-ledger' ) ) );
                echo '</div></div>';
            }
            ?>
        <?php else : ?>
            <p class="wcsl-panel-notice"><?php esc_html_e( 'No tasks have been logged for your account yet.', 'wp-client-support-ledger' ); ?></p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
    <?php
}


/**
 * Renders the content for the "Notifications" page in the Client Portal (NEW Jobi Structure)
 */
function wcsl_render_client_portal_notifications_page( $user_id ) {
    global $wpdb;
    $table_name = wcsl_get_notifications_table_name();
    
    $items_per_page = 20;
    $paged = isset( $_GET['notif_page'] ) ? max( 1, intval( $_GET['notif_page'] ) ) : 1;
    $offset = ( $paged - 1 ) * $items_per_page;

    $total_items = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name} WHERE user_id = %d", $user_id ) );
    $notifications = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", $user_id, $items_per_page, $offset ) );
    ?>
    <script>
        document.getElementById('wcsl-dynamic-client-page-title').innerHTML = '<h2 class="wcsl-page-title"><?php esc_html_e( 'My Notifications', 'wp-client-support-ledger' ); ?></h2>';
    </script>

    <div class="wcsl-data-table-wrapper">
        <div class="wcsl-data-table-header">
            <h3 class="wcsl-data-table-title"><?php esc_html_e( 'All Notifications', 'wp-client-support-ledger' ); ?></h3>
        </div>
        <?php if ( ! empty( $notifications ) ) : ?>
            <table class="wcsl-portal-table" id="wcsl-notifications-table">
                <thead><tr><th class="notification-message"><?php esc_html_e( 'Notification', 'wp-client-support-ledger' ); ?></th><th><?php esc_html_e( 'Date', 'wp-client-support-ledger' ); ?></th><th><?php esc_html_e( 'Actions', 'wp-client-support-ledger' ); ?></th></tr></thead>
                <tbody>
                    <?php foreach ( $notifications as $notification ) :
                        $base_url = get_permalink( get_option('wcsl_portal_settings')['portal_page_id'] );
                        $base_url = add_query_arg('wcsl_view', 'notifications', $base_url);
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
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil( $total_items / $items_per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="wcsl-data-table-footer">';
                $pagination_base = add_query_arg( 'notif_page', '%#%', $base_url );
                echo '<div class="wcsl-pagination">';
                echo paginate_links( array( 'base' => $pagination_base, 'format' => '', 'current' => $paged, 'total' => $total_pages, 'prev_text' => __( '« Previous', 'wp-client-support-ledger' ), 'next_text' => __( 'Next »', 'wp-client-support-ledger' ) ) );
                echo '</div></div>';
            }
            ?>
        <?php else : ?>
            <p class="wcsl-panel-notice"><?php esc_html_e( 'You have no notifications.', 'wp-client-support-ledger' ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}