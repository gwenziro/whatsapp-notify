/**
 * Fluent WhatsApp Notifier - Admin JavaScript
 */
(function($) {
    'use strict';
    
    // DOM Ready
    $(function() {
        // Show/hide dynamic recipient field based on checkbox
        toggleDynamicRecipient();
        
        // Toggle field list based on "include all fields" checkbox
        toggleFieldList();
        
        // Initialize event listeners
        initEventListeners();
        
        // Initialize form handlers
        initFormHandlers();
    });
    
    /**
     * Show/hide dynamic recipient field based on checkbox state
     */
    function toggleDynamicRecipient() {
        const isDynamicRecipient = $('#dynamic_recipient').is(':checked');
        
        if (isDynamicRecipient) {
            $('#recipient_field_row').show();
        } else {
            $('#recipient_field_row').hide();
        }
    }
    
    /**
     * Toggle field list based on "include all fields" checkbox
     */
    function toggleFieldList() {
        const includeAllFields = $('#include_all_fields').is(':checked');
        
        if (includeAllFields) {
            $('#field_list').hide();
        } else {
            $('#field_list').show();
        }
    }
    
    /**
     * Initialize event listeners
     */
    function initEventListeners() {
        // Show/hide dynamic recipient field when checkbox changes
        $('#dynamic_recipient').on('change', toggleDynamicRecipient);
        
        // Toggle field list when "include all fields" changes
        $('#include_all_fields').on('change', function() {
            toggleFieldList();
            
            // Check/uncheck all fields when "include all fields" is checked
            if ($(this).is(':checked')) {
                $('#field_list input[type="checkbox"]').prop('checked', true);
            }
        });
        
        // Test connection button
        $('#fluentwa-test-connection').on('click', testConnection);
        
        // Test form notification button
        $('#fluentwa-test-form-notification').on('click', testFormNotification);
        
        // Clear logs button
        $('#fluentwa-clear-logs').on('click', clearLogs);
        
        // Close notification
        $(document).on('click', '.fluentwa-notification-close', function() {
            $(this).closest('.fluentwa-notification').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Initialize form handlers
     */
    function initFormHandlers() {
        // General settings form
        $('#fluentwa-general-settings').on('submit', function(e) {
            e.preventDefault();
            saveGeneralSettings($(this));
        });
        
        // Form settings
        $('#fluentwa-form-settings').on('submit', function(e) {
            e.preventDefault();
            saveFormSettings($(this));
        });
    }
    
    /**
     * Save general settings via AJAX
     */
    function saveGeneralSettings($form) {
        const btnText = $form.find('.fluentwa-submit-btn').text();
        $form.find('.fluentwa-submit-btn').text(fluentWA.i18n.saving).prop('disabled', true);
        
        const data = {
            action: 'fluentwa_save_settings',
            nonce: fluentWA.nonce,
            api_url: $form.find('#api_url').val(),
            default_recipient: $form.find('#default_recipient').val(),
            default_template: $form.find('#default_template').val(),
            enable_logging: $form.find('#enable_logging').is(':checked')
        };
        
        $.post(fluentWA.ajax_url, data, function(response) {
            if (response.success) {
                showNotification('success', response.data.message);
            } else {
                showNotification('error', response.data.message);
            }
            
            $form.find('.fluentwa-submit-btn').text(btnText).prop('disabled', false);
        }).fail(function() {
            showNotification('error', 'Terjadi kesalahan server. Silakan coba lagi.');
            $form.find('.fluentwa-submit-btn').text(btnText).prop('disabled', false);
        });
    }
    
    /**
     * Save form settings via AJAX
     */
    function saveFormSettings($form) {
        const formId = $form.data('form-id');
        const btnText = $form.find('.fluentwa-submit-btn').text();
        
        $form.find('.fluentwa-submit-btn').text(fluentWA.i18n.saving).prop('disabled', true);
        
        // Prepare form data
        const formData = {
            action: 'fluentwa_save_form_settings',
            nonce: fluentWA.nonce,
            form_id: formId,
            enabled: $form.find('#enabled').is(':checked'),
            recipient: $form.find('#recipient').val(),
            dynamic_recipient: $form.find('#dynamic_recipient').is(':checked'),
            recipient_field: $form.find('#recipient_field').val(),
            message_template: $form.find('#message_template').val()
        };
        
        // Handle included fields
        if ($form.find('#include_all_fields').is(':checked')) {
            formData.included_fields = ['*']; // All fields
        } else {
            formData.included_fields = [];
            $form.find('input[name="included_fields[]"]:checked').each(function() {
                formData.included_fields.push($(this).val());
            });
        }
        
        // Send AJAX request
        $.post(fluentWA.ajax_url, formData, function(response) {
            if (response.success) {
                showNotification('success', response.data.message);
            } else {
                showNotification('error', response.data.message);
            }
            
            $form.find('.fluentwa-submit-btn').text(btnText).prop('disabled', false);
        }).fail(function() {
            showNotification('error', 'Terjadi kesalahan server. Silakan coba lagi.');
            $form.find('.fluentwa-submit-btn').text(btnText).prop('disabled', false);
        });
    }
    
    /**
     * Test WhatsApp connection
     */
    function testConnection() {
        const $btn = $('#fluentwa-test-connection');
        const btnText = $btn.text();
        
        $btn.text(fluentWA.i18n.testing).prop('disabled', true);
        
        const data = {
            action: 'fluentwa_test_connection',
            nonce: fluentWA.nonce
        };
        
        $.post(fluentWA.ajax_url, data, function(response) {
            if (response.success) {
                showNotification('success', response.data.message);
            } else {
                showNotification('error', response.data.message);
            }
            
            $btn.text(btnText).prop('disabled', false);
        }).fail(function() {
            showNotification('error', 'Terjadi kesalahan server. Silakan coba lagi.');
            $btn.text(btnText).prop('disabled', false);
        });
    }
    
    /**
     * Test form notification
     */
    function testFormNotification() {
        const formId = $('#fluentwa-form-settings').data('form-id');
        const $btn = $('#fluentwa-test-form-notification');
        const btnText = $btn.text();
        
        $btn.text(fluentWA.i18n.testing).prop('disabled', true);
        
        const data = {
            action: 'fluentwa_test_form_notification',
            nonce: fluentWA.nonce,
            form_id: formId
        };
        
        $.post(fluentWA.ajax_url, data, function(response) {
            if (response.success) {
                showNotification('success', response.data.message);
            } else {
                showNotification('error', response.data.message);
            }
            
            $btn.text(btnText).prop('disabled', false);
        }).fail(function() {
            showNotification('error', 'Terjadi kesalahan server. Silakan coba lagi.');
            $btn.text(btnText).prop('disabled', false);
        });
    }
    
    /**
     * Clear logs
     */
    function clearLogs() {
        if (!confirm(fluentWA.i18n.confirm_clear_logs)) {
            return;
        }
        
        const $btn = $('#fluentwa-clear-logs');
        const btnText = $btn.text();
        
        $btn.text('Menghapus...').prop('disabled', true);
        
        const data = {
            action: 'fluentwa_clear_logs',
            nonce: fluentWA.nonce
        };
        
        $.post(fluentWA.ajax_url, data, function(response) {
            if (response.success) {
                showNotification('success', response.data.message);
                $('#fluentwa-logs-container').html('<p>Log telah dibersihkan.</p>');
            } else {
                showNotification('error', response.data.message);
            }
            
            $btn.text(btnText).prop('disabled', false);
        }).fail(function() {
            showNotification('error', 'Terjadi kesalahan server. Silakan coba lagi.');
            $btn.text(btnText).prop('disabled', false);
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(type, message) {
        const icon = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
        const notificationHtml = `
            <div class="fluentwa-notification fluentwa-${type}">
                <div class="fluentwa-notification-icon">
                    <span class="dashicons ${icon}"></span>
                </div>
                <div class="fluentwa-notification-message">${message}</div>
                <div class="fluentwa-notification-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
        `;
        
        $('#fluentwa-notification-area').append(notificationHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.fluentwa-notification').first().fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Fungsi untuk menangani perubahan mode penerima
jQuery(document).ready(function($) {
    // Toggle recipient mode
    $('.recipient-mode-selector').on('change', function() {
        var selectedMode = $('input[name="recipient_mode"]:checked').val();
        
        // Sembunyikan semua pengaturan
        $('.recipient-mode-settings').hide();
        
        // Tampilkan pengaturan untuk mode yang dipilih
        if (selectedMode === 'manual') {
            $('.recipient-manual-settings').show();
        } else if (selectedMode === 'dynamic') {
            $('.recipient-dynamic-settings').show();
        }
    });
});

// Fungsi untuk menangani perubahan mode penerima
$('.recipient-mode-selector').on('change', function() {
    var selectedMode = $('input[name="recipient_mode"]:checked').val();
    
    // Sembunyikan semua pengaturan
    $('.recipient-mode-settings').hide();
    
    // Tampilkan pengaturan untuk mode yang dipilih
    if (selectedMode === 'manual') {
        $('.recipient-manual-settings').show();
    } else if (selectedMode === 'dynamic') {
        $('.recipient-dynamic-settings').show();
    }
});

// Trigger perubahan saat halaman dimuat
$(document).ready(function() {
    $('.recipient-mode-selector:checked').trigger('change');
});
    
})(jQuery);