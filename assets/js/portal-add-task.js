function initializeAddTaskForm() {
    var $ = jQuery;
    var $form = $('#wcsl-frontend-add-task-form, #wcsl-frontend-edit-task-form');

    if (!$form.length || $form.data('initialized')) {
        return;
    }
    $form.data('initialized', true);

    // --- Dynamic Task Categories ---
    var $taskTypeSelect = $form.find('#wcsl_task_type');
    var $categoryRow = $form.find('#wcsl_task_category_row');
    var $categorySelect = $form.find('#wcsl_task_category');
    var $spinner = $categoryRow.find('.spinner');

    var fetchTaskCategories = function (primaryType) {
        if (!primaryType) { $categoryRow.hide(); return; }
        $spinner.show(); $categoryRow.show(); $categorySelect.prop('disabled', true).html('<option>Loading...</option>');
        $.ajax({
            url: wcsl_add_task_obj.ajax_url, type: 'POST',
            data: {
                action: 'wcsl_get_task_categories',
                nonce: wcsl_add_task_obj.nonce,
                primary_type: primaryType,
                post_id: wcsl_add_task_obj.post_id || 0
            },
            success: function (response) {
                if (response.success) {
                    $categorySelect.html(response.data.html);
                } else {
                    $categorySelect.html('<option>Error</option>');
                }
            },
            error: function () { $categorySelect.html('<option>Failed</option>'); },
            complete: function () { $spinner.hide(); $categorySelect.prop('disabled', false); }
        });
    };

    $taskTypeSelect.off('change.wcsl').on('change.wcsl', function () { fetchTaskCategories($(this).val()); });
    if ($taskTypeSelect.val()) {
        fetchTaskCategories($taskTypeSelect.val());
    }

    // --- Media Uploader ---
    var taskMediaUploader;
    $form.find('#wcsl_upload_attachment_button').off('click.wcsl').on('click.wcsl', function (e) {
        e.preventDefault();
        if (taskMediaUploader) { taskMediaUploader.open(); return; }
        taskMediaUploader = wp.media({
            title: 'Choose Task Attachment',
            button: { text: 'Choose Attachment' },
            multiple: false
        });
        taskMediaUploader.on('select', function () {
            var attachment = taskMediaUploader.state().get('selection').first().toJSON();
            $form.find('#wcsl_task_attachment_url').val(attachment.url);
            $form.find('#wcsl_attachment_preview').html('<img src="' + attachment.url + '" style="max-width:200px; height:auto; border:1px solid #ddd;" />');
            $form.find('#wcsl_remove_attachment_button').show();
        });
        taskMediaUploader.open();
    });

    $form.find('#wcsl_remove_attachment_button').off('click.wcsl').on('click.wcsl', function (e) {
        e.preventDefault();
        $form.find('#wcsl_task_attachment_url').val('');
        $form.find('#wcsl_attachment_preview').html('');
        $(this).hide();
    });

    // --- *** NEW: AJAX Form Submission for ADD TASK FORM ONLY *** ---
    if ($form.is('#wcsl-frontend-add-task-form')) {
        $form.off('submit.wcsl').on('submit.wcsl', function (e) {
            e.preventDefault();

            var $submitButton = $(this).find('input[type="submit"]');
            var $messages = $('#wcsl-add-task-messages');
            var formData = $(this).serialize();

            // Add nonce and action for our AJAX handler
            formData += '&action=wcsl_frontend_add_task_ajax';
            formData += '&wcsl_add_task_nonce=' + $('#wcsl_add_task_nonce').val();

            $submitButton.prop('disabled', true).val('Submitting...');
            $messages.hide().removeClass('error success').empty();

            $.ajax({
                url: wcsl_add_task_obj.ajax_url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        // Redirect on success
                        window.location.href = response.data.redirect_url;
                    } else {
                        $messages.addClass('error').html(response.data.message).show();
                        $submitButton.prop('disabled', false).val('Submit Task');
                    }
                },
                error: function () {
                    $messages.addClass('error').html('An unexpected network error occurred. Please try again.').show();
                    $submitButton.prop('disabled', false).val('Submit Task');
                }
            });
        });
    }
}