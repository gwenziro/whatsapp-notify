/**
 * WhatsApp Notify - Form Validation Module
 * Menangani validasi form dan field untuk berbagai tipe input
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * FormValidation Module
     */
    WANotify.FormValidation = {
        /**
         * Initialize form validation module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initValidationHandlers();
            console.log('FormValidation module initialized');
        },
        
        /**
         * Initialize validation handlers
         */
        initValidationHandlers: function() {
            const self = this;
            
            // Add validation on recipient mode change
            $(".recipient-mode-selector").on("change", function() {
                const selectedMode = $('input[name="recipient_mode"]:checked').val();
                
                // Reset all error messages
                $(".wanotify-field-error").remove();
                $(".wanotify-form-input").removeClass("has-error");
                $(".recipient-mode-settings").removeClass("has-error");
                
                if (selectedMode === "manual") {
                    self.validateManualRecipient();
                }
                
                if (selectedMode === "dynamic") {
                    self.validateDynamicRecipient();
                }
            });
            
            // Real-time validation on custom number field
            $('input[name="recipient"]').on("change keyup", function() {
                if ($('input[name="recipient_mode"]:checked').val() === "manual") {
                    self.validateManualRecipient();
                }
            });
            
            // Real-time validation on field select
            $('select[name="recipient_field"]').on("change", function() {
                if ($('input[name="recipient_mode"]:checked').val() === "dynamic") {
                    self.validateDynamicRecipient();
                }
            });
            
            // Initialize general settings validation if applicable
            if ($("#wanotify-general-settings").length) {
                this.initGeneralSettingsValidation();
            }
            
            // Form settings submission validation
            $("#wanotify-form-settings").on("submit", function(e) {
                if (!self.validateRecipientSettings()) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Test notification validation
            $("#wanotify-test-form-notification").on("click", function(e) {
                if (!self.validateRecipientSettings()) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        /**
         * Initialize general settings validation
         */
        initGeneralSettingsValidation: function() {
            const self = this;
            
            if (!WANotify.Validator || !WANotify.FormUtils) return;
            
            // Validate API URL
            $("#api_url").on("blur", function() {
                WANotify.FormUtils.validateField($(this), WANotify.Validator.validateApiUrl);
            });
            
            // Validate Access Token
            $("#access_token").on("blur", function() {
                WANotify.FormUtils.validateField($(this), WANotify.Validator.validateAccessToken);
            });
            
            // Validate Default Recipient
            $("#default_recipient").on("blur", function() {
                const result = WANotify.FormUtils.validateField(
                    $(this),
                    WANotify.Validator.validateWhatsAppNumber
                );
                
                if (result.isValid && result.formatted !== $(this).val()) {
                    $(this).val(result.formatted);
                    
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification(
                            "info",
                            "Nomor WhatsApp diformat ulang untuk standarisasi"
                        );
                    }
                }
            });
            
            // Validate Message Template
            $("#default_template").on("blur", function() {
                const result = WANotify.FormUtils.validateField(
                    $(this),
                    WANotify.Validator.validateMessageTemplate
                );
                
                if (result.isValid && result.isWarning) {
                    WANotify.FormUtils.showFieldWarning($(this), result.message);
                }
            });
            
            // Override general settings form submission
            $("#wanotify-general-settings").off("submit").on("submit", function(e) {
                e.preventDefault();
                
                // Validate all fields
                let isValid = true;
                
                // Validate URL API
                const apiUrlResult = WANotify.FormUtils.validateField(
                    $("#api_url"),
                    WANotify.Validator.validateApiUrl
                );
                if (!apiUrlResult.isValid) isValid = false;
                
                // Validate Token
                const tokenResult = WANotify.FormUtils.validateField(
                    $("#access_token"),
                    WANotify.Validator.validateAccessToken
                );
                if (!tokenResult.isValid) isValid = false;
                
                // Validate Default Recipient
                const recipientResult = WANotify.FormUtils.validateField(
                    $("#default_recipient"),
                    WANotify.Validator.validateWhatsAppNumber
                );
                if (!recipientResult.isValid) isValid = false;
                
                // Validate Template
                const templateResult = WANotify.FormUtils.validateField(
                    $("#default_template"),
                    WANotify.Validator.validateMessageTemplate
                );
                if (!templateResult.isValid) isValid = false;
                
                // If valid, save settings using FormDataHandler
                if (isValid) {
                    if (WANotify.FormDataHandler) {
                        WANotify.FormDataHandler.saveGeneralSettings($(this));
                    }
                } else {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification(
                            "error",
                            "Harap perbaiki kesalahan pada formulir"
                        );
                    }
                    
                    // Focus on first error field
                    $(".wanotify-form-input.has-error")
                        .first()
                        .find("input, textarea")
                        .focus();
                }
                
                return false;
            });
        },
        
        /**
         * Validate manual recipient
         * @returns {boolean} True if valid
         */
        validateManualRecipient: function() {
            const $input = $('input[name="recipient"]');
            const $container = $(".recipient-manual-settings");
            
            // Remove previous errors
            $(".wanotify-field-error", $container).remove();
            $container.removeClass("has-error");
            
            // Use validator if available
            if (!WANotify.Validator) return true;
            
            const result = WANotify.Validator.validateWhatsAppNumber($input.val());
            
            if (!result.isValid) {
                // Show error
                if (WANotify.FormUtils) {
                    WANotify.FormUtils.showFieldError($input, result.message);
                } else {
                    $input.after('<div class="wanotify-field-error">' + result.message + '</div>');
                    $container.addClass("has-error");
                }
                return false;
            }
            
            // Update field with formatted number if different
            if (result.formatted !== $input.val()) {
                $input.val(result.formatted);
            }
            
            return true;
        },
        
        /**
         * Validate dynamic recipient
         * @returns {boolean} True if valid
         */
        validateDynamicRecipient: function() {
            const $select = $('select[name="recipient_field"]');
            const $container = $(".recipient-dynamic-settings");
            
            // Remove previous errors
            $(".wanotify-field-error", $container).remove();
            $container.removeClass("has-error");
            
            if ($select.val() === "" || $select.val() === "--") {
                // Show error
                if (WANotify.FormUtils) {
                    WANotify.FormUtils.showFieldError($select, "Silakan pilih field");
                } else {
                    $select.after('<div class="wanotify-field-error">Silakan pilih field</div>');
                    $container.addClass("has-error");
                }
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate all recipient settings
         * @returns {boolean} True if valid
         */
        validateRecipientSettings: function() {
            // Check selected mode
            const selectedMode = $('input[name="recipient_mode"]:checked').val();
            let isValid = true;
            
            // Reset all error messages
            $(".wanotify-field-error").remove();
            $(".wanotify-form-input").removeClass("has-error");
            $(".recipient-mode-settings").removeClass("has-error");
            
            if (selectedMode === "manual") {
                isValid = this.validateManualRecipient();
            }
            
            if (selectedMode === "dynamic") {
                isValid = this.validateDynamicRecipient();
            }
            
            if (!isValid && WANotify.Notifications) {
                WANotify.Notifications.showNotification(
                    "error", 
                    "Harap perbaiki error validasi sebelum menyimpan"
                );
            }
            
            return isValid;
        }
    };
    
})(jQuery);
