<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin settings.
 */
function wcsl_register_settings() {
    // Register a setting group
    register_setting(
        'wcsl_settings_group', // Option group. Must match settings_fields() call in the form.
        'wcsl_email_notification_settings', // Option name. This will store an array of settings.
        'wcsl_sanitize_email_notification_settings' // Sanitization callback.
    );

    // Add settings section for Email Notifications
    add_settings_section(
        'wcsl_email_notifications_section', // ID for the section
        __( 'Email Notification Settings', 'wp-client-support-ledger' ), // Title of the section
        'wcsl_email_notifications_section_cb', // Callback for section description
        'wcsl_email_settings_section_page' // *** PAGE SLUG for do_settings_sections() in the form ***
    );

    // Add fields to the section
    add_settings_field(
        'admin_recipients', // ID of the field
        __( 'Admin Notification Emails', 'wp-client-support-ledger' ), // Label for the field
        'wcsl_field_admin_recipients_cb', // Callback to render the field HTML
        'wcsl_email_settings_section_page', // *** PAGE SLUG - must match add_settings_section and do_settings_sections ***
        'wcsl_email_notifications_section', // Section ID this field belongs to
        array( 'label_for' => 'wcsl_admin_recipients' ) // Args for the callback
    );
    add_settings_field(
        'enable_email_new_client_admin',
        __( 'Notify Admin on New Client', 'wp-client-support-ledger' ),
        'wcsl_field_checkbox_cb',
        'wcsl_email_settings_section_page', // *** PAGE SLUG ***
        'wcsl_email_notifications_section',
        array( 
            'label_for' => 'wcsl_enable_email_new_client_admin', // Matches input ID
            'option_name' => 'wcsl_email_notification_settings', // The option where this array key is stored
            'field_key' => 'enable_email_new_client_admin',    // The key within the option array
            'description' => __('Send an email to admin(s) when a new client is published.'),
            'default' => 1 // Checked by default
        )
    );
    add_settings_field(
        'enable_email_new_task_employee',
        __( 'Notify Employee on New Task', 'wp-client-support-ledger' ),
        'wcsl_field_checkbox_cb',
        'wcsl_email_settings_section_page', // *** PAGE SLUG ***
        'wcsl_email_notifications_section',
        array( 
            'label_for' => 'wcsl_enable_email_new_task_employee',
            'option_name' => 'wcsl_email_notification_settings',
            'field_key' => 'enable_email_new_task_employee',
            'description' => __('Send an email to the employee (if email is set on the task) when a new task is published.'),
            'default' => 1
        )
    );
    add_settings_field(
        'enable_email_hours_exceeded_admin',
        __( 'Notify Admin on Hours Exceeded', 'wp-client-support-ledger' ),
        'wcsl_field_checkbox_cb',
        'wcsl_email_settings_section_page', // *** PAGE SLUG ***
        'wcsl_email_notifications_section',
        array( 
            'label_for' => 'wcsl_enable_email_hours_exceeded_admin',
            'option_name' => 'wcsl_email_notification_settings',
            'field_key' => 'enable_email_hours_exceeded_admin',
            'description' => __('Send an email to admin(s) when a client\'s hours are exceeded.'),
            'default' => 1
        )
    );
    add_settings_field(
        'enable_email_hours_exceeded_client',
        __( 'Notify Client on Hours Exceeded', 'wp-client-support-ledger' ),
        'wcsl_field_checkbox_cb',
        'wcsl_email_settings_section_page', // *** PAGE SLUG ***
        'wcsl_email_notifications_section',
        array( 
            'label_for' => 'wcsl_enable_email_hours_exceeded_client',
            'option_name' => 'wcsl_email_notification_settings',
            'field_key' => 'enable_email_hours_exceeded_client',
            'description' => __('Send an email to the client (if contact email is set) when their hours are exceeded.'),
            'default' => 0 // Off by default for client-facing
        )
    );

      // --- Add a new section for Frontend Appearance Settings ---
    add_settings_section(
        'wcsl_frontend_appearance_section', // ID
        __( 'Frontend Shortcode Appearance', 'wp-client-support-ledger' ), // Title
        'wcsl_frontend_appearance_section_cb', // Callback for section description
        'wcsl_email_settings_section_page' // Same page slug as other settings for now
    );

    // Field for Frontend Container Background Color
    add_settings_field(
        'frontend_container_bg_color',
        __( 'Report Container Background', 'wp-client-support-ledger' ),
        'wcsl_field_color_picker_cb', // New callback for color picker
        'wcsl_email_settings_section_page',
        'wcsl_frontend_appearance_section',
        array(
            'label_for'   => 'wcsl_frontend_container_bg_color',
            'option_name' => 'wcsl_email_notification_settings', // Storing in existing option array
            'field_key'   => 'frontend_container_bg_color',
            'default'     => '#39618C' // Your current default blue
        )
    );

    // --- NEW/UPDATED FIELDS FOR BUTTONS AND PAGINATION ---
    add_settings_field(
        'frontend_button_bg_color', // General button background
        __( 'Button Background Color', 'wp-client-support-ledger' ),
        'wcsl_field_color_picker_cb',
        'wcsl_email_settings_section_page',
        'wcsl_frontend_appearance_section',
        array(
            'label_for'   => 'wcsl_frontend_button_bg_color',
            'option_name' => 'wcsl_email_notification_settings',
            'field_key'   => 'frontend_button_bg_color',
            'default'     => '#39618C', // NEW PRIMARY COLOR AS DEFAULT FOR BUTTONS
            'description' => __('Background color for general buttons (View Details, Print, Login, Filter).', 'wp-client-support-ledger')
        )
    );
     add_settings_field(
        'frontend_button_text_color', // General button text
        __( 'Button Text Color', 'wp-client-support-ledger' ),
        'wcsl_field_color_picker_cb',
        'wcsl_email_settings_section_page',
        'wcsl_frontend_appearance_section',
        array(
            'label_for'   => 'wcsl_frontend_button_text_color',
            'option_name' => 'wcsl_email_notification_settings',
            'field_key'   => 'frontend_button_text_color',
            'default'     => '#FFFFFF', // Default white text
            'description' => __('Text color for general buttons.', 'wp-client-support-ledger')
        )
    );
    add_settings_field(
        'frontend_pagination_active_bg',
        __( 'Pagination Active Background', 'wp-client-support-ledger' ),
        'wcsl_field_color_picker_cb',
        'wcsl_email_settings_section_page',
        'wcsl_frontend_appearance_section',
        array(
            'label_for'   => 'wcsl_frontend_pagination_active_bg',
            'option_name' => 'wcsl_email_notification_settings',
            'field_key'   => 'frontend_pagination_active_bg',
            'default'     => '#2c4a6b', // Darker shade of your primary for active pagination
            'description' => __('Background color for the current/active pagination link.', 'wp-client-support-ledger')
        )
    );
    add_settings_field(
        'frontend_pagination_active_text',
        __( 'Pagination Active Text Color', 'wp-client-support-ledger' ),
        'wcsl_field_color_picker_cb',
        'wcsl_email_settings_section_page',
        'wcsl_frontend_appearance_section',
        array(
            'label_for'   => 'wcsl_frontend_pagination_active_text',
            'option_name' => 'wcsl_email_notification_settings',
            'field_key'   => 'frontend_pagination_active_text',
            'default'     => '#FFFFFF', // Default white text
            'description' => __('Text color for the current/active pagination link.', 'wp-client-support-ledger')
        )
    );
}
add_action( 'admin_init', 'wcsl_register_settings' );

/**
 * Callback for the email notifications section description.
 */
function wcsl_email_notifications_section_cb() {
    echo '<p>' . esc_html__( 'Configure email notifications for various plugin events. Ensure your WordPress site is configured to send emails reliably (e.g., via an SMTP plugin).', 'wp-client-support-ledger' ) . '</p>';
}

/**
 * Callback to render the Admin Recipients textarea field.
 * $args will contain 'label_for'.
 */
function wcsl_field_admin_recipients_cb( $args ) {
    $options = get_option( 'wcsl_email_notification_settings' );
    // Use get_option('admin_email') as a fallback if the setting is not yet saved or is empty.
    $value = isset( $options['admin_recipients'] ) && !empty( $options['admin_recipients'] ) ? $options['admin_recipients'] : get_option('admin_email');
    ?>
    <textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" 
              name="wcsl_email_notification_settings[admin_recipients]" 
              rows="3" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php esc_html_e( 'Enter admin email addresses, one per line, to receive selected notifications. Defaults to site admin email if left empty.', 'wp-client-support-ledger' ); ?></p>
    <?php
}

/**
 * Callback to render a generic checkbox field.
 * $args will contain 'label_for', 'option_name', 'field_key', 'description', 'default'.
 */
function wcsl_field_checkbox_cb( $args ) {
    $options = get_option( $args['option_name'] ); // e.g., 'wcsl_email_notification_settings'
    $field_key = $args['field_key'];              // e.g., 'enable_email_new_client_admin'
    
    // Determine the checked state: if the key exists in options, use its value. Otherwise, use the default.
    // If options haven't been saved yet, $options might be false.
    if ( $options && isset( $options[$field_key] ) ) {
        $checked = intval( $options[$field_key] );
    } else {
        $checked = isset($args['default']) ? intval($args['default']) : 0;
    }
    ?>
    <label for="<?php echo esc_attr( $args['label_for'] ); ?>">
        <input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="<?php echo esc_attr( $args['option_name'] . '[' . $field_key . ']' ); ?>"
               value="1" <?php checked( 1, $checked ); ?> />
        <?php echo esc_html( $args['description'] ); ?>
    </label>
    <?php
}

/**
 * Sanitize email notification settings.
 */
function wcsl_sanitize_email_notification_settings( $input ) {
    $sanitized_input = array();
    $existing_options = get_option( 'wcsl_email_notification_settings', array() );
    // Start with existing values to preserve settings not in the current form submission
    // (though for a single settings page, all relevant $input keys should be present or absent if checkbox)
    $sanitized_input = $existing_options; 

    // --- Sanitize Email Recipients ---
    if ( isset( $input['admin_recipients'] ) ) {
        $emails_str = trim($input['admin_recipients']);
        if (!empty($emails_str)) {
            $emails = array_map('trim', explode( "\n", $emails_str ));
            $valid_emails = array();
            foreach ( $emails as $email ) {
                if ( is_email( $email ) ) {
                    $valid_emails[] = $email;
                }
            }
            $sanitized_input['admin_recipients'] = implode( "\n", $valid_emails );
        } else {
            $sanitized_input['admin_recipients'] = ''; // Save empty if submitted empty
        }
    } // If not in $input, $existing_options value is kept.

    // --- Sanitize Email Checkboxes ---
    $email_checkboxes = array(
        'enable_email_new_client_admin',
        'enable_email_new_task_employee',
        'enable_email_hours_exceeded_admin',
        'enable_email_hours_exceeded_client'
    );
    foreach ($email_checkboxes as $key) {
        $sanitized_input[$key] = isset( $input[$key] ) ? 1 : 0;
    }

    // --- Sanitize ALL Color Fields ---
    $color_fields_with_defaults = array(
        'frontend_container_bg_color'    => '#39618C',
        'frontend_button_bg_color'       => '#39618C', // Changed default to primary
        'frontend_button_text_color'     => '#FFFFFF',
        'frontend_pagination_active_bg'  => '#2c4a6b', // Darker primary for active pagination
        'frontend_pagination_active_text'=> '#FFFFFF'
    );

    foreach ( $color_fields_with_defaults as $field_key => $default_color ) {
        if ( isset( $input[$field_key] ) ) {
            $color_val = sanitize_text_field( $input[$field_key] );
            if ( preg_match( '/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $color_val ) ) {
                $sanitized_input[$field_key] = strtoupper( $color_val );
            } else {
                // Invalid format, revert to existing or default
                $sanitized_input[$field_key] = isset( $existing_options[$field_key] ) ? $existing_options[$field_key] : $default_color;
            }
        } else {
            // If field not in $input (e.g., something went wrong with form submission for this field)
            // It will retain its value from $existing_options, or if it's a new field not yet in options,
            // it might be better to set its default here if not already covered by $existing_options = get_option(...)
            if ( !isset( $existing_options[$field_key] ) ) {
                 $sanitized_input[$field_key] = $default_color;
            }
        }
    }
    
    return $sanitized_input;
}


function wcsl_frontend_appearance_section_cb() {
    echo '<p>' . esc_html__( 'Customize the colors of the frontend client report shortcode.', 'wp-client-support-ledger' ) . '</p>';
}

/**
 * Callback to render a color picker field.
 * Enqueues WordPress color picker scripts.
 */
function wcsl_field_color_picker_cb( $args ) {
    // Enqueue the WordPress color picker scripts and styles
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' ); // Default WP handle
    // You might need to add 'iris' as a dependency for wp-color-picker if it's not automatically handled:
    // wp_enqueue_script( 'my-plugin-color-picker-js', plugin_dir_url(__FILE__) . '../assets/js/admin-color-picker.js', array( 'jquery', 'wp-color-picker' ), '1.0.0', true );


    $options = get_option( $args['option_name'] );
    $field_key = $args['field_key'];
    $value = isset( $options[$field_key] ) && !empty($options[$field_key]) ? $options[$field_key] : $args['default'];
    ?>
    <input type="text"
           id="<?php echo esc_attr( $args['label_for'] ); ?>"
           name="<?php echo esc_attr( $args['option_name'] . '[' . $field_key . ']' ); ?>"
           value="<?php echo esc_attr( $value ); ?>"
           class="wcsl-color-picker-field"
           data-default-color="<?php echo esc_attr( $args['default'] ); ?>" />
    <p class="description">
        <?php printf(esc_html__('Select a color. Default: %s', 'wp-client-support-ledger'), esc_html($args['default'])); ?>
    </p>
    <?php
}
