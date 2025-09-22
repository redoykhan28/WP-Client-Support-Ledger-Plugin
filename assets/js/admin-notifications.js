jQuery(document).ready(function($) {
    
    // --- AJAX Handler for SINGLE Notification Row DELETE Action ---
    $('.wcsl-notifications-table').on('click', 'a.wcsl-notification-action.wcsl-delete-notification-ajax', function(e) {
        e.preventDefault(); 

        var $link = $(this);
        var notificationId = $link.data('notification-id');
        var nonce = $link.data('nonce'); // This is 'wcsl_ajax_manage_notification_' + id
        var $row = $link.closest('tr');

        // Use the localized string for confirmation
        var confirmMessage = (typeof wcsl_admin_strings !== 'undefined' && wcsl_admin_strings.delete_confirm_message) 
                             ? wcsl_admin_strings.delete_confirm_message 
                             : 'Are you sure you want to delete this notification?';
        if (!confirm(confirmMessage)) { 
            return false;
        }
        
        $link.hide(); 
        var $spinner = $('<span class="spinner is-active" style="display:inline-block; float:none; vertical-align:middle; margin-left:5px;"></span>');
        $link.parent().append($spinner); 

        $.ajax({
            url: (typeof wcsl_admin_strings !== 'undefined' ? wcsl_admin_strings.ajax_url : ajaxurl), // Fallback to global ajaxurl
            type: 'POST',
            data: {
                action: 'wcsl_manage_notification_ajax', 
                wcsl_ajax_action: 'delete',             
                notification_id: notificationId,
                _ajax_nonce: nonce                     
            },
            success: function(response) {
                $spinner.remove(); 
                if (response.success) {
                    $row.css('background-color', '#ffbaba'); 
                    $row.fadeOut(400, function() { 
                        $(this).remove(); 
                        var noNotifMsg = (typeof wcsl_admin_strings !== 'undefined' && wcsl_admin_strings.no_notifications_found)
                                         ? wcsl_admin_strings.no_notifications_found
                                         : 'No notifications found.';
                        if ($('#the-list tr:not(.no-items)').length === 0 && $('#the-list tr.no-items').length === 0) {
                            $('#the-list').append('<tr class="no-items"><td class="colspanchange" colspan="5">' + noNotifMsg + '</td></tr>');
                        }
                        // Unread count badge will update on next full page load.
                    });
                    // console.log('WCSL AJAX Delete Success:', response.data.message);
                } else {
                    $link.show(); 
                    alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error occurred.'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $spinner.remove();
                $link.show(); 
                alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
            }
        });
    });

    // --- Bulk Action Checkbox Handling ---
    var $notificationForm = $('#wcsl-notifications-form');
    if ($notificationForm.length) {
        var $selectAllTop = $notificationForm.find('#cb-select-all-1');
        var $selectAllBottom = $notificationForm.find('#cb-select-all-2');
        var $itemCheckboxes = $notificationForm.find('#the-list input[type="checkbox"][name="notification_ids[]"]');

        $selectAllTop.on('click', function() { /* ... as before ... */ });
        $selectAllBottom.on('click', function() { /* ... as before ... */ });
        $itemCheckboxes.on('click', function() { /* ... as before ... */ });

        $('#doaction, #doaction2', $notificationForm).on('click', function(e){
            var bulkAction = $(this).closest('.bulkactions').find('select[name^="action"]').val();
            
            var noSelectedMsgBulk = (typeof wcsl_admin_strings !== 'undefined' && wcsl_admin_strings.no_notifications_selected)
                                 ? wcsl_admin_strings.no_notifications_selected
                                 : 'Please select one or more notifications to perform a bulk action.';
            var bulkDeleteConfirmMsgJs = (typeof wcsl_admin_strings !== 'undefined' && wcsl_admin_strings.bulk_delete_confirm_message)
                                 ? wcsl_admin_strings.bulk_delete_confirm_message
                                 : 'Are you sure you want to delete these selected notifications? This action cannot be undone.';

            if ($itemCheckboxes.filter(':checked').length === 0 && bulkAction !== '-1' && bulkAction !== '') {
                alert(noSelectedMsgBulk);
                e.preventDefault(); 
                return;
            }

            if (bulkAction === 'bulk_delete') {
                if (!confirm(bulkDeleteConfirmMsgJs)) {
                    e.preventDefault();
                }
            }
        });
    }
});