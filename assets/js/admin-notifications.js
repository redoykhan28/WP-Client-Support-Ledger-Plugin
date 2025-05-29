jQuery(document).ready(function($) {
    $('.wcsl-notifications-table').on('click', '.wcsl-notification-action', function(e) {
        e.preventDefault();

        var $link = $(this);
        var action = $link.data('action');
        var notificationId = $link.data('notification-id');
        var nonce = $link.data('nonce');
        var $row = $link.closest('tr');

        if (action === 'delete') {
            if (!confirm(wcsl_ajax_object.delete_confirm_message)) {
                return false;
            }
        }
        
        $link.css('opacity', '0.5').parent().append(' <span class="spinner is-active" style="display:inline-block; float:none;"></span>');

        $.ajax({
            url: wcsl_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'wcsl_manage_notification_ajax', // WordPress AJAX action hook
                wcsl_ajax_action: action, // Our specific sub-action
                notification_id: notificationId,
                _ajax_nonce: nonce // Nonce
            },
            success: function(response) {
                $link.siblings('.spinner').remove();
                $link.css('opacity', '1');

                if (response.success) {
                    // Update UI based on action
                    if (action === 'mark_read') {
                        $row.removeClass('wcsl-notification-unread').addClass('wcsl-notification-read');
                        $row.find('td:last-child').html(response.data.new_status_html); // Update status text
                        $link.parent().html(response.data.new_action_link_html); // Replace action link
                    } else if (action === 'mark_unread') {
                        $row.removeClass('wcsl-notification-read').addClass('wcsl-notification-unread');
                        $row.find('td:last-child').html(response.data.new_status_html);
                        $link.parent().html(response.data.new_action_link_html);
                    } else if (action === 'delete') {
                        $row.fadeOut(300, function() { $(this).remove(); });
                        // Optional: Add a "Notification deleted" message somewhere, or just rely on row removal
                    }
                    // TODO: Update unread count bubble in the menu (more complex, requires another AJAX call or page part refresh)
                    // For now, it will update on next full page load.
                    console.log(response.data.message); // For debugging
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $link.siblings('.spinner').remove();
                $link.css('opacity', '1');
                alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
            }
        });
    });
});