/**
 * WhatsApp Notify - Form Toggle Module
 * Menangani toggle status form dan sinkronisasi
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * FormToggle Module
     */
    WANotify.FormToggle = {
        /**
         * Initialize form toggle module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.toggleState = {};
            this.initFormStatusToggles();
            this.initPageLoadHandlers();
            console.log('FormToggle module initialized');
        },
        
        /**
         * Initialize form status toggles
         */
        initFormStatusToggles: function() {
            const self = this;
            
            // Remove any existing handlers to avoid duplicates
            $(document).off("change", ".wanotify-status-toggle");
            
            // Add change handler
            $(document).on("change", ".wanotify-status-toggle", function() {
                self.toggleFormStatus($(this));
            });
        },
        
        /**
         * Initialize handlers on page load
         */
        initPageLoadHandlers: function() {
            const self = this;
            
            console.log("Form toggle page load handlers initialized");
            
            // Refresh form statuses on load
            setTimeout(function() {
                self.refreshFormStatuses();
            }, 300);
            
            // Check if we're coming back from form config page
            if (sessionStorage.getItem("wanotifyBackToList") === "true") {
                console.log("Detected back navigation from form configuration");
                
                // Remove flag
                sessionStorage.removeItem("wanotifyBackToList");
                
                // Force refresh with longer delay for DOM to be ready
                setTimeout(function() {
                    self.refreshFormStatuses();
                    
                    // Add second refresh as fallback
                    setTimeout(function() {
                        self.refreshFormStatuses();
                    }, 1000);
                }, 500);
            }
        },
        
        /**
         * Toggle form status
         * @param {jQuery} $toggle Toggle element
         */
        toggleFormStatus: function($toggle) {
            const self = this;
            const formId = $toggle.data("form-id");
            const isEnabled = $toggle.is(":checked");
            const $statusText = $toggle.siblings(".wanotify-toggle-status");
            const $toggleContainer = $toggle.closest(".wanotify-toggle");
            
            // Store original status for rollback
            const originalStatus = !isEnabled;
            
            // Add loading class
            $toggleContainer.addClass("wanotify-loading");
            
            // Update text temporarily
            $statusText.text(isEnabled ? 
                (wanotify.i18n.activating || "Mengaktifkan...") :
                (wanotify.i18n.deactivating || "Menonaktifkan...")
            );
            
            // Send AJAX request
            $.post(wanotify.ajax_url, {
                action: "wanotify_toggle_form_status",
                nonce: wanotify.nonce,
                form_id: formId,
                enabled: isEnabled ? 1 : 0
            })
            .done(function(response) {
                if (response.success) {
                    // Use status from server response to ensure correctness
                    const newStatus = response.data.status !== undefined ?
                        response.data.status : isEnabled;
                        
                    // Update local state
                    self.toggleState[formId] = newStatus;
                    
                    // Update toggle UI
                    $toggle.prop("checked", newStatus);
                    $statusText.text(newStatus ? "Aktif" : "Tidak Aktif");
                    
                    // Update form settings if open
                    self.updateFormSettingsAfterToggle(formId, newStatus);
                    
                    // Show success message
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("success", response.data.message);
                    }
                } else {
                    // Roll back to original state
                    $toggle.prop("checked", originalStatus);
                    $statusText.text(originalStatus ? "Aktif" : "Tidak Aktif");
                    
                    // Show error message
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("error", response.data.message);
                    }
                }
            })
            .fail(function() {
                // Roll back to original state
                $toggle.prop("checked", originalStatus);
                $statusText.text(originalStatus ? "Aktif" : "Tidak Aktif");
                
                // Show error message
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
                }
            })
            .always(function() {
                // Remove loading class
                $toggleContainer.removeClass("wanotify-loading");
            });
        },
        
        /**
         * Update form settings after toggle
         * @param {number} formId Form ID
         * @param {boolean} enabled New status
         */
        updateFormSettingsAfterToggle: function(formId, enabled) {
            const $formSettings = $("#wanotify-form-settings");
            
            // Check if we're on the form settings page for this form
            if ($formSettings.length && parseInt($formSettings.data("form-id")) === parseInt(formId)) {
                const $enabledCheckbox = $("#enabled");
                
                if ($enabledCheckbox.length) {
                    // Update enabled checkbox without triggering change event
                    $enabledCheckbox.prop("checked", enabled);
                }
                
                // Update initial form data to prevent false unsaved changes detection
                if (WANotify.FormUtils) {
                    this.state.initialFormData["wanotify-form-settings"] = 
                        WANotify.FormUtils.getFormState("#wanotify-form-settings");
                
                    // Reset dirty flag
                    this.state.formIsDirty = false;
                    
                    // Update dirty state indicator
                    if (WANotify.UIState && typeof WANotify.UIState.updateDirtyStateIndicator === 'function') {
                        WANotify.UIState.updateDirtyStateIndicator();
                    }
                }
            }
        },
        
        /**
         * Refresh form statuses from server
         */
        refreshFormStatuses: function() {
            const self = this;
            
            // Only proceed if there are any toggles on the page
            if ($(".wanotify-status-toggle").length === 0) {
                console.log("No form status toggles found on page");
                return;
            }
            
            // Collect all form IDs on the page
            const formIds = [];
            $(".wanotify-status-toggle").each(function() {
                formIds.push($(this).data("form-id"));
            });
            
            if (formIds.length === 0) {
                console.log("No form IDs collected");
                return;
            }
            
            console.log("Refreshing form statuses for IDs:", formIds);
            
            // Get latest status from server
            $.post(wanotify.ajax_url, {
                action: "wanotify_get_forms_status",
                nonce: wanotify.nonce,
                form_ids: formIds
            })
            .done(function(response) {
                if (response.success && response.data.statuses) {
                    // Update all toggles based on server status
                    $.each(response.data.statuses, function(formId, status) {
                        self.updateFormToggle(formId, status);
                    });
                } else {
                    console.error("Error in response or missing statuses:", response);
                }
            })
            .fail(function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
            })
            .always(function() {
                // Check localStorage for last saved status
                const lastStatusJson = localStorage.getItem("wanotifyLastStatus");
                if (lastStatusJson) {
                    try {
                        const lastStatus = JSON.parse(lastStatusJson);
                        console.log("Found last saved status in localStorage:", lastStatus);
                        
                        // Apply saved status from localStorage if form exists on page
                        if (lastStatus && lastStatus.formId) {
                            self.updateFormToggle(lastStatus.formId, lastStatus.enabled);
                        }
                        
                        // Remove item from localStorage after use
                        localStorage.removeItem("wanotifyLastStatus");
                    } catch (e) {
                        console.error("Error parsing last status from localStorage:", e);
                    }
                }
            });
        },
        
        /**
         * Update the toggle UI for a specific form
         * @param {string|number} formId Form ID
         * @param {boolean} status Enabled status
         */
        updateFormToggle: function(formId, status) {
            const $toggle = $(`.wanotify-status-toggle[data-form-id="${formId}"]`);
            
            if ($toggle.length) {
                console.log(`Updating form ${formId} status to ${status ? "active" : "inactive"}`);
                $toggle.prop("checked", status);
                $toggle
                    .siblings(".wanotify-toggle-status")
                    .text(status ? "Aktif" : "Tidak Aktif");
            }
        }
    };
    
})(jQuery);
