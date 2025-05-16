/**
 * WhatsApp Notify - Recipient Manager Module
 * Menangani mode penerima dan penyesuaian pengaturan penerima
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * RecipientManager Module
     */
    WANotify.RecipientManager = {
        /**
         * Initialize recipient manager module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initRecipientMode();
            this.initDynamicRecipientMode();
            console.log('RecipientManager module initialized');
        },
        
        /**
         * Initialize recipient mode selection
         */
        initRecipientMode: function() {
            // Toggle recipient mode
            $(".recipient-mode-selector").on("change", function() {
                var selectedMode = $('input[name="recipient_mode"]:checked').val();
                
                // Hide all settings
                $(".recipient-mode-settings").hide();
                
                // Show settings for selected mode
                if (selectedMode === "manual") {
                    $(".recipient-manual-settings").show();
                } else if (selectedMode === "dynamic") {
                    $(".recipient-dynamic-settings").show();
                }
            });
            
            // Trigger change on page load
            $(".recipient-mode-selector:checked").trigger("change");
        },
        
        /**
         * Handle dynamic recipient mode
         */
        initDynamicRecipientMode: function() {
            const self = this;
            
            // Handle case when phone fields are not available
            $(".wanotify-radio-disabled").on("click", function(e) {
                // If radio button in this container is disabled, prevent selection
                if ($(this).find('input[type="radio"]').is(":disabled")) {
                    e.preventDefault();
                    
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification(
                            "info", 
                            wanotify.i18n.phone_field_required || 
                            "Opsi ini tidak tersedia karena tidak ada field telepon di formulir"
                        );
                    }
                    return false;
                }
            });
            
            // Check if we need to auto-adjust settings
            let needsAutoAdjustment = false;
            
            // If dynamic option is selected but disabled (no phone fields)
            if ($('.wanotify-radio-disabled input[type="radio"]:checked').length) {
                needsAutoAdjustment = true;
            }
            
            // If auto-adjustment needed
            if (needsAutoAdjustment) {
                const formId = $("#wanotify-form-settings").data("form-id");
                
                // Switch to default
                $('input[name="recipient_mode"][value="default"]').prop("checked", true);
                
                // Update UI without triggering change
                self.updateRecipientModeUI("default");
                
                // Show notification
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification(
                        "info",
                        wanotify.i18n.settings_auto_adjusted ||
                        "Pengaturan disesuaikan otomatis karena field telepon tidak tersedia lagi"
                    );
                }
                
                // Save auto-adjusted settings
                self.saveAutoAdjustedSettings(formId);
            }
        },
        
        /**
         * Update UI for recipient mode without triggering change event
         * @param {string} selectedMode Selected recipient mode
         */
        updateRecipientModeUI: function(selectedMode) {
            // Hide all settings
            $(".recipient-mode-settings").hide();
            
            // Show settings for selected mode
            $(`.recipient-${selectedMode}-settings`).show();
        },
        
        /**
         * Save auto-adjusted settings silently
         * @param {number} formId Form ID
         */
        saveAutoAdjustedSettings: function(formId) {
            const self = this;
            const data = {
                action: "wanotify_auto_adjust_form_settings",
                nonce: wanotify.nonce,
                form_id: formId,
                enabled: $("#enabled").is(":checked") ? "1" : "0",
                recipient_mode: "default", // Force to default
                recipient: $('input[name="recipient"]').val(),
                recipient_field: $('select[name="recipient_field"]').val(),
                message_template: $("#message_template").val()
            };
            
            // Handle included fields
            if ($("#include_all_fields").is(":checked")) {
                data.included_fields = ["*"];
            } else {
                data.included_fields = [];
                $('input[name="included_fields[]"]:checked').each(function() {
                    data.included_fields.push($(this).val());
                });
            }
            
            // Save quietly
            $.post(wanotify.ajax_url, data, function(response) {
                if (response.success) {
                    console.log("Form settings auto-adjusted successfully");
                    
                    // Update initial form data
                    if (WANotify.FormUtils) {
                        self.state.initialFormData["wanotify-form-settings"] = 
                            WANotify.FormUtils.getFormState("#wanotify-form-settings");
                        
                        self.state.formIsDirty = false;
                        
                        if (WANotify.UIState && typeof WANotify.UIState.updateDirtyStateIndicator === 'function') {
                            WANotify.UIState.updateDirtyStateIndicator();
                        }
                    }
                }
            });
        }
    };
    
})(jQuery);
