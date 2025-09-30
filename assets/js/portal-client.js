jQuery(document).ready(function ($) {

    // --- NEW: Reusable Confirmation Modal Function ---
function showConfirmationModal(onConfirmCallback) {
    var $modalOverlay = $('#wcsl-confirmation-modal-overlay');
    var $confirmBtn = $modalOverlay.find('.wcsl-modal-confirm');
    var $cancelBtn = $modalOverlay.find('.wcsl-modal-cancel');

    $modalOverlay.show().addClass('is-visible');

    $confirmBtn.off('click').one('click', function() {
        if (typeof onConfirmCallback === 'function') {
            onConfirmCallback();
        }
        $modalOverlay.removeClass('is-visible').hide();
    });

    $cancelBtn.off('click').one('click', function() {
        $modalOverlay.removeClass('is-visible').hide();
    });
}


    /**
     * *** NEW FUNCTION ***
     * Updates the notification badge in the sidebar menu.
     * @param {int} count - The new number of unread notifications.
     */
    function updateNotificationBadge(count) {
        var $badge = $('.wcsl-notification-badge');
        if (count > 0) {
            if ($badge.length) {
                $badge.text(count); // Update existing badge
            } else {
                // Create badge if it doesn't exist
                var $notificationLink = $('.wcsl-portal-sidebar a[href*="wcsl_view=notifications"]');
                $notificationLink.append(' <span class="wcsl-notification-badge">' + count + '</span>');
            }
        } else {
            $badge.remove(); // Remove badge if count is zero
        }
    }

    // Use event delegation on the main content container for AJAX-loaded content
  $('#wcsl-portal-main-content').on('click', '.notification-actions a', function (e) {
    e.preventDefault();

    if ($(this).hasClass('processing')) { return; }
    $(this).addClass('processing');

    var $link = $(this);
    var href = $link.attr('href');
    var $row = $link.closest('tr');
    var urlParams = new URLSearchParams(href.split('?')[1]);
    var notificationId = urlParams.get('notification_id');
    var nonce = urlParams.get('_wcsl_nonce');
    var action = urlParams.get('wcsl_action');

    if (!notificationId || !nonce || !action) {
        alert('An error occurred. Missing required parameters.');
        $link.removeClass('processing');
        return;
    }

    var ajaxCall = function() {
        var $spinner = $('<span class="spinner is-active" style="display:inline-block; float:none; vertical-align:middle; margin-left:5px;"></span>');
        $link.parent().append($spinner);
        $link.parent().find('a, .action-divider').css('visibility', 'hidden');

        $.ajax({
            url: wcsl_client_portal_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'wcsl_manage_client_notification_ajax',
                notification_id: notificationId,
                _ajax_nonce: nonce,
                wcsl_action: action
            },
            success: function (response) {
                if (response.success) {
                    if (typeof response.data.new_count !== 'undefined') {
                        updateNotificationBadge(response.data.new_count);
                    }
                    if (action === 'delete') {
                        $row.css('background-color', '#ffbaba').fadeOut(400, function () {
                            $(this).remove();
                            if ($('#wcsl-notifications-table tbody tr').length === 0) {
                                $('#wcsl-notifications-table').closest('.wcsl-data-table-wrapper').html('<p class="wcsl-panel-notice">You have no notifications.</p>');
                            }
                        });
                    } else {
                        $row.replaceWith(response.data.html);
                    }
                } else {
                    alert('Error: ' + (response.data.message || 'An unknown error occurred.'));
                    $spinner.remove();
                    $link.parent().find('a, .action-divider').css('visibility', 'visible');
                    $link.removeClass('processing');
                }
            },
            error: function () {
                alert('A network error occurred. Please try again.');
                $spinner.remove();
                $link.parent().find('a, .action-divider').css('visibility', 'visible');
                $link.removeClass('processing');
            }
        });
    };

    if (action === 'delete') {
        // --- MODIFIED: Use the new modal instead of confirm() ---
        showConfirmationModal(ajaxCall);
        // We need to remove the processing class here since the call is now async
        $link.removeClass('processing');
    } else {
        // For other actions, run the AJAX call directly
        ajaxCall();
    }
});
});