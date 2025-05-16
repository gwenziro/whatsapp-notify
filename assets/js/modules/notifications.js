/**
 * WhatsApp Notify - Notifications Module
 * Menangani tampilan notifikasi untuk user
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * Notifications Module
     */
    WANotify.Notifications = {
        /**
         * Initialize notifications module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initEvents();
            console.log('Notifications module initialized');
        },
        
        /**
         * Initialize notification events
         */
        initEvents: function() {
            // Close notification
            $(document).on("click", ".wanotify-notification-close", function() {
                $(this)
                    .closest(".wanotify-notification")
                    .fadeOut(300, function() {
                        $(this).remove();
                    });
            });
        },
        
        /**
         * Show notification
         * @param {string} type Type of notification (success, error, info, warning)
         * @param {string} message Message to display
         * @param {number} duration Duration in ms (default: 5000, 0 for no auto-hide)
         */
        showNotification: function(type, message, duration = 5000) {
            // Determine the icon based on notification type
            let icon;
            switch (type) {
                case 'success':
                    icon = 'dashicons-yes-alt';
                    break;
                case 'error':
                    icon = 'dashicons-warning';
                    break;
                case 'info':
                    icon = 'dashicons-info';
                    break;
                case 'warning':
                    icon = 'dashicons-warning';
                    break;
                default:
                    icon = 'dashicons-info';
            }
            
            // Create the notification HTML
            const notificationHtml = `
                <div class="wanotify-notification wanotify-${type}">
                    <div class="wanotify-notification-icon">
                        <span class="dashicons ${icon}"></span>
                    </div>
                    <div class="wanotify-notification-message">${message}</div>
                    <div class="wanotify-notification-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </div>
                </div>
            `;
            
            // Add to notification area
            const $notification = $(notificationHtml).appendTo("#wanotify-notification-area");
            
            // Auto-hide after specified duration (if not 0)
            if (duration > 0) {
                setTimeout(function() {
                    $notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, duration);
            }
            
            return $notification;
        }
    };
    
})(jQuery);
