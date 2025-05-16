/**
 * WhatsApp Notify - Form Data Handler Module
 * Menangani penyimpanan dan pengambilan data formulir
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * FormDataHandler Module
     */
    WANotify.FormDataHandler = {
        /**
         * Initialize form data handler module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initFormSubmitHandlers();
            console.log('FormDataHandler module initialized');
        },
        
        /**
         * Initialize form submit handlers
         */
        initFormSubmitHandlers: function() {
            const self = this;
            
            // Form settings submission
            $("#wanotify-form-settings").on("submit", function(e) {
                e.preventDefault();
                
                // First validate using FormValidation if available
                if (WANotify.FormValidation && typeof WANotify.FormValidation.validateRecipientSettings === 'function') {
                    if (!WANotify.FormValidation.validateRecipientSettings()) {
                        return false;
                    }
                }
                
                self.saveFormSettings($(this));
                return false;
            });
        },
        
        /**
         * Save general settings
         * @param {jQuery} $form Form element
         */
        saveGeneralSettings: function($form) {
            const self = this;
            const btnText = $form.find(".wanotify-submit-btn").text();
            
            $form.find(".wanotify-submit-btn")
                .text(wanotify.i18n.saving)
                .prop("disabled", true);
                
            // Reset errors
            $form.find(".wanotify-field-error").remove();
            $form.find(".wanotify-form-input").removeClass("has-error");
            
            // Format recipient number if validator available
            let formattedRecipient = $form.find("#default_recipient").val();
            if (WANotify.Validator) {
                const recipientValidation = WANotify.Validator.validateWhatsAppNumber(formattedRecipient);
                if (recipientValidation.isValid) {
                    formattedRecipient = recipientValidation.formatted;
                }
            }
            
            const data = {
                action: "wanotify_save_settings",
                nonce: wanotify.nonce,
                api_url: $form.find("#api_url").val(),
                access_token: $form.find("#access_token").val(),
                default_recipient: formattedRecipient,
                default_template: $form.find("#default_template").val(),
                enable_logging: $form.find("#enable_logging").is(":checked") ? 1 : 0
            };
            
            $.post(wanotify.ajax_url, data, function(response) {
                if (response.success) {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("success", response.data.message);
                    }
                    
                    // Reset dirty flag
                    self.state.formIsDirty = false;
                    
                    // Update initial form data
                    if (WANotify.FormUtils) {
                        self.state.initialFormData["wanotify-general-settings"] = 
                            WANotify.FormUtils.getFormState("#wanotify-general-settings");
                    }
                    
                    // Update dirty state indicator
                    if (WANotify.UIState && typeof WANotify.UIState.updateDirtyStateIndicator === 'function') {
                        WANotify.UIState.updateDirtyStateIndicator();
                    }
                } else {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("error", response.data.message);
                    }
                    
                    // Display field errors if any
                    if (response.data.errors && WANotify.FormUtils) {
                        WANotify.FormUtils.displayFieldErrors($form, response.data.errors);
                    }
                }
                
                $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
            }).fail(function() {
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
                }
                
                $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
            });
        },
        
        /**
         * Save form settings
         * @param {jQuery} $form Form element
         */
        saveFormSettings: function($form) {
            const self = this;
            const formId = $form.data("form-id");
            const btnText = $form.find(".wanotify-submit-btn").text();
            
            $form.find(".wanotify-submit-btn")
                .text(wanotify.i18n.saving)
                .prop("disabled", true);
                
            // Get enabled status explicitly
            const isEnabled = $form.find("#enabled").is(":checked");
                
            // Create FormData for better handling of arrays
            const formData = new FormData();
            formData.append('action', 'wanotify_save_form_settings');
            formData.append('nonce', wanotify.nonce);
            formData.append('form_id', formId);
            formData.append('enabled', isEnabled ? '1' : '0');
            
            // Add test_completed flag if needed
            if (this.state.wanotifyTestCompleted) {
                formData.append('test_completed', 'true');
            }
            
            // Get recipient mode
            const recipientMode = $form.find('input[name="recipient_mode"]:checked').val();
            formData.append('recipient_mode', recipientMode);
            
            formData.append('recipient', $form.find('input[name="recipient"]').val());
            formData.append('recipient_field', $form.find('select[name="recipient_field"]').val());
            formData.append('message_template', $form.find('#message_template').val());
            
            // Handle included fields
            if ($form.find('#include_all_fields').is(':checked')) {
                formData.append('included_fields[]', '*');
            } else {
                $form.find('input[name="included_fields[]"]:checked').each(function() {
                    formData.append('included_fields[]', $(this).val());
                });
            }
            
            // Submit using FormData
            $.ajax({
                url: wanotify.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (WANotify.Notifications) {
                            WANotify.Notifications.showNotification("success", response.data.message);
                        }
                        
                        // Reset flags
                        self.state.formIsDirty = false;
                        self.state.wanotifyTestCompleted = false;
                        
                        // Save status for navigation back to list
                        localStorage.setItem(
                            "wanotifyLastStatus",
                            JSON.stringify({
                                formId: formId,
                                enabled: response.data.status !== undefined ? response.data.status : isEnabled
                            })
                        );
                        
                        // Update initial form data
                        if (WANotify.FormUtils) {
                            self.state.initialFormData["wanotify-form-settings"] = 
                                WANotify.FormUtils.getFormState("#wanotify-form-settings");
                        }
                        
                        // Update UI indicators
                        if (WANotify.UIState && typeof WANotify.UIState.updateDirtyStateIndicator === 'function') {
                            WANotify.UIState.updateDirtyStateIndicator();
                        }
                    } else {
                        if (WANotify.Notifications) {
                            WANotify.Notifications.showNotification("error", response.data.message);
                        }
                        
                        // Display field errors if any
                        if (response.data.errors && WANotify.FormUtils) {
                            WANotify.FormUtils.displayFieldErrors($form, response.data.errors);
                        }
                    }
                    
                    $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
                },
                error: function(xhr) {
                    console.error("Save form settings failed:", xhr.responseText);
                    
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
                    }
                    
                    $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
                }
            });
        }
    };
    
})(jQuery);
