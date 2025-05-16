/**
 * WhatsApp Notify - Configuration Checker Module
 * Memeriksa kelengkapan konfigurasi dan membatasi fitur yang memerlukan konfigurasi lengkap
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * ConfigChecker Module
     */
    WANotify.ConfigChecker = {
        /**
         * Initialize configuration checker module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initConfigChecker();
            console.log('ConfigChecker module initialized');
        },
        
        /**
         * Initialize configuration checker
         */
        initConfigChecker: function() {
            // Skip if on general settings page
            if ($("#wanotify-general-settings").length) {
                return;
            }
            
            this.checkConfiguration();
        },
        
        /**
         * Check configuration state via AJAX
         */
        checkConfiguration: function() {
            const self = this;
            
            $.post(wanotify.ajax_url, {
                action: "wanotify_check_configuration",
                nonce: wanotify.nonce
            })
            .done(function(response) {
                if (!response.success || !response.data.is_complete) {
                    // Show notification banner
                    self.showConfigurationNotice(response.data.validation_results);
                    
                    // Disable features that require complete configuration
                    self.disableUnconfiguredFeatures();
                }
            })
            .fail(function() {
                console.error("Failed to check configuration status");
            });
        },
        
        /**
         * Show notification banner about incomplete configuration
         * @param {Object} validationResults Validation results from server
         */
        showConfigurationNotice: function(validationResults) {
            let message = "<strong>Perhatian:</strong> Beberapa pengaturan dasar belum dikonfigurasi dengan benar: ";
            let fieldMessages = [];
            
            for (const key in validationResults) {
                if (validationResults.hasOwnProperty(key) && !validationResults[key].is_valid) {
                    fieldMessages.push(
                        "<strong>" + validationResults[key].field_name + "</strong>: " +
                        validationResults[key].message
                    );
                }
            }
            
            message += fieldMessages.join(", ");
            message += '<br><a href="' + wanotify.settings_url + 
                '" class="button button-primary button-small">Lengkapi Konfigurasi Sekarang</a>';
            
            // Show persistent banner
            if ($(".wanotify-config-notice").length === 0) {
                $(".wanotify-header").after(
                    '<div class="wanotify-config-notice">' + message + '</div>'
                );
            }
        },
        
        /**
         * Disable features that require complete configuration
         */
        disableUnconfiguredFeatures: function() {
            // Disable test notification button
            $("#wanotify-test-form-notification")
                .addClass("disabled")
                .prop("disabled", true)
                .attr("title", "Konfigurasi dasar belum lengkap");
            
            // Add info to button
            $("#wanotify-test-form-notification").after(
                '<div class="wanotify-feature-blocked-info">' +
                'Fitur ini memerlukan konfigurasi lengkap</div>'
            );
            
            // Disable form status toggles
            $(".wanotify-status-toggle").each(function() {
                const $this = $(this);
                if (!$this.hasClass("disabled")) {
                    $this
                        .addClass("disabled")
                        .prop("disabled", true)
                        .attr("title", "Konfigurasi dasar belum lengkap");
                }
            });
        }
    };
    
})(jQuery);
