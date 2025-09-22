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
    // Existing meta box for general details
    add_meta_box(
        'wcsl_client_details_meta_box',
        __( 'Client Details', 'wp-client-support-ledger' ),
        'wcsl_client_details_meta_box_html',
        'client',
        'normal',
        'high'
    );

    // *** NEW: Add meta box for billing info ***
    add_meta_box(
        'wcsl_client_billing_meta_box',
        __( 'Client Billing (Optional)', 'wp-client-support-ledger' ),
        'wcsl_client_billing_meta_box_html', // New HTML callback function
        'client',
        'normal',
        'default' // Lower priority so it appears below the main details
    );
}
add_action( 'add_meta_boxes_client', 'wcsl_client_add_meta_boxes' );

/**
 * HTML for the 'Client Details' meta box.
 *
 * @param WP_Post $post The current post object.
 */
function wcsl_client_details_meta_box_html( $post ) {
    wp_nonce_field( 'wcsl_client_details_save', 'wcsl_client_details_nonce' );
    $contracted_hours = get_post_meta( $post->ID, '_wcsl_contracted_support_hours', true );
    $client_email = get_post_meta( $post->ID, '_wcsl_client_contact_email', true );
    $linked_user_id = get_post_meta( $post->ID, '_wcsl_linked_user_id', true );
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="wcsl_contracted_support_hours"><?php esc_html_e( 'Contracted Hours', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <input type="text" id="wcsl_contracted_support_hours" name="wcsl_contracted_support_hours" value="<?php echo esc_attr( $contracted_hours ); ?>" class="regular-text" placeholder="e.g., 2h 30m" />
                    <p class="description"><?php esc_html_e( 'The standard monthly support hours for this client.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wcsl_client_contact_email"><?php esc_html_e( 'Contact Email', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <input type="email" id="wcsl_client_contact_email" name="wcsl_client_contact_email" value="<?php echo esc_attr( $client_email ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Primary contact email. Required for account creation.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>
            
            <?php if ( $linked_user_id && get_userdata($linked_user_id) ) : 
                $user_data = get_userdata( $linked_user_id );
            ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Linked Portal User', 'wp-client-support-ledger' ); ?></th>
                    <td>
                        <p>
                            <strong><?php echo esc_html( $user_data->user_login ); ?></strong> (<?php echo esc_html( $user_data->user_email ); ?>)
                            <a href="<?php echo esc_url( get_edit_user_link( $linked_user_id ) ); ?>" target="_blank" style="margin-left: 10px;"><?php esc_html_e('Edit User Profile', 'wp-client-support-ledger'); ?></a>
                        </p>
                        <p class="description"><?php esc_html_e('This client is linked to a WordPress user account for portal access.', 'wp-client-support-ledger'); ?></p>
                        <input type="hidden" name="wcsl_linked_user_id" value="<?php echo esc_attr($linked_user_id); ?>" />
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin-bottom: 0;"><?php esc_html_e( 'Create Portal Account', 'wp-client-support-ledger' ); ?></h3>
                        <p class="description" style="font-weight: 400;"><?php esc_html_e( 'Optional: Create a user account for the primary client contact to give them portal access.', 'wp-client-support-ledger' ); ?></p>
                    </th>
                </tr>
                <tr>
                    <th scope="row"><label for="wcsl_client_user_login"><?php esc_html_e( 'Username', 'wp-client-support-ledger' ); ?></label></th>
                    <td><input type="text" id="wcsl_client_user_login" name="wcsl_client_user_login" class="regular-text" /></td>
                </tr>
                 <tr>
                    <th scope="row"><label for="wcsl_client_display_name"><?php esc_html_e( 'Contact Name', 'wp-client-support-ledger' ); ?></label></th>
                    <td>
                        <input type="text" id="wcsl_client_display_name" name="wcsl_client_display_name" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'The full name of the contact person (e.g., Jane Doe).', 'wp-client-support-ledger' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcsl_client_user_pass"><?php esc_html_e( 'Password', 'wp-client-support-ledger' ); ?></label></th>
                    <td>
                        <input type="password" id="wcsl_client_user_pass" name="wcsl_client_user_pass" class="regular-text" autocomplete="new-password" />
                        <p class="description"><?php esc_html_e( 'Leave blank to automatically generate a strong password.', 'wp-client-support-ledger' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Send Credentials', 'wp-client-support-ledger' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wcsl_send_client_notification" value="1" checked />
                            <?php esc_html_e( 'Send the new user an email about their account.', 'wp-client-support-ledger' ); ?>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

/**
 * *** NEW: HTML for the 'Client Billing' meta box. ***
 */
function wcsl_client_billing_meta_box_html( $post ) {
    // Nonce is handled by the main details meta box, as they are part of the same form submission.
    // If you were to make them separate forms/save actions, this would need its own nonce.

    $billing_address = get_post_meta( $post->ID, '_wcsl_client_billing_address', true );
    $hourly_rate     = get_post_meta( $post->ID, '_wcsl_client_hourly_rate', true );
    $tax_rate        = get_post_meta( $post->ID, '_wcsl_client_tax_rate', true );
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="wcsl_client_billing_address"><?php esc_html_e( 'Billing Address', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <textarea id="wcsl_client_billing_address"
                              name="wcsl_client_billing_address"
                              rows="4"
                              class="large-text"><?php echo esc_textarea( $billing_address ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'The client\'s billing address, as it should appear on invoices.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wcsl_client_hourly_rate"><?php esc_html_e( 'Hourly Rate', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="wcsl_client_hourly_rate"
                           name="wcsl_client_hourly_rate"
                           value="<?php echo esc_attr( $hourly_rate ); ?>"
                           class="small-text" 
                           step="0.01" 
                           min="0"
                           placeholder="e.g., 100.00" />
                    <p class="description"><?php esc_html_e( 'The billable hourly rate for this client. Do not include currency symbols.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wcsl_client_tax_rate"><?php esc_html_e( 'Tax Rate (%)', 'wp-client-support-ledger' ); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="wcsl_client_tax_rate"
                           name="wcsl_client_tax_rate"
                           value="<?php echo esc_attr( $tax_rate ); ?>"
                           class="small-text"
                           step="0.01"
                           min="0"
                           placeholder="e.g., 7.5" />
                    <p class="description"><?php esc_html_e( 'Enter the tax rate as a percentage (e.g., 7.5 for 7.5%).', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}


/**
 * Save meta box data for the 'client' CPT.
 *
 * @param int $post_id The ID of the post being saved.
 */
function wcsl_client_save_meta_box_data( $post_id ) {
    // Standard checks
    if ( ! isset( $_POST['wcsl_client_details_nonce'] ) || ! wp_verify_nonce( $_POST['wcsl_client_details_nonce'], 'wcsl_client_details_save' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( ! ( isset( $_POST['post_type'] ) && 'client' == $_POST['post_type'] ) ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    // Save standard meta fields
    if ( isset( $_POST['wcsl_contracted_support_hours'] ) ) {
        update_post_meta( $post_id, '_wcsl_contracted_support_hours', sanitize_text_field( $_POST['wcsl_contracted_support_hours'] ) );
    }
    if ( isset( $_POST['wcsl_client_contact_email'] ) ) {
        update_post_meta( $post_id, '_wcsl_client_contact_email', sanitize_email( $_POST['wcsl_client_contact_email'] ) );
    }
    if ( isset( $_POST['wcsl_client_billing_address'] ) ) {
        update_post_meta( $post_id, '_wcsl_client_billing_address', sanitize_textarea_field( $_POST['wcsl_client_billing_address'] ) );
    }
    if ( isset( $_POST['wcsl_client_hourly_rate'] ) ) {
        update_post_meta( $post_id, '_wcsl_client_hourly_rate', floatval( $_POST['wcsl_client_hourly_rate'] ) );
    }
    if ( isset( $_POST['wcsl_client_tax_rate'] ) ) {
        update_post_meta( $post_id, '_wcsl_client_tax_rate', floatval( $_POST['wcsl_client_tax_rate'] ) );
    }

    // --- NEW: Handle WordPress user creation for the client contact ---
    $user_login = isset( $_POST['wcsl_client_user_login'] ) ? sanitize_user( $_POST['wcsl_client_user_login'] ) : '';
    $linked_user_id = get_post_meta( $post_id, '_wcsl_linked_user_id', true );

    // Only proceed if a username was provided AND this client is not already linked
    if ( ! empty( $user_login ) && ! $linked_user_id ) {
        $client_email = get_post_meta( $post_id, '_wcsl_client_contact_email', true );
        $display_name = isset( $_POST['wcsl_client_display_name'] ) ? sanitize_text_field( $_POST['wcsl_client_display_name'] ) : get_the_title( $post_id );

        // Validation
        if ( username_exists( $user_login ) ) { wp_die( 'Error: This username already exists. Please go back and choose a different one.' ); }
        if ( email_exists( $client_email ) ) { wp_die( 'Error: This email address is already registered. Please go back and use a different one.' ); }
        if ( ! is_email( $client_email ) ) { wp_die( 'Error: A valid Contact Email is required to create a user account. Please go back and add one.' ); }

        // Create the user
        $user_password = ! empty( $_POST['wcsl_client_user_pass'] ) ? $_POST['wcsl_client_user_pass'] : wp_generate_password();
        $user_data = array(
            'user_login'   => $user_login,
            'user_pass'    => $user_password,
            'user_email'   => $client_email,
            'display_name' => $display_name,
            'role'         => 'wcsl_client'
        );
        $new_user_id = wp_insert_user( $user_data );

        if ( ! is_wp_error( $new_user_id ) ) {
            // Link the new user ID to this client post
            update_post_meta( $post_id, '_wcsl_linked_user_id', $new_user_id );
            
            if ( isset( $_POST['wcsl_send_client_notification'] ) ) {
                wp_new_user_notification( $new_user_id, null, 'both' );
            }
        } else {
            wp_die( 'Error creating client user: ' . $new_user_id->get_error_message() );
        }
    }
}
add_action( 'save_post_client', 'wcsl_client_save_meta_box_data' );



/**
 * --------------------------------------------------------------------------
 * EMPLOYEE CPT META BOXES
 * --------------------------------------------------------------------------
 */


/**
 * Register meta box(es) for the 'employee' CPT.
 */
function wcsl_employee_add_meta_boxes() {
    add_meta_box(
        'wcsl_employee_email_meta_box',
        __( 'Employee Contact Info', 'wp-client-support-ledger' ),
        'wcsl_employee_email_meta_box_html',
        'employee', // Post type
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_employee', 'wcsl_employee_add_meta_boxes' );


/**
 * HTML for the 'Employee Email' meta box.
 */
function wcsl_employee_email_meta_box_html( $post ) {
    wp_nonce_field( 'wcsl_employee_email_save', 'wcsl_employee_email_nonce' );
    $employee_email = get_post_meta( $post->ID, '_wcsl_employee_contact_email', true );
    $linked_user_id = get_post_meta( $post->ID, '_wcsl_linked_user_id', true );
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="wcsl_employee_contact_email"><?php esc_html_e( 'Employee Email Address:', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <input type="email" id="wcsl_employee_contact_email" name="wcsl_employee_contact_email" value="<?php echo esc_attr( $employee_email ); ?>" class="regular-text" required />
                    <p class="description"><?php esc_html_e( 'Email address for this employee. Required for notifications and account creation.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>

            <?php if ( $linked_user_id ) : 
                $user_data = get_userdata( $linked_user_id );
            ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Linked WordPress User', 'wp-client-support-ledger' ); ?></th>
                    <td>
                        <p>
                            <strong><?php echo esc_html( $user_data->user_login ); ?></strong> (<?php echo esc_html( $user_data->user_email ); ?>)
                            <a href="<?php echo esc_url( get_edit_user_link( $linked_user_id ) ); ?>" target="_blank" style="margin-left: 10px;"><?php esc_html_e('Edit User', 'wp-client-support-ledger'); ?></a>
                        </p>
                        <p class="description"><?php esc_html_e('This employee is linked to a WordPress user account.', 'wp-client-support-ledger'); ?></p>
                        <input type="hidden" name="wcsl_linked_user_id" value="<?php echo esc_attr($linked_user_id); ?>" />
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><h3><?php esc_html_e( 'Create Portal Account', 'wp-client-support-ledger' ); ?></h3></th>
                    <td><p class="description"><?php esc_html_e( 'Optional: Create a WordPress user account for this employee to give them access to the frontend portal.', 'wp-client-support-ledger' ); ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcsl_user_login"><?php esc_html_e( 'Username', 'wp-client-support-ledger' ); ?></label></th>
                    <td><input type="text" id="wcsl_user_login" name="wcsl_user_login" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wcsl_user_pass"><?php esc_html_e( 'Password', 'wp-client-support-ledger' ); ?></label></th>
                    <td>
                        <input type="password" id="wcsl_user_pass" name="wcsl_user_pass" class="regular-text" autocomplete="new-password" />
                        <p class="description"><?php esc_html_e( 'Leave blank to automatically generate a strong password.', 'wp-client-support-ledger' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Send Credentials', 'wp-client-support-ledger' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wcsl_send_user_notification" value="1" checked />
                            <?php esc_html_e( 'Send the new user an email about their account.', 'wp-client-support-ledger' ); ?>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Save meta box data for the 'employee' CPT.
 */
function wcsl_employee_save_meta_box_data( $post_id, $post, $update ) {
    if ( ! isset( $_POST['wcsl_employee_email_nonce'] ) || ! wp_verify_nonce( $_POST['wcsl_employee_email_nonce'], 'wcsl_employee_email_save' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( 'employee' !== $post->post_type ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    // Save the employee contact email first, as it's always needed
    if ( isset( $_POST['wcsl_employee_contact_email'] ) ) {
        $email = sanitize_email( $_POST['wcsl_employee_contact_email'] );
        if ( is_email( $email ) ) {
            update_post_meta( $post_id, '_wcsl_employee_contact_email', $email );
        } else {
            delete_post_meta( $post_id, '_wcsl_employee_contact_email' );
        }
    } else {
         delete_post_meta( $post_id, '_wcsl_employee_contact_email' );
    }

    // --- NEW: Handle WordPress user creation ---
    $user_login = isset( $_POST['wcsl_user_login'] ) ? sanitize_user( $_POST['wcsl_user_login'] ) : '';
    $linked_user_id = get_post_meta( $post_id, '_wcsl_linked_user_id', true );

    // Only proceed if a username was provided AND this employee is not already linked
    if ( ! empty( $user_login ) && ! $linked_user_id ) {
        $employee_name = $post->post_title;
        $employee_email = get_post_meta( $post_id, '_wcsl_employee_contact_email', true );

        // Validation: Ensure required fields exist
        if ( username_exists( $user_login ) ) {
            wp_die( 'Error: This username already exists. Please go back and choose a different one.' );
        }
        if ( email_exists( $employee_email ) ) {
            wp_die( 'Error: This email address is already registered. Please go back and use a different one.' );
        }
        if ( ! is_email( $employee_email ) ) {
            wp_die( 'Error: A valid employee email address is required to create a user account. Please go back and add one.' );
        }

        // Create the user
        $user_password = ! empty( $_POST['wcsl_user_pass'] ) ? $_POST['wcsl_user_pass'] : wp_generate_password();
        $user_data = array(
            'user_login' => $user_login,
            'user_pass'  => $user_password,
            'user_email' => $employee_email,
            'display_name' => $employee_name,
            'role'       => 'wcsl_employee'
        );
        $new_user_id = wp_insert_user( $user_data );

        if ( ! is_wp_error( $new_user_id ) ) {
            // Link the new user ID to this employee post
            update_post_meta( $post_id, '_wcsl_linked_user_id', $new_user_id );

            // Send notification email if checked
            if ( isset( $_POST['wcsl_send_user_notification'] ) ) {
                wp_new_user_notification( $new_user_id, null, 'both' );
            }
        } else {
            // Handle potential errors during user creation
            wp_die( 'Error creating user: ' . $new_user_id->get_error_message() );
        }
    }
}
add_action( 'save_post_employee', 'wcsl_employee_save_meta_box_data', 10, 3 ); // Use save_post_{cpt} and pass 3 args




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
    $task_type = get_post_meta( $post->ID, '_wcsl_task_type', true );
    $attachment_url = get_post_meta( $post->ID, '_wcsl_task_attachment_url', true );

    $task_date = get_post_meta( $post->ID, '_wcsl_task_date', true );
    if ( empty( $task_date ) ) {
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
        'On Hold'  => __( 'On Hold', 'wp-client-support-ledger' ),
        'billed'     => __( 'Billed', 'wp-client-support-ledger' ),
    );
    
    // For "Task Type" dropdown
    $task_types = array(
        'support' => __( 'Support', 'wp-client-support-ledger' ),
        'fixing'  => __( 'Fixing', 'wp-client-support-ledger' ),
    );

    // For "Related Client" dropdown
    $clients_query = new WP_Query( array(
        'post_type'      => 'client',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    ?>
    <table class="form-table">
        <tbody>
            <!-- Task Type (Required) -->
            <tr>
                <th scope="row"><label for="wcsl_task_type"><?php esc_html_e( 'Task Type:', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <select id="wcsl_task_type" name="wcsl_task_type" required>
                        <option value="" <?php selected( empty($task_type) ); ?> disabled><?php esc_html_e( '-- Select a Type --', 'wp-client-support-ledger' ); ?></option>
                        <?php foreach ( $task_types as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $task_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- NEW: Initially hidden Task Category row -->
            <tr id="wcsl_task_category_row" style="display: none;">
                <th scope="row"><label for="wcsl_task_category"><?php esc_html_e( 'Task Category:', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <select id="wcsl_task_category" name="wcsl_task_category">
                        <option value=""><?php esc_html_e( 'Loading...', 'wp-client-support-ledger' ); ?></option>
                    </select>
                    <span class="spinner" style="vertical-align: middle; float: none; display: none;"></span>
                </td>
            </tr>

            <!-- Task Date (Required) -->
            <tr>
                <th scope="row"><label for="wcsl_task_date"><?php esc_html_e( 'Task Date:', 'wp-client-support-ledger' ); ?></label></th>
                <td><input type="date" id="wcsl_task_date" name="wcsl_task_date" value="<?php echo esc_attr( $task_date ); ?>" class="regular-text" required /></td>
            </tr>
            <!-- Hours Spent (Required) -->
            <tr>
                <th scope="row"><label for="wcsl_hours_spent_on_task"><?php esc_html_e( 'Hours Spent:', 'wp-client-support-ledger' ); ?></label></th>
                <td><input type="text" id="wcsl_hours_spent_on_task" name="wcsl_hours_spent_on_task" value="<?php echo esc_attr( $hours_spent ); ?>" class="regular-text" required /></td>
            </tr>
            <!-- Task Status (Required) -->
            <tr>
                <th scope="row"><label for="wcsl_task_status"><?php esc_html_e( 'Task Status:', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <select id="wcsl_task_status" name="wcsl_task_status" required>
                        <?php foreach ( $statuses as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $task_status, $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <!-- Task Link (Required) -->
            <tr>
                <th scope="row"><label for="wcsl_task_link"><?php esc_html_e( 'Task Link (URL):', 'wp-client-support-ledger' ); ?></label></th>
                <td><input type="url" id="wcsl_task_link" name="wcsl_task_link" value="<?php echo esc_attr( get_post_meta( $post->ID, '_wcsl_task_link', true ) ); ?>" class="regular-text" placeholder="https://example.com/task/123" required /></td>
            </tr>
            <!-- Employee Details (Required) -->
            <tr>
                <th scope="row"><label for="wcsl_assigned_employee_id"><?php esc_html_e( 'Assigned Employee:', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <?php $assigned_employee_id = get_post_meta( $post->ID, '_wcsl_assigned_employee_id', true );
                    $employees_query = new WP_Query( array( 'post_type' => 'employee', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) ); ?>
                    <select id="wcsl_assigned_employee_id" name="wcsl_assigned_employee_id" required>
                        <option value=""><?php esc_html_e( '-- Select Employee --', 'wp-client-support-ledger' ); ?></option>
                        <?php if ( $employees_query->have_posts() ) : ?>
                            <?php while ( $employees_query->have_posts() ) : $employees_query->the_post(); ?>
                                <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php selected( $assigned_employee_id, get_the_ID() ); ?>><?php echo esc_html( get_the_title() ); ?></option>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <!-- Related Client (Required) -->
            <tr>
                <th scope="row"><label for="wcsl_related_client_id"><?php esc_html_e( 'Related Client:', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <?php if ( $clients_query->have_posts() ) : ?>
                        <select id="wcsl_related_client_id" name="wcsl_related_client_id" required>
                            <option value=""><?php esc_html_e( '-- Select a Client --', 'wp-client-support-ledger' ); ?></option>
                            <?php while ( $clients_query->have_posts() ) : $clients_query->the_post(); ?>
                                <option value="<?php echo esc_attr( get_the_ID() ); ?>" <?php selected( $related_client_id, get_the_ID() ); ?>><?php echo esc_html( get_the_title() ); ?></option>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </select>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No clients found. Please', 'wp-client-support-ledger' ); ?> <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=client' ) ); ?>"><?php esc_html_e( 'add a client first', 'wp-client-support-ledger' ); ?></a>.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <!-- Task Note (Optional) --> 
            <tr>
                <th scope="row"><label for="wcsl_task_note"><?php esc_html_e( 'Task Note:', 'wp-client-support-ledger' ); ?></label></th>
                <td><textarea id="wcsl_task_note" name="wcsl_task_note" rows="4" class="large-text" maxlength="150"><?php echo esc_textarea( get_post_meta( $post->ID, '_wcsl_task_note', true ) ); ?></textarea></td>
            </tr>
            <!-- Task Attachment (Optional) -->
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Task Attachments:', 'wp-client-support-ledger' ); ?></label></th>
                <td>
                    <input type="hidden" id="wcsl_task_attachment_url" name="wcsl_task_attachment_url" value="<?php echo esc_attr( $attachment_url ); ?>" />
                    <button type="button" class="button" id="wcsl_upload_attachment_button"><?php esc_html_e( 'Select Attachment', 'wp-client-support-ledger' ); ?></button>
                    <button type="button" class="button button-secondary" id="wcsl_remove_attachment_button" style="<?php echo empty($attachment_url) ? 'display:none;' : ''; ?>"><?php esc_html_e( 'Remove Attachment', 'wp-client-support-ledger' ); ?></button>
                    <div id="wcsl_attachment_preview" style="margin-top: 10px;"><?php if ( ! empty( $attachment_url ) ) { echo '<img src="' . esc_url( $attachment_url ) . '" style="max-width:200px; height:auto; border:1px solid #ddd;" />'; } ?></div>
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
    // Standard checks
    if ( ! isset( $_POST['wcsl_client_task_details_nonce'] ) || ! wp_verify_nonce( $_POST['wcsl_client_task_details_nonce'], 'wcsl_client_task_details_save' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( ! ( isset( $_POST['post_type'] ) && 'client_task' == $_POST['post_type'] ) ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    // Static flag to prevent infinite loops
    static $is_saving = false;
    if ( $is_saving ) {
        return;
    }
    $is_saving = true;

    // Save all standard meta fields
    if ( isset( $_POST['wcsl_task_type'] ) ) { $task_type = sanitize_key( $_POST['wcsl_task_type'] ); if ( in_array( $task_type, array( 'support', 'fixing' ) ) ) { update_post_meta( $post_id, '_wcsl_task_type', $task_type ); } }
    if ( isset( $_POST['wcsl_task_date'] ) ) { update_post_meta( $post_id, '_wcsl_task_date', sanitize_text_field( $_POST['wcsl_task_date'] ) ); }
    if ( isset( $_POST['wcsl_hours_spent_on_task'] ) ) { update_post_meta( $post_id, '_wcsl_hours_spent_on_task', sanitize_text_field( $_POST['wcsl_hours_spent_on_task'] ) ); }
    if ( isset( $_POST['wcsl_task_status'] ) ) { update_post_meta( $post_id, '_wcsl_task_status', sanitize_key( $_POST['wcsl_task_status'] ) ); }
    if ( isset( $_POST['wcsl_related_client_id'] ) ) { update_post_meta( $post_id, '_wcsl_related_client_id', intval( $_POST['wcsl_related_client_id'] ) ); }
    if ( isset( $_POST['wcsl_assigned_employee_id'] ) ) {
        $employee_id = intval( $_POST['wcsl_assigned_employee_id'] );
        if ( $employee_id > 0 ) {
            update_post_meta( $post_id, '_wcsl_assigned_employee_id', $employee_id );
            $employee_post = get_post( $employee_id );
            if ( $employee_post && $employee_post->post_type === 'employee' ) {
                update_post_meta( $post_id, '_wcsl_employee_name', sanitize_text_field( $employee_post->post_title ) );
                $employee_contact_email = get_post_meta( $employee_id, '_wcsl_employee_contact_email', true );
                if ( is_email( $employee_contact_email ) ) { update_post_meta( $post_id, '_wcsl_employee_email', $employee_contact_email ); } else { delete_post_meta( $post_id, '_wcsl_employee_email' ); }
            }
        } else {
            delete_post_meta( $post_id, '_wcsl_assigned_employee_id' );
            delete_post_meta( $post_id, '_wcsl_employee_name' );
            delete_post_meta( $post_id, '_wcsl_employee_email' );
        }
    }
    if ( isset( $_POST['wcsl_task_link'] ) ) { update_post_meta( $post_id, '_wcsl_task_link', esc_url_raw( $_POST['wcsl_task_link'] ) ); }
    if ( isset( $_POST['wcsl_task_note'] ) ) { update_post_meta( $post_id, '_wcsl_task_note', sanitize_textarea_field( $_POST['wcsl_task_note'] ) ); }
    if ( isset( $_POST['wcsl_task_attachment_url'] ) ) { update_post_meta( $post_id, '_wcsl_task_attachment_url', esc_url_raw( $_POST['wcsl_task_attachment_url'] ) ); }
    
    // --- NEW LOGIC: Save the selected Task Category with a default fallback ---
    if ( isset( $_POST['wcsl_task_type'] ) ) {
        $term_id_to_set = 0;
        
        // Check if a specific category was chosen from the dropdown
        if ( ! empty( $_POST['wcsl_task_category'] ) ) {
            $term_id_to_set = intval( $_POST['wcsl_task_category'] );
        } else {
            // If not, find the appropriate default category ID
            $default_term_ids = get_option('wcsl_default_term_ids', array());
            $primary_type = sanitize_key( $_POST['wcsl_task_type'] );

            if ( 'support' === $primary_type && ! empty( $default_term_ids['support'] ) ) {
                $term_id_to_set = $default_term_ids['support'];
            } elseif ( 'fixing' === $primary_type && ! empty( $default_term_ids['fixing'] ) ) {
                $term_id_to_set = $default_term_ids['fixing'];
            }
        }

        // Now, set the term for the post
        if ( $term_id_to_set > 0 ) {
            wp_set_post_terms( $post_id, $term_id_to_set, 'task_category' );
        } else {
            // If for some reason we still don't have a term, clear any existing ones
            wp_set_post_terms( $post_id, '', 'task_category' );
        }
    }

    // Reset the flag
    $is_saving = false;
}
add_action( 'save_post_client_task', 'wcsl_client_task_save_meta_box_data' );


/**
 * Redirects the user back to the Monthly Overview page after saving a task.
 *
 * @param string  $location The default redirect location.
 * @param int     $post_id  The ID of the post that was just saved.
 * @return string The new, modified redirect location.
 */
function wcsl_redirect_after_task_save( $location, $post_id ) {
    // Check if the post being saved is our 'client_task' CPT and if a redirect is desired
    if ( isset( $_POST['post_type'] ) && 'client_task' === $_POST['post_type'] ) {
        // Get the task date from the submitted form data
        $task_date_str = isset( $_POST['wcsl_task_date'] ) ? sanitize_text_field( $_POST['wcsl_task_date'] ) : '';
        
        if ( ! empty( $task_date_str ) ) {
            // Create a timestamp from the date
            $timestamp = strtotime( $task_date_str );
            
            // Extract the month and year from the timestamp
            $month = date( 'n', $timestamp );
            $year  = date( 'Y', $timestamp );
            
            // Build the URL for the correct Monthly Overview details page
            $new_location = add_query_arg(
                array(
                    'page'   => 'wcsl-main-menu',
                    'action' => 'view_month_details',
                    'month'  => $month,
                    'year'   => $year,
                ),
                admin_url( 'admin.php' )
            );
            
            // Return our new URL to override the default
            return $new_location;
        }
    }
    
    // If it's not our CPT or something went wrong, return the default location
    return $location;
}
add_filter( 'redirect_post_location', 'wcsl_redirect_after_task_save', 10, 2 );