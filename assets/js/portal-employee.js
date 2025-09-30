jQuery(document).ready(function ($) {
    var mainContentContainer = '#wcsl-portal-main-content';
    var portalWrapper = '.wcsl-portal-wrapper';
    var currentRequest = null;
    var $globalLoader = $('.wcsl-global-loader'); // Cache the global loader element


    // --- NEW: Reusable Confirmation Modal Function ---
    function showConfirmationModal(onConfirmCallback) {
    var $modalOverlay = $('#wcsl-confirmation-modal-overlay');
    var $confirmBtn = $modalOverlay.find('.wcsl-modal-confirm');
    var $cancelBtn = $modalOverlay.find('.wcsl-modal-cancel');

    // Show the modal
    $modalOverlay.show().addClass('is-visible');

    // Handle the CONFIRM action
    $confirmBtn.off('click').one('click', function() {
        if (typeof onConfirmCallback === 'function') {
            onConfirmCallback();
        }
        $modalOverlay.removeClass('is-visible').hide();
    });

    // Handle the CANCEL action
    $cancelBtn.off('click').one('click', function() {
        $modalOverlay.removeClass('is-visible').hide();
    });
    }


    /**
     * ===================================================================
     * Core AJAX Content Loading Function
     * ===================================================================
     */
    function loadContent(url, isPopState, targetContainer) {
        if (currentRequest) { currentRequest.abort(); }

        var $container = $(targetContainer);

        // --- LOADER LOGIC ---
        if (targetContainer === mainContentContainer) {
            $globalLoader.addClass('is-active');
        } else {
            $container.addClass('wcsl-loading');
        }

        currentRequest = $.ajax({
            url: wcsl_employee_portal_obj.ajax_url, type: 'POST',
            data: { action: 'wcsl_load_employee_portal_content', nonce: wcsl_employee_portal_obj.nonce, requested_url: url },
            success: function (response) {
                if (response.success) {
                    $container.html(response.data.html);
                    updatePageTitle(url); // Set title after content loads

                    // --- THIS IS THE FIX ---
                    // Re-initialize the Add/Edit Task form scripts if the form is now on the page.
                    if (typeof initializeAddTaskForm === 'function') { initializeAddTaskForm(); }
                    
                    // Re-initialize the Reports scripts if the charts are now on the page.
                    if (typeof initializePortalReports === 'function') { initializePortalReports(response.data.scripts_data); }
                    
                    if (!isPopState && targetContainer === mainContentContainer) { history.pushState({ path: url }, '', url); }
                    if (targetContainer === mainContentContainer) { updateSidebarMenu(url); }
                } else { $container.html('<p class="wcsl-panel-notice error">Error: ' + (response.data.message || 'Could not load content.') + '</p>'); }
            },
            error: function (jqXHR, textStatus) { if (textStatus !== 'abort') { $container.html('<p class="wcsl-panel-notice error">An unexpected error occurred. Please try again.</p>'); } },
            complete: function () {
                // --- Hide the correct loader ---
                if (targetContainer === mainContentContainer) {
                    $globalLoader.removeClass('is-active');
                } else {
                    $container.removeClass('wcsl-loading');
                }
            }
        });
    }

    /**
     * Updates the main page title in the header based on the current view.
     */
    function updatePageTitle(url) {
        var $titleContainer = $('#wcsl-dynamic-page-title');
        if (!$titleContainer.length) return;

        var fullUrl = new URL(url);
        var urlParams = new URLSearchParams(fullUrl.search);

        var view = urlParams.get('wcsl_view') || 'dashboard';
        var titleText = 'Dashboard'; // Default title

        if (view === 'dashboard') {
            var date = new Date();
            var month = date.toLocaleString('default', { month: 'long' });
            var year = date.getFullYear();
            titleText = 'Dashboard for ' + month + ' ' + year;
        } else if (view === 'month_details') {
            titleText = '';
        } else if (view === 'add_task') {
            titleText = 'Add New Task';
        } else if (view === 'all_tasks') {
            titleText = 'All Tasks';
        } else if (view === 'my_tasks') {
            titleText = 'My Tasks';
        } else if (view === 'edit_task') {
            titleText = 'Edit Task';
        } else if (view === 'reports') {
            titleText = 'Reports';
        } else if (view === 'notifications') {
            titleText = 'Notifications';
        }

        if (titleText) {
            $titleContainer.html('<h2 class="wcsl-page-title">' + titleText + '</h2>');
        } else {
            $titleContainer.html('');
        }
    }

    /**
     * Updates the active state of the sidebar menu.
     */
    function updateSidebarMenu(url) {
        var view = 'dashboard';
        var fullUrl = new URL(url);
        var urlParams = new URLSearchParams(fullUrl.search);
        if (urlParams.has('wcsl_view')) { view = urlParams.get('wcsl_view'); }
        $('.wcsl-portal-sidebar li').removeClass('active');
        var $activeLink;
        if (view === 'dashboard' || view === 'month_details') {
            $activeLink = $('.wcsl-portal-sidebar a[href*="wcsl_view=dashboard"]');
        } else if (view === 'reports') {
            $activeLink = $('.wcsl-portal-sidebar a[href*="wcsl_view=reports"]');
        } else if (view === 'notifications' || view === 'notifications_table') {
            $activeLink = $('.wcsl-portal-sidebar a[href*="wcsl_view=notifications"]');
        } else if (view === 'edit_task') {
            $activeLink = $('.wcsl-portal-sidebar a[href*="wcsl_view=my_tasks"]');
        } else { $activeLink = $('.wcsl-portal-sidebar a[href*="wcsl_view=' + view + '"]'); }
        if ($activeLink.length) {
            $activeLink.parent('li').addClass('active');
        } else if (view === 'dashboard') { $('.wcsl-portal-sidebar a').not('[href*="wcsl_view"]').parent('li').addClass('active'); }
    }

    /**
     * Updates the notification badge in the sidebar menu.
     */
    function updateNotificationBadge(count) {
        var $badge = $('.wcsl-notification-badge');
        if (count > 0) {
            if ($badge.length) { $badge.text(count); } else {
                var $notificationLink = $('.wcsl-portal-sidebar a[href*="wcsl_view=notifications"]');
                $notificationLink.append(' <span class="wcsl-notification-badge">' + count + '</span>');
            }
        } else { $badge.remove(); }
    }

    // --- EVENT LISTENERS ---

    // 1. Main navigation
    $(portalWrapper).on('click', 'a.wcsl-ajax-load-main', function (e) {
        e.preventDefault();
        var targetUrl = $(this).attr('href');
        if (targetUrl && targetUrl !== '#') {
            loadContent(targetUrl, false, mainContentContainer);
        }
    });

    // 2. Partial form submissions (Search, Filter on task lists)
    $(portalWrapper).on('submit', 'form.wcsl-ajax-form:not(#wcsl-employee-notifications-form)', function (e) {
        e.preventDefault();
        var $form = $(this);
        var targetContainer = $form.data('target');
        var targetUrl = location.protocol + '//' + location.host + location.pathname + '?' + $form.serialize();
        if (targetContainer) {
            loadContent(targetUrl, false, targetContainer);
        }
    });

    // 3. Main form submissions (Reports filter)
    $(portalWrapper).on('submit', 'form.wcsl-reports-filter-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var targetUrl = location.protocol + '//' + location.host + location.pathname + '?' + $form.serialize();
        loadContent(targetUrl, false, mainContentContainer);
    });

    // 4. Partial pagination
    $(portalWrapper).on('click', '.wcsl-ajax-pagination a', function (e) {
        e.preventDefault();
        var targetUrl = $(this).attr('href');
        var targetContainer = $(this).closest('.wcsl-ajax-container').attr('id');
        if (targetUrl && targetUrl !== '#' && targetContainer) {
            loadContent(targetUrl, false, '#' + targetContainer);
        }
    });

    // 5. "My Tasks" DELETE action
$(portalWrapper).on('click', '.wcsl-my-tasks-wrap .wcsl-action-link.delete', function (e) {
    e.preventDefault();
    var $link = $(this), $row = $link.closest('tr'), taskId = $link.data('task-id'), nonce = $link.data('nonce');
    
    // --- MODIFIED: Replaced confirm() with our new modal ---
    showConfirmationModal(function() {
        // This entire AJAX call is the "callback" that runs only on confirm.
        $row.css('opacity', '0.5'); $link.text('Deleting...');
        $.ajax({
            url: wcsl_employee_portal_obj.ajax_url, type: 'POST',
            data: { action: 'wcsl_employee_delete_task', task_id: taskId, nonce: nonce },
            success: function (response) {
                if (response.success) {
                    Toastify({ text: 'Task successfully deleted.', duration: 3000, gravity: 'top', position: 'right', className: 'wcsl-toast-success' }).showToast();
                    $row.css('background-color', '#ffbaba').fadeOut(400, function () { $(this).remove(); if ($('.wcsl-my-tasks-wrap tbody tr').length === 0) { $('#my-tasks-ajax-content').html('<div class="wcsl-portal-section"><p class="wcsl-panel-notice">You do not have any tasks assigned to you.</p></div>'); } });
                } else {
                    Toastify({ text: 'Error: ' + (response.data.message || 'Could not delete task.'), duration: 5000, gravity: 'top', position: 'right', className: 'wcsl-toast-error' }).showToast();
                    $row.css('opacity', '1'); $link.text('Delete');
                }
            },
            error: function () {
                Toastify({ text: 'An unexpected network error occurred.', duration: 5000, gravity: 'top', position: 'right', className: 'wcsl-toast-error' }).showToast();
                $row.css('opacity', '1'); $link.text('Delete');
            }
        });
    });
});

    // 6. Browser Back/Forward buttons
    $(window).on('popstate', function (e) {
        if (e.originalEvent.state && e.originalEvent.state.path) {
            loadContent(e.originalEvent.state.path, true, mainContentContainer);
        }
    });

    // *** NOTIFICATION MODULE LISTENERS ***

    // 7. Single notification action (Mark Read/Unread, Delete)
    $(portalWrapper).on('click', 'a.wcsl-notification-action', function (e) {
    e.preventDefault();
    var $link = $(this), $row = $link.closest('tr'), notifId = $link.data('id'), actionType = $link.data('action'), nonce = $link.data('nonce');
    
    var ajaxCall = function() {
        $row.css('opacity', 0.5);
        $.ajax({
            url: wcsl_employee_portal_obj.ajax_url, type: 'POST',
            data: { action: 'wcsl_employee_manage_notification', notification_id: notifId, wcsl_action: actionType, nonce: nonce },
            success: function (response) {
                if (response.success) {
                    var newUrl = new URL(location.href);
                    newUrl.searchParams.set('wcsl_view', 'notifications_table');
                    newUrl.searchParams.delete('paged');
                    loadContent(newUrl.href, true, '#employee-notifications-ajax-content');
                    updateNotificationBadge(response.data.new_count);
                } else {
                    Toastify({ text: response.data.message || 'An error occurred.', duration: 5000, gravity: 'top', position: 'right', className: 'wcsl-toast-error' }).showToast();
                    $row.css('opacity', 1);
                }
            },
            error: function () {
                Toastify({ text: 'A network error occurred.', duration: 5000, gravity: 'top', position: 'right', className: 'wcsl-toast-error' }).showToast();
                $row.css('opacity', 1);
            }
        });
    };

    if (actionType === 'delete') {
        // --- MODIFIED: Use the modal only for the delete action ---
        showConfirmationModal(ajaxCall);
    } else {
        // For other actions, run the AJAX call directly
        ajaxCall();
    }
});

    // 8. Bulk action form submission
    $(portalWrapper).on('submit', '#wcsl-employee-notifications-form', function (e) {
    e.preventDefault();
    var $form = $(this), $container = $form.closest('.wcsl-ajax-container'), bulkAction = $form.find('.wcsl-bulk-action-select').val(), checkedIds = $form.find('.wcsl-item-checkbox:checked').map(function () { return this.value; }).get(), paged = $form.find('input[name="paged"]').val() || 1;
    if (bulkAction === '-1' || checkedIds.length === 0) {
        Toastify({ text: 'Please select an action and at least one notification.', duration: 5000, gravity: 'top', position: 'right', className: 'wcsl-toast-info' }).showToast();
        return;
    }

    var ajaxCall = function() {
        $container.addClass('wcsl-loading');
        $.ajax({
            url: wcsl_employee_portal_obj.ajax_url, type: 'POST',
            data: { action: 'wcsl_employee_bulk_notifications', nonce: wcsl_employee_portal_obj.nonce, bulk_action: bulkAction, notification_ids: checkedIds, paged: paged },
            success: function (response) {
                if (response.success) {
                    $container.html(response.data.html);
                    updateNotificationBadge(response.data.new_count);
                    Toastify({ text: 'Bulk action completed.', duration: 3000, gravity: 'top', position: 'right', className: 'wcsl-toast-success' }).showToast();
                } else {
                    Toastify({ text: response.data.message || 'An error occurred.', duration: 5000, gravity: 'top', position: 'right', className: 'wcsl-toast-error' }).showToast();
                }
            },
            error: function () {
                Toastify({ text: 'A network error occurred.', duration: 5000, gravity: 'top', position: 'right', className: 'wcsl-toast-error' }).showToast();
            },
            complete: function () { $container.removeClass('wcsl-loading'); }
        });
    };
    
    if (bulkAction === 'bulk_delete') {
         // --- MODIFIED: Use the modal only for the bulk delete action ---
        showConfirmationModal(ajaxCall);
    } else {
        ajaxCall();
    }
});
    // 9. Checkbox "Select All" functionality
    $(portalWrapper).on('change', '.wcsl-select-all-checkbox', function () {
        var $this = $(this);
        var $table = $this.closest('table');
        $table.find('.wcsl-item-checkbox').prop('checked', $this.prop('checked'));
    });

    // Initial page title load
    updatePageTitle(location.href);
});