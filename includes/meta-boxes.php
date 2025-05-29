<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * --------------------------------------------------------------------------
 * CLIENT CPT META BOXES
 * --------------------------------------------------------------------------
 */

/**
 * Register meta box(es) for the 'client' CPT.
 */
function wcsl_client_add_meta_boxes() {
    add_meta_box(
        'wcsl_client_details_meta_box',                 // Unique ID for the meta box
        __( 'Client Details', 'wp-client-support-ledger' ), // Box title
        'wcsl_client_details_meta_box_html',            // Callback function to display HTML
        'client',                                       // Post type to display on
        'normal',                                       // Context (normal, side, advanced)
        'high'                                          // Priority (high, core, default, low)
    );
}
add_action( 'add_meta_boxes_client', 'wcsl_client_add_meta_boxes' ); // Note the _client suffix for specific CPT

/**
 * HTML for the 'Client Details' meta box.
 *
 * @param WP_Post $post The current post object.
 */
function wcsl_client_details_meta_box_html( $post ) {
    // Use a nonce for verification
    wp_nonce_field( 'wcsl_client_details_save', 'wcsl_client_details_nonce' );

    // Get existing value if set
    $contracted_hours = get_post_meta( $post->ID, '_wcsl_contracted_support_hours', true );
    ?>
    <p>
        <label for="wcsl_contracted_support_hours">
            <?php esc_html_e( 'Monthly Contracted Support Hours (e.g., "2h", "3h 30m"):', 'wp-client-support-ledger' ); ?>
        </label>
        <br />
        <input type="text"
               id="wcsl_contracted_support_hours"
               name="wcsl_contracted_support_hours"
               value="<?php echo esc_attr( $contracted_hours ); ?>"
               class="regular-text" />
        <span class="description">
            <?php esc_html_e( 'Enter the standard monthly support hours for this client.', 'wp-client-support-ledger' ); ?>
        </span>
    </p>

    <p>
    <label for="wcsl_client_contact_email">
        <?php esc_html_e( 'Client Email Address:', 'wp-client-support-ledger' ); ?>
    </label>
    <br />
    <input type="email"
           id="wcsl_client_contact_email"
           name="wcsl_client_contact_email"
           value="<?php echo esc_attr( get_post_meta( $post->ID, '_wcsl_client_contact_email', true ) ); ?>"
           class="regular-text" />
    <span class="description">
        <?php esc_html_e( 'Primary contact email of client.', 'wp-client-support-ledger' ); ?>
    </span>
    </p>

    <?php
}

/**
 * Save meta box data for the 'client' CPT.
 *
 * @param int $post_id The ID of the post being saved.
 */
function wcsl_client_save_meta_box_data( $post_id ) {
    // Check if our nonce is set.
    if ( ! isset( $_POST['wcsl_client_details_nonce'] ) ) {
        return;
    }
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['wcsl_client_details_nonce'], 'wcsl_client_details_save' ) ) {
        return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'client' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // Sanitize user input and update the meta field.
    if ( isset( $_POST['wcsl_contracted_support_hours'] ) ) {
        $contracted_hours_str = sanitize_text_field( $_POST['wcsl_contracted_support_hours'] );
        // We will parse this string into minutes later when we need it for calculations.
        // For now, we save the string as entered by the user.
        update_post_meta( $post_id, '_wcsl_contracted_support_hours', $contracted_hours_str );
    }

    if ( isset( $_POST['wcsl_client_contact_email'] ) ) {
    update_post_meta( $post_id, '_wcsl_client_contact_email', sanitize_email( $_POST['wcsl_client_contact_email'] ) );
    }
}
add_action( 'save_post_client', 'wcsl_client_save_meta_box_data' ); // Note the _client suffix





/**
 * --------------------------------------------------------------------------
 * CLIENT TASK CPT META BOXES
 * --------------------------------------------------------------------------
 */

/**
 * Register meta box(es) for the 'client_task' CPT.
 */
function wcsl_client_task_add_meta_boxes() {
    add_meta_box(
        'wcsl_client_task_details_meta_box',
        __( 'Task Details', 'wp-client-support-ledger' ),
        'wcsl_client_task_details_meta_box_html',
        'client_task',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_client_task', 'wcsl_client_task_add_meta_boxes' );

/**
 * HTML for the 'Task Details' meta box.
 *
 * @param WP_Post $post The current post object.
 */
function wcsl_client_task_details_meta_box_html( $post ) {
    wp_nonce_field( 'wcsl_client_task_details_save', 'wcsl_client_task_details_nonce' );

    // Get existing values
    $task_date = get_post_meta( $post->ID, '_wcsl_task_date', true );
    if ( empty( $task_date ) ) { // Default to today if not set
        $task_date = date( 'Y-m-d' );
    }
    $hours_spent = get_post_meta( $post->ID, '_wcsl_hours_spent_on_task', true );
    $task_status = get_post_meta( $post->ID, '_wcsl_task_status', true );
    $related_client_id = get_post_meta( $post->ID, '_wcsl_related_client_id', true );

    // For "Task Status" dropdown
    $statuses = array(
        'pending'    => __( 'Pending', 'wp-client-support-ledger' ),
        'in-progress'=> __( 'In Progress', 'wp-client-support-ledger' ),
        'in-review'  => __( 'In Review', 'wp-client-support-ledger' ),
        'completed'  => __( 'Completed', 'wp-client-support-ledger' ),
        'billed'     => __( 'Billed', 'wp-client-support-ledger' ),
    );

    // For "Related Client" dropdown - Fetch all 'client' CPTs
    $clients_query = new WP_Query( array(
        'post_type'      => 'client',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    ?>
    <table class="form-table">
        <tbody>
            <!-- Task Date -->
            <tr>
                <th scope="row">
                    <label for="wcsl_task_date"><?php esc_html_e( 'Task Date:', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <input type="date"
                           id="wcsl_task_date"
                           name="wcsl_task_date"
                           value="<?php echo esc_attr( $task_date ); ?>"
                           class="regular-text" required />
                    <p class="description">
                        <?php esc_html_e( 'Note: Please also Update the publishable date also if you set  Next/Previous Month date here.', 'wp-client-support-ledger' ); ?>
                    </p>
                </td>
            </tr>

            <!-- Hours Spent -->
            <tr>
                <th scope="row">
                    <label for="wcsl_hours_spent_on_task"><?php esc_html_e( 'Hours Spent (e.g., "1h 15m", "45m"):', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <input type="text"
                           id="wcsl_hours_spent_on_task"
                           name="wcsl_hours_spent_on_task"
                           value="<?php echo esc_attr( $hours_spent ); ?>"
                           class="regular-text" required />
                    <p class="description"><?php esc_html_e( 'Time spent on this task.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>

            <!-- Task Status -->
            <tr>
                <th scope="row">
                    <label for="wcsl_task_status"><?php esc_html_e( 'Task Status:', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <select id="wcsl_task_status" name="wcsl_task_status">
                        <?php foreach ( $statuses as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $task_status, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Task Link -->
            <tr>
                <th scope="row">
                    <label for="wcsl_task_link"><?php esc_html_e( 'Task Link (URL):', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <input type="url"
                           id="wcsl_task_link"
                           name="wcsl_task_link"
                           value="<?php echo esc_attr( get_post_meta( $post->ID, '_wcsl_task_link', true ) ); ?>"
                           class="regular-text"
                           placeholder="https://example.com/task/123" />
                    <p class="description"><?php esc_html_e( 'Optional: Link to the task in your project management tool (e.g., ClickUp, Asana, Jira).', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>


            <!-- Employee Name --> 
            <tr>
                <th scope="row">
                    <label for="wcsl_employee_name"><?php esc_html_e( 'Employee Name:', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <input type="text"
                           id="wcsl_employee_name"
                           name="wcsl_employee_name"
                           value="<?php echo esc_attr( get_post_meta( $post->ID, '_wcsl_employee_name', true ) ); ?>"
                           class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Name of the employee who performed the task.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>

            <!-- Employee Email -->
            <tr>
                <th scope="row">
                    <label for="wcsl_employee_email"><?php esc_html_e( 'Employee Email Address', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <input type="email"
                        id="wcsl_employee_email"
                        name="wcsl_employee_email"
                        value="<?php echo esc_attr( get_post_meta( $post->ID, '_wcsl_employee_email', true ) ); ?>"
                        class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Employee Primary Email Address (For Notification)', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>


            <!-- Related Client -->
            <tr>
                <th scope="row">
                    <label for="wcsl_related_client_id"><?php esc_html_e( 'Related Client:', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <?php if ( $clients_query->have_posts() ) : ?>
                        <select id="wcsl_related_client_id" name="wcsl_related_client_id" required>
                            <option value=""><?php esc_html_e( '-- Select a Client --', 'wp-client-support-ledger' ); ?></option>
                            <?php while ( $clients_query->have_posts() ) : $clients_query->the_post(); ?>
                                <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php selected( $related_client_id, get_the_ID() ); ?>>
                                    <?php echo esc_html( get_the_title() ); ?>
                                </option>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); // IMPORTANT: Reset post data after custom loop ?>
                        </select>
                    <?php else : ?>
                        <p>
                            <?php esc_html_e( 'No clients found. Please', 'wp-client-support-ledger' ); ?>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=client' ) ); ?>">
                                <?php esc_html_e( 'add a client first', 'wp-client-support-ledger' ); ?>
                            </a>.
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Task Note --> 
        <tr>
            <th scope="row">
                <label for="wcsl_task_note"><?php esc_html_e( 'Task Note:', 'wp-client-support-ledger' ); ?></label>
            </th>
            <td>
                <textarea id="wcsl_task_note"
                name="wcsl_task_note"
                rows="4"
                class="large-text"
                maxlength="150"><?php echo esc_textarea( get_post_meta( $post->ID, '_wcsl_task_note', true ) ); ?></textarea>
                <p class="description">
                <?php esc_html_e( 'Optional: Add any relevant notes for this task (max 150 characters).', 'wp-client-support-ledger' ); ?>
            </td>
        </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Save meta box data for the 'client_task' CPT.
 *
 * @param int $post_id The ID of the post being saved.
 */
function wcsl_client_task_save_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['wcsl_client_task_details_nonce'] ) ||
         ! wp_verify_nonce( $_POST['wcsl_client_task_details_nonce'], 'wcsl_client_task_details_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && 'client_task' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    } else {
        return; // Not our CPT
    }

    // Save Task Date
    if ( isset( $_POST['wcsl_task_date'] ) ) {
        update_post_meta( $post_id, '_wcsl_task_date', sanitize_text_field( $_POST['wcsl_task_date'] ) );
    }
    // Save Hours Spent
    if ( isset( $_POST['wcsl_hours_spent_on_task'] ) ) {
        update_post_meta( $post_id, '_wcsl_hours_spent_on_task', sanitize_text_field( $_POST['wcsl_hours_spent_on_task'] ) );
    }
    // Save Task Status
    if ( isset( $_POST['wcsl_task_status'] ) ) {
        update_post_meta( $post_id, '_wcsl_task_status', sanitize_key( $_POST['wcsl_task_status'] ) );
    }
    // Save Related Client ID
    if ( isset( $_POST['wcsl_related_client_id'] ) ) {
        update_post_meta( $post_id, '_wcsl_related_client_id', intval( $_POST['wcsl_related_client_id'] ) );
    }

    // Save Employee Name
    if ( isset( $_POST['wcsl_employee_name'] ) ) {
        update_post_meta( $post_id, '_wcsl_employee_name', sanitize_text_field( $_POST['wcsl_employee_name'] ) );
    }

    if ( isset( $_POST['wcsl_employee_email'] ) ) {
    update_post_meta( $post_id, '_wcsl_employee_email', sanitize_email( $_POST['wcsl_employee_email'] ) );
    }

    // Save Task Link
    if ( isset( $_POST['wcsl_task_link'] ) ) {
        // Sanitize the URL
        $task_link_url = sanitize_url( $_POST['wcsl_task_link'] );
        update_post_meta( $post_id, '_wcsl_task_link', $task_link_url );
    } else {
        // If not set (e.g. field not submitted or checkbox unchecked if it were one), delete meta
        // For an optional text field, you might just let it be empty if not submitted
        // delete_post_meta( $post_id, '_wcsl_task_link' );
    }

    // Save Task Note
    if ( isset( $_POST['wcsl_task_note'] ) ) {
        // Use wp_kses_post for a textarea if you want to allow some HTML,
        // or sanitize_textarea_field for plain text.
        // For simple notes, sanitize_textarea_field is usually safer.
        update_post_meta( $post_id, '_wcsl_task_note', sanitize_textarea_field( $_POST['wcsl_task_note'] ) );
    } else {
        // If you want to delete the meta if the field is submitted empty or not present
        // delete_post_meta( $post_id, '_wcsl_task_note' );
    }
}
add_action( 'save_post_client_task', 'wcsl_client_task_save_meta_box_data' );