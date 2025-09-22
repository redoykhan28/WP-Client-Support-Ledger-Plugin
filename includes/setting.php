<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin settings.
 */
function wcsl_register_settings() {
    // --- Group 1: Notifications & (REMOVED) Admin Shortcode Appearance ---
    register_setting(
        'wcsl_settings_group',
        'wcsl_email_notification_settings',
        'wcsl_sanitize_email_notification_settings'
    );

    // This section remains for notifications
    add_settings_section(
        'wcsl_email_notifications_section',
        __( 'Email Notification Settings', 'wp-client-support-ledger' ),
        'wcsl_email_notifications_section_cb',
        'wcsl_email_settings_section_page'
    );

    // All notification fields remain the same
    add_settings_field('admin_recipients', __( 'Admin Notification Emails', 'wp-client-support-ledger' ), 'wcsl_field_admin_recipients_cb', 'wcsl_email_settings_section_page', 'wcsl_email_notifications_section', array( 'label_for' => 'wcsl_admin_recipients' ) );
    add_settings_field('enable_email_new_client_admin', __( 'Notify Admin on New Client', 'wp-client-support-ledger' ), 'wcsl_field_checkbox_cb', 'wcsl_email_settings_section_page', 'wcsl_email_notifications_section', array( 'label_for' => 'wcsl_enable_email_new_client_admin', 'option_name' => 'wcsl_email_notification_settings', 'field_key' => 'enable_email_new_client_admin', 'description' => __('Send an email to admin(s) when a new client is published.'), 'default' => 1 ) );
    add_settings_field('enable_email_new_task_employee', __( 'Notify Employee on New Task', 'wp-client-support-ledger' ), 'wcsl_field_checkbox_cb', 'wcsl_email_settings_section_page', 'wcsl_email_notifications_section', array( 'label_for' => 'wcsl_enable_email_new_task_employee', 'option_name' => 'wcsl_email_notification_settings', 'field_key' => 'enable_email_new_task_employee', 'description' => __('Send an email to the employee (if email is set on the task) when a new task is published.'), 'default' => 1 ) );
    add_settings_field('enable_email_hours_exceeded_admin', __( 'Notify Admin on Hours Exceeded', 'wp-client-support-ledger' ), 'wcsl_field_checkbox_cb', 'wcsl_email_settings_section_page', 'wcsl_email_notifications_section', array( 'label_for' => 'wcsl_enable_email_hours_exceeded_admin', 'option_name' => 'wcsl_email_notification_settings', 'field_key' => 'enable_email_hours_exceeded_admin', 'description' => __('Send an email to admin(s) when a client\'s hours are exceeded.'), 'default' => 1 ) );
    add_settings_field('enable_email_hours_exceeded_client', __( 'Notify Client on Hours Exceeded', 'wp-client-support-ledger' ), 'wcsl_field_checkbox_cb', 'wcsl_email_settings_section_page', 'wcsl_email_notifications_section', array( 'label_for' => 'wcsl_enable_email_hours_exceeded_client', 'option_name' => 'wcsl_email_notification_settings', 'field_key' => 'enable_email_hours_exceeded_client', 'description' => __('Send an email to the client (if contact email is set) when their hours are exceeded.'), 'default' => 0 ) );
    
    // NOTE: The entire 'Admin Shortcode Appearance' section and its fields have been removed.

    // --- Group 2: Invoice Settings (Unchanged) ---
    register_setting('wcsl_invoice_settings_group', 'wcsl_invoice_settings', 'wcsl_sanitize_invoice_settings');
    add_settings_section('wcsl_invoice_details_section', __( 'Invoice & Company Details', 'wp-client-support-ledger' ), 'wcsl_invoice_details_section_cb', 'wcsl_invoice_settings_page');
    add_settings_field('wcsl_invoice_company_name', __( 'Company Name', 'wp-client-support-ledger' ), 'wcsl_field_text_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'company_name', 'label_for' => 'wcsl_company_name_field', 'description' => 'Your company name as it should appear on invoices.' ) );
    add_settings_field('wcsl_invoice_company_address', __( 'Company Address', 'wp-client-support-ledger' ), 'wcsl_field_textarea_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'company_address', 'label_for' => 'wcsl_company_address_field', 'rows' => 4, 'description' => 'Your company address, one line per entry.' ) );
    add_settings_field('wcsl_invoice_company_email', __( 'Company Email', 'wp-client-support-ledger' ), 'wcsl_field_text_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'company_email', 'label_for' => 'wcsl_company_email_field', 'type' => 'email', 'description' => 'Your company contact/billing email.' ) );
    add_settings_field('wcsl_invoice_company_phone', __( 'Company Phone', 'wp-client-support-ledger' ), 'wcsl_field_text_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'company_phone', 'label_for' => 'wcsl_company_phone_field', 'description' => 'Your company phone number.' ) );
    add_settings_field('wcsl_invoice_logo', __( 'Invoice Logo', 'wp-client-support-ledger' ), 'wcsl_field_logo_uploader_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'invoice_logo', 'label_for' => 'wcsl_invoice_logo_id' ) );
    add_settings_field('wcsl_invoice_next_number', __( 'Next Invoice Number', 'wp-client-support-ledger' ), 'wcsl_field_text_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'next_invoice_number', 'label_for' => 'wcsl_next_invoice_number_field', 'type' => 'number', 'description' => 'The next invoice will use this number. It will auto-increment.' ) );
    add_settings_field('wcsl_invoice_currency_symbol', __( 'Currency Symbol', 'wp-client-support-ledger' ), 'wcsl_field_text_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'currency_symbol', 'label_for' => 'wcsl_currency_symbol_field', 'default' => '$', 'description' => 'e.g., $, €, £' ) );
    add_settings_field('wcsl_invoice_footer_text', __( 'Invoice Footer Text', 'wp-client-support-ledger' ), 'wcsl_field_textarea_cb', 'wcsl_invoice_settings_page', 'wcsl_invoice_details_section', array( 'option_name' => 'wcsl_invoice_settings', 'field_key' => 'footer_text', 'label_for' => 'wcsl_footer_text_field', 'rows' => 4, 'description' => 'Text to appear at the bottom of invoices.' ) );

    // --- Group 3: Portal Settings (Configuration & NEW Appearance) ---
    register_setting('wcsl_portal_settings_group', 'wcsl_portal_settings', 'wcsl_sanitize_portal_settings');
    add_settings_section('wcsl_portal_config_section', __( 'Portal Configuration', 'wp-client-support-ledger' ), 'wcsl_portal_settings_section_cb', 'wcsl_portal_settings_page');
    add_settings_field('portal_page', __( 'Portal Page', 'wp-client-support-ledger' ), 'wcsl_field_page_dropdown_cb', 'wcsl_portal_settings_page', 'wcsl_portal_config_section', array( 'option_name' => 'wcsl_portal_settings', 'field_key' => 'portal_page_id', 'description' => __('Select the page where you have placed the <code>[wcsl_portal]</code> shortcode.', 'wp-client-support-ledger') ) );
    
    // NOTE: The old 'Portal Appearance' section is now removed.

    // NEW Employee Portal Appearance Section
    add_settings_section('wcsl_employee_portal_appearance_section', __( 'Employee Portal Appearance', 'wp-client-support-ledger' ), null, 'wcsl_portal_settings_page');
    add_settings_field('emp_main_bg', __( 'Main Background Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_employee_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'emp_main_bg', 'default' => '#CCD8D6'));
    add_settings_field('emp_content_bg', __( 'Content Background Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_employee_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'emp_content_bg', 'default' => '#F0F5F3'));
    add_settings_field('emp_primary_color', __( 'Primary Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_employee_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'emp_primary_color', 'default' => '#3E624D'));
    add_settings_field('emp_accent_color', __( 'Accent Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_employee_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'emp_accent_color', 'default' => '#D4F838'));
    add_settings_field('emp_icon_color', __( 'Metric Icon Color', 'wp-client-support-ledger' ), 'wcsl_field_radio_cb', 'wcsl_portal_settings_page', 'wcsl_employee_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'emp_icon_color', 'options' => array('default' => 'Default (Dark)', 'white' => 'White') ) );
    // NEW Client Portal Appearance Section
    add_settings_section('wcsl_client_portal_appearance_section', __( 'Client Portal Appearance', 'wp-client-support-ledger' ), null, 'wcsl_portal_settings_page');
    add_settings_field('client_main_bg', __( 'Main Background Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_client_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'client_main_bg', 'default' => '#CCD8D6'));
    add_settings_field('client_content_bg', __( 'Content Background Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_client_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'client_content_bg', 'default' => '#F0F5F3'));
    add_settings_field('client_primary_color', __( 'Primary Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_client_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'client_primary_color', 'default' => '#4A90E2'));
    add_settings_field('client_accent_color', __( 'Accent Color', 'wp-client-support-ledger' ), 'wcsl_field_color_picker_cb', 'wcsl_portal_settings_page', 'wcsl_client_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'client_accent_color', 'default' => '#7ED321'));
    add_settings_field('client_icon_color', __( 'Metric Icon Color', 'wp-client-support-ledger' ), 'wcsl_field_radio_cb', 'wcsl_portal_settings_page', 'wcsl_client_portal_appearance_section', array('option_name' => 'wcsl_portal_settings', 'field_key' => 'client_icon_color', 'options' => array('default' => 'Default (Dark)', 'white' => 'White') ) );    
    // --- Group 4: Import/Export ---
    add_settings_section(
        'wcsl_import_export_section',
        __( 'Import / Export Settings', 'wp-client-support-ledger' ),
        'wcsl_import_export_section_cb',
        'wcsl_import_export_page'
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
    echo '<p>' . esc_html__( 'Customize the colors of the frontend report when viewed by an Administrator.', 'wp-client-support-ledger' ) . '</p>';
}

/**
 * Callback to render a color picker field.
 * Enqueues WordPress color picker scripts.
 */
function wcsl_field_color_picker_cb( $args ) {
    $options = get_option( $args['option_name'] );
    $field_key = $args['field_key'];
    $default = isset($args['default']) ? $args['default'] : '#FFFFFF';
    $value = isset( $options[$field_key] ) && !empty($options[$field_key]) ? $options[$field_key] : $default;
    ?>
    <input type="text"
           id="<?php echo esc_attr( $field_key ); // Use field_key for a unique ID ?>"
           name="<?php echo esc_attr( $args['option_name'] . '[' . $field_key . ']' ); ?>"
           value="<?php echo esc_attr( $value ); ?>"
           class="wcsl-color-picker-field"
           data-default-color="<?php echo esc_attr( $default ); ?>" />
    <p class="description">
        <?php printf(esc_html__('Select a color. Default: %s', 'wp-client-support-ledger'), esc_html($default)); ?>
    </p>
    <?php
}


/**
 * Callback to render generic radio button fields.
 */
function wcsl_field_radio_cb( $args ) {
    $options = get_option( $args['option_name'] );
    $field_key = $args['field_key'];
    $current_value = isset( $options[$field_key] ) ? $options[$field_key] : 'default';
    
    foreach ( $args['options'] as $value => $label ) {
        echo '<label style="margin-right: 20px;">';
        echo '<input type="radio" name="' . esc_attr( $args['option_name'] . '[' . $field_key . ']' ) . '" value="' . esc_attr( $value ) . '" ' . checked( $current_value, $value, false ) . ' />';
        echo ' ' . esc_html( $label );
        echo '</label>';
    }
}



/**
 * Callback for the invoice details section description.
 */
function wcsl_invoice_details_section_cb() {
    echo '<p>' . esc_html__( 'Enter your company details and invoice defaults. This information will be used when generating PDFs.', 'wp-client-support-ledger' ) . '</p>';
}

/**
 * Generic callback to render a text/email/number input field.
 */
function wcsl_field_text_cb( $args ) {
    $options = get_option( $args['option_name'] );
    $field_key = $args['field_key'];
    $default = isset($args['default']) ? $args['default'] : '';
    $value = isset( $options[$field_key] ) ? $options[$field_key] : $default;
    $type = isset($args['type']) ? $args['type'] : 'text';
    
    // Build the HTML for the input field
    $html = sprintf(
        '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" />',
        esc_attr( $type ),
        esc_attr( $args['label_for'] ),
        esc_attr( $args['option_name'] . '[' . $field_key . ']' ),
        esc_attr( $value )
    );
    
    // Add the description paragraph if it exists
    if (isset($args['description'])) {
        $html .= '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    echo $html;
}

/**
 * Generic callback to render a textarea field.
 */
function wcsl_field_textarea_cb( $args ) {
    $options = get_option( $args['option_name'] );
    $field_key = $args['field_key'];
    $default = isset($args['default']) ? $args['default'] : '';
    $value = isset( $options[$field_key] ) ? $options[$field_key] : $default;
    $rows = isset($args['rows']) ? intval($args['rows']) : 4;
    
    // Build the HTML for the textarea
    $html = sprintf(
        '<textarea id="%s" name="%s" rows="%d" class="large-text">%s</textarea>',
        esc_attr( $args['label_for'] ),
        esc_attr( $args['option_name'] . '[' . $field_key . ']' ),
        esc_attr( $rows ),
        esc_textarea( $value ) // Use esc_textarea for content between tags
    );

    // Add the description paragraph if it exists
    if (isset($args['description'])) {
        $html .= '<p class="description">' . esc_html($args['description']) . '</p>';
    }

    echo $html;
}


/**
 * Callback for the logo uploader field.
 */
function wcsl_field_logo_uploader_cb( $args ) {
    $options = get_option( $args['option_name'] );
    $field_key = $args['field_key'];
    $value = isset( $options[$field_key] ) ? $options[$field_key] : ''; 
    ?>
    <!-- <<< NEW UPLOADER HTML STRUCTURE >>> -->
    <div class="wcsl-media-uploader-wrapper">
        <!-- This hidden input will store the image URL -->
        <input type="hidden"
               id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="<?php echo esc_attr( $args['option_name'] . '[' . $field_key . ']' ); ?>"
               value="<?php echo esc_attr( $value ); ?>" />
        
        <!-- The uploader button -->
        <button type="button" class="button" id="wcsl_upload_invoice_logo_button">
            <?php esc_html_e( 'Select Image', 'wp-client-support-ledger' ); ?>
        </button>

        <!-- The remove button (initially hidden) -->
        <button type="button" class="button button-secondary" id="wcsl_remove_invoice_logo_button" style="<?php echo empty($value) ? 'display:none;' : ''; ?>">
            <?php esc_html_e( 'Remove Image', 'wp-client-support-ledger' ); ?>
        </button>
        
        <p class="description">
            <?php esc_html_e( 'Upload or select a logo for your invoices.', 'wp-client-support-ledger' ); ?>
        </p>

        <!-- The preview area -->
        <div id="wcsl_invoice_logo_preview" style="margin-top:10px;">
            <?php if ( ! empty( $value ) ) : ?>
                <img src="<?php echo esc_url($value); ?>" style="max-height: 80px; border: 1px solid #ddd; padding: 5px; background: white;">
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * New sanitization function for invoice settings.
 */
function wcsl_sanitize_invoice_settings( $input ) {
    $sanitized_input = array();
    
    if ( isset( $input['company_name'] ) ) $sanitized_input['company_name'] = sanitize_text_field( $input['company_name'] );
    if ( isset( $input['company_address'] ) ) $sanitized_input['company_address'] = sanitize_textarea_field( $input['company_address'] );
    if ( isset( $input['company_email'] ) ) $sanitized_input['company_email'] = sanitize_email( $input['company_email'] );
    if ( isset( $input['company_phone'] ) ) $sanitized_input['company_phone'] = sanitize_text_field( $input['company_phone'] );
     if ( isset( $input['invoice_logo'] ) ) {
        // esc_url_raw is good for saving URLs to the database.
        // It ensures the URL is properly formed and safe.
        $sanitized_input['invoice_logo'] = esc_url_raw( $input['invoice_logo'] );
    }
    if ( isset( $input['next_invoice_number'] ) ) $sanitized_input['next_invoice_number'] = intval( $input['next_invoice_number'] );
    if ( isset( $input['currency_symbol'] ) ) $sanitized_input['currency_symbol'] = sanitize_text_field( $input['currency_symbol'] );
    if ( isset( $input['footer_text'] ) ) $sanitized_input['footer_text'] = wp_kses_post( $input['footer_text'] ); // Allow some HTML in footer
    
    return $sanitized_input;
}



/**
 * Displays the HTML for the Import/Export settings section.
 */
function wcsl_import_export_section_cb() {
    echo '<p>' . esc_html__( 'Export your plugin settings to a file, which can then be imported into another site.', 'wp-client-support-ledger' ) . '</p>';
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php esc_html_e( 'Export Settings', 'wp-client-support-ledger' ); ?></th>
                <td>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="wcsl_export_settings" />
                        <?php wp_nonce_field( 'wcsl_export_nonce', 'wcsl_export_nonce_field' ); ?>
                        <?php submit_button( __( 'Export Settings File', 'wp-client-support-ledger' ), 'secondary', 'submit', false ); ?>
                    </form>
                    <p class="description"><?php esc_html_e( 'This will download a .json file containing your notification, appearance, and invoice settings.', 'wp-client-support-ledger' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Import Settings', 'wp-client-support-ledger' ); ?></th>
                <td>
                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="wcsl_import_settings" />
                        <?php wp_nonce_field( 'wcsl_import_nonce', 'wcsl_import_nonce_field' ); ?>
                        <p>
                            <label for="wcsl_import_file"><?php esc_html_e( 'Select a .json file to import:', 'wp-client-support-ledger' ); ?></label>
                            <input type="file" id="wcsl_import_file" name="wcsl_import_file" accept=".json" required/>
                        </p>
                        <?php submit_button( __( 'Import Settings', 'wp-client-support-ledger' ), 'primary', 'submit', false ); ?>
                    </form>
                    <p class="description">
                        <strong style="color: red;"><?php esc_html_e( 'Warning:', 'wp-client-support-ledger' ); ?></strong>
                        <?php esc_html_e( 'Importing settings will overwrite your current settings. This cannot be undone.', 'wp-client-support-ledger' ); ?>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Handles the settings export request.
 */
function wcsl_handle_settings_export() {
    if ( ! isset( $_POST['wcsl_export_nonce_field'] ) || ! wp_verify_nonce( $_POST['wcsl_export_nonce_field'], 'wcsl_export_nonce' ) ) { wp_die( 'Security check failed.' ); }
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'You do not have permission to export settings.' ); }

    $settings_to_export = array(
        'wcsl_email_notification_settings' => get_option('wcsl_email_notification_settings'),
        'wcsl_invoice_settings'            => get_option('wcsl_invoice_settings'),
    );
    $filename = 'wcsl-settings-export-' . date('Y-m-d') . '.json';
    header( 'Content-Type: application/json' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    echo wp_json_encode( $settings_to_export, JSON_PRETTY_PRINT );
    exit;
}
add_action( 'admin_post_wcsl_export_settings', 'wcsl_handle_settings_export' );

/**
 * Handles the settings import request.
 */
function wcsl_handle_settings_import() {
    if ( ! isset( $_POST['wcsl_import_nonce_field'] ) || ! wp_verify_nonce( $_POST['wcsl_import_nonce_field'], 'wcsl_import_nonce' ) ) { wp_die( 'Security check failed.' ); }
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'You do not have permission to import settings.' ); }
    if ( ! isset( $_FILES['wcsl_import_file'] ) || $_FILES['wcsl_import_file']['error'] !== UPLOAD_ERR_OK ) { wp_die( 'File upload error.' ); }

    $file_path = $_FILES['wcsl_import_file']['tmp_name'];
    $file_content = file_get_contents( $file_path );
    $settings = json_decode( $file_content, true );

    if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $settings ) ) { wp_die( 'Invalid JSON file or format.' ); }

    if ( isset( $settings['wcsl_email_notification_settings'] ) ) {
        $sanitized_email = wcsl_sanitize_email_notification_settings( $settings['wcsl_email_notification_settings'] );
        update_option( 'wcsl_email_notification_settings', $sanitized_email );
    }
    if ( isset( $settings['wcsl_invoice_settings'] ) ) {
        $sanitized_invoice = wcsl_sanitize_invoice_settings( $settings['wcsl_invoice_settings'] );
        update_option( 'wcsl_invoice_settings', $sanitized_invoice );
    }

    wp_safe_redirect( admin_url( 'admin.php?page=wcsl-settings-help&wcsl_notice=settings_imported' ) );
    exit;
}
add_action( 'admin_post_wcsl_import_settings', 'wcsl_handle_settings_import' );


/**
 * Callback for the portal settings section description.
 */
function wcsl_portal_settings_section_cb() {
    echo '<p>' . esc_html__( 'Configure the main frontend portal for clients and employees.', 'wp-client-support-ledger' ) . '</p>';
}

/**
 * Callback to render a dropdown list of pages.
 */
function wcsl_field_page_dropdown_cb( $args ) {
    $options = get_option( $args['option_name'] );
    $field_key = $args['field_key'];
    $value = isset( $options[$field_key] ) ? $options[$field_key] : '';

    $pages = get_pages();
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['option_name'] . '[' . $field_key . ']' ); ?>">
        <option value=""><?php esc_html_e( '— Select a Page —', 'wp-client-support-ledger' ); ?></option>
        <?php foreach ( $pages as $page ) : ?>
            <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $value, $page->ID ); ?>>
                <?php echo esc_html( $page->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
    <?php
}


/**
 * Sanitization callback for the new Portal settings.
 */
function wcsl_sanitize_portal_settings( $input ) {
    $sanitized_input = get_option( 'wcsl_portal_settings', array() );

    // Sanitize the Portal Page ID
    if ( isset( $input['portal_page_id'] ) ) {
        $sanitized_input['portal_page_id'] = intval( $input['portal_page_id'] );
    }

    // List all possible color fields
    $color_fields = [
        'emp_main_bg', 'emp_content_bg', 'emp_primary_color', 'emp_accent_color',
        'client_main_bg', 'client_content_bg', 'client_primary_color', 'client_accent_color'
    ];
    foreach ( $color_fields as $key ) {
        if ( isset( $input[$key] ) ) {
            $sanitized_input[$key] = sanitize_hex_color( $input[$key] );
        }
    }

    // *** NEW: Sanitize the radio button options ***
    if ( isset( $input['emp_icon_color'] ) && in_array( $input['emp_icon_color'], ['default', 'white'] ) ) {
        $sanitized_input['emp_icon_color'] = $input['emp_icon_color'];
    } else {
        $sanitized_input['emp_icon_color'] = 'default';
    }

    if ( isset( $input['client_icon_color'] ) && in_array( $input['client_icon_color'], ['default', 'white'] ) ) {
        $sanitized_input['client_icon_color'] = $input['client_icon_color'];
    } else {
        $sanitized_input['client_icon_color'] = 'default';
    }
    
    return $sanitized_input;
}


/**
 * Dynamically generates and outputs the CSS variables for the portal based on saved settings.
 */
function wcsl_output_portal_dynamic_css() {
    $portal_settings = get_option('wcsl_portal_settings');
    $portal_page_id = isset($portal_settings['portal_page_id']) ? (int) $portal_settings['portal_page_id'] : 0;
    if ( ! is_page( $portal_page_id ) || empty( $portal_settings ) ) { return; }

    $user = wp_get_current_user();
    $css_vars = array();
    $scope_class = '';

    if ( in_array( 'wcsl_employee', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) {
        $scope_class = '#wcsl-portal-app-wrapper';
        $css_vars = array(
            '--wcsl-bg-color-main'  => isset($portal_settings['emp_main_bg']) ? sanitize_hex_color($portal_settings['emp_main_bg']) : '#CCD8D6',
            '--wcsl-bg-color-content' => isset($portal_settings['emp_content_bg']) ? sanitize_hex_color($portal_settings['emp_content_bg']) : '#F0F5F3',
            '--wcsl-primary-color'  => isset($portal_settings['emp_primary_color']) ? sanitize_hex_color($portal_settings['emp_primary_color']) : '#3E624D',
            '--wcsl-accent-color'   => isset($portal_settings['emp_accent_color']) ? sanitize_hex_color($portal_settings['emp_accent_color']) : '#D4F838',
            '--wcsl-metric-icon-filter' => (isset($portal_settings['emp_icon_color']) && $portal_settings['emp_icon_color'] === 'white') ? 'brightness(0) invert(1)' : 'none',
        );
    } elseif ( in_array( 'wcsl_client', (array) $user->roles ) ) {
        $scope_class = '#wcsl-portal-app-wrapper';
        $css_vars = array(
            '--wcsl-bg-color-main'  => isset($portal_settings['client_main_bg']) ? sanitize_hex_color($portal_settings['client_main_bg']) : '#CCD8D6',
            '--wcsl-bg-color-content' => isset($portal_settings['client_content_bg']) ? sanitize_hex_color($portal_settings['client_content_bg']) : '#F0F5F3',
            '--wcsl-primary-color'  => isset($portal_settings['client_primary_color']) ? sanitize_hex_color($portal_settings['client_primary_color']) : '#4A90E2',
            '--wcsl-accent-color'   => isset($portal_settings['client_accent_color']) ? sanitize_hex_color($portal_settings['client_accent_color']) : '#7ED321',
            '--wcsl-metric-icon-filter' => (isset($portal_settings['client_icon_color']) && $portal_settings['client_icon_color'] === 'white') ? 'brightness(0) invert(1)' : 'none',
        );
    }

    if ( ! empty( $scope_class ) && ! empty( $css_vars ) ) {
        echo "\n" . '<style type="text/css" id="wcsl-portal-dynamic-styles">' . "\n";
        echo esc_html( $scope_class ) . ' {' . "\n";
        foreach ( $css_vars as $key => $value ) {
            echo "\t" . esc_html( $key ) . ': ' . esc_html( $value ) . ';' . "\n";
        }
        echo '}' . "\n";
        echo '</style>' . "\n";
    }
}
add_action( 'wp_head', 'wcsl_output_portal_dynamic_css' );


