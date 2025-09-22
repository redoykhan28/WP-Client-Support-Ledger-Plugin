jQuery(document).ready(function($) {
    // --- Initialize WordPress Color Pickers ---
    if (typeof $.fn.wpColorPicker === 'function') {
        $('.wcsl-color-picker-field').wpColorPicker();
    } else {
        console.warn('WCSL: WordPress Color Picker script not loaded or is not a function.');
    }

    // --- CODE FOR TASK EDIT SCREEN ---
    var $postForm = $('#post');
    if ($postForm.length && $('body').hasClass('post-type-client_task')) {
        $('#title').prop('required', true);
        $('label[for="title"]').append(' <span class="required" style="color:red;">*</span>');

        // --- DYNAMIC TASK CATEGORIES ---
        var $taskTypeSelect = $('#wcsl_task_type');
        if ($taskTypeSelect.length) {
            var $categoryRow = $('#wcsl_task_category_row');
            var $categorySelect = $('#wcsl_task_category');
            var $spinner = $categoryRow.find('.spinner');

            var fetchTaskCategories = function(primaryType, callback) { // Added a callback
                if (!primaryType) {
                    $categoryRow.hide();
                    return;
                }
                $spinner.css('display', 'inline-block');
                $categoryRow.show();
                $categorySelect.prop('disabled', true).html('<option value="">Loading...</option>');

                $.ajax({
                    url: wcsl_task_edit_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wcsl_get_task_categories',
                        nonce: wcsl_task_edit_obj.nonce,
                        primary_type: primaryType,
                        post_id: wcsl_task_edit_obj.post_id
                    },
                    success: function(response) {
                        $spinner.hide();
                        $categorySelect.prop('disabled', false);
                        if (response.success) {
                            $categorySelect.html(response.data.html);
                            if (typeof callback === 'function') {
                                callback(); // Execute the callback if provided
                            }
                        } else {
                            $categorySelect.html('<option value="">Error loading</option>');
                        }
                    },
                    error: function() {
                        $spinner.hide();
                        $categorySelect.prop('disabled', false);
                        $categorySelect.html('<option value="">Request failed</option>');
                    }
                });
            }
            
            $taskTypeSelect.on('change', function() {
                fetchTaskCategories($(this).val());
            });
            
            // On page load, if a type is already selected, trigger the fetch
            if ($taskTypeSelect.val()) {
                fetchTaskCategories($taskTypeSelect.val());
            }
        }
    }

    // Media Uploader Logic for Task Attachments
    if ($('#wcsl_upload_attachment_button').length) {
        var taskMediaUploader;
        $('#wcsl_upload_attachment_button').on('click', function(e) {
            e.preventDefault();
            if (taskMediaUploader) { taskMediaUploader.open(); return; }
            taskMediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Task Attachment', button: { text: 'Choose Attachment' },
                library: { type: 'image' }, multiple: false
            });
            taskMediaUploader.on('select', function() {
                var attachment = taskMediaUploader.state().get('selection').first().toJSON();
                $('#wcsl_task_attachment_url').val(attachment.url);
                $('#wcsl_attachment_preview').html('<img src="' + attachment.url + '" style="max-width:200px; height:auto; border:1px solid #ddd;" />');
                $('#wcsl_remove_attachment_button').show();
            });
            taskMediaUploader.open();
        });
        $('#wcsl_remove_attachment_button').on('click', function(e) {
            e.preventDefault();
            $('#wcsl_task_attachment_url').val('');
            $('#wcsl_attachment_preview').html('');
            $(this).hide();
        });
    }

    // --- CODE FOR INVOICE LOGO UPLOADER ON SETTINGS PAGE ---
    if ($('#wcsl_upload_invoice_logo_button').length) {
        var logoMediaUploader;
        $('#wcsl_upload_invoice_logo_button').on('click', function(e) {
            e.preventDefault();
            if (logoMediaUploader) {
                logoMediaUploader.open();
                return;
            }
            logoMediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Invoice Logo',
                button: { text: 'Choose Logo' },
                library: { type: 'image' },
                multiple: false
            });
            logoMediaUploader.on('select', function() {
                var attachment = logoMediaUploader.state().get('selection').first().toJSON();
                $('#wcsl_invoice_logo_id').val(attachment.url); 
                $('#wcsl_invoice_logo_preview').html('<img src="' + attachment.url + '" style="max-height: 80px; border: 1px solid #ddd; padding: 5px; background: white;">');
                $('#wcsl_remove_invoice_logo_button').show();
            });
            logoMediaUploader.open();
        });
        $('#wcsl_remove_invoice_logo_button').on('click', function(e) {
            e.preventDefault();
            $('#wcsl_invoice_logo_id').val('');
            $('#wcsl_invoice_logo_preview').html('');
            $(this).hide();
        });
    }
});