/**
 * WhatsApp Notify - Test Handlers Module
 * Menangani pengujian koneksi dan notifikasi
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * TestHandlers Module
     */
    WANotify.TestHandlers = {
        /**
         * Initialize test handlers module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initTestButtons();
            console.log('TestHandlers module initialized');
        },
        
        /**
         * Initialize test buttons event handlers
         */
        initTestButtons: function() {
            const self = this;
            
            // Test connection button
            $("#wanotify-test-connection").on("click", function(e) {
                e.preventDefault();
                self.testConnection($(this));
                return false;
            });
            
            // Test form notification button
            $("#wanotify-test-form-notification").on("click", function(e) {
                e.preventDefault();
                self.testFormNotification($(this));
                return false;
            });
            
            // Clear logs button
            $("#wanotify-clear-logs").on("click", function(e) {
                e.preventDefault();
                self.clearLogs($(this));
                return false;
            });
            
            // Update test button state based on form status
            $("#enabled").on("change", function() {
                self.updateTestButtonState($(this).is(":checked"));
            });
            
            // Initial state on page load
            setTimeout(function() {
                if ($("#enabled").length) {
                    self.updateTestButtonState($("#enabled").is(":checked"));
                }
            }, 100);
        },
        
        /**
         * Update test button state based on form enabled status
         * @param {boolean} isEnabled Whether form notifications are enabled
         */
        updateTestButtonState: function(isEnabled) {
            const $btn = $("#wanotify-test-form-notification");
            if (!$btn.length) return;
            
            // Remove existing tooltip if any
            $("#wanotify-test-tooltip").remove();
            
            if (!isEnabled) {
                $btn.addClass("button-disabled").prop("disabled", true);
                
                // Create tooltip with more information
                const $tooltipContainer = $('<span id="wanotify-test-tooltip" class="wanotify-tooltip-container"></span>');
                const $tooltipIcon = $('<span class="dashicons dashicons-info-outline"></span>');
                
                $tooltipContainer.attr('title', "Notifikasi harus diaktifkan untuk melakukan pengujian");
                $tooltipContainer.append($tooltipIcon);
                
                // Add tooltip after button
                $btn.after($tooltipContainer);
            } else {
                $btn.removeClass("button-disabled").prop("disabled", false);
            }
        },
        
        /**
         * Test WhatsApp connection
         * @param {jQuery} $btn Button element
         */
        testConnection: function($btn) {
            const btnText = $btn.text();
            
            $btn.text(wanotify.i18n.testing).prop("disabled", true);
            
            $.post(wanotify.ajax_url, {
                action: "wanotify_test_connection",
                nonce: wanotify.nonce
            })
            .done(function(response) {
                if (response.success) {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("success", response.data.message);
                    }
                } else {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("error", response.data.message);
                    }
                }
            })
            .fail(function() {
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
                }
            })
            .always(function() {
                $btn.text(btnText).prop("disabled", false);
            });
        },
        
        /**
         * Test form notification
         * @param {jQuery} $btn Button element
         */
        testFormNotification: function($btn) {
            const self = this;
            const formId = $("#wanotify-form-settings").data("form-id");
            const btnText = $btn.text();
            
            // Check if form is enabled
            const isEnabled = $("#enabled").is(":checked");
            if (!isEnabled) {
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification(
                        "error",
                        "Notifikasi tidak diaktifkan untuk formulir ini. Aktifkan terlebih dahulu untuk melakukan pengujian."
                    );
                }
                return;
            }
            
            // Get selected recipient mode
            const selectedMode = $('input[name="recipient_mode"]:checked').val();
            
            // Validate recipient mode settings
            if (!WANotify.FormValidation) {
                return;
            }
            
            // Check recipient mode validity
            if (selectedMode === "manual" && $('input[name="recipient"]').val().trim() === "") {
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification(
                        "error",
                        "Nomor kustom wajib diisi untuk mengirim notifikasi tes."
                    );
                }
                return;
            }
            
            if (selectedMode === "dynamic" && 
                ($('select[name="recipient_field"]').val() === "" || 
                 $('select[name="recipient_field"]').val() === "--")) {
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification("error", "Silakan pilih field terlebih dahulu.");
                }
                return;
            }
            
            $btn.text(wanotify.i18n.testing).prop("disabled", true);
            
            $.post(wanotify.ajax_url, {
                action: "wanotify_test_form_notification",
                nonce: wanotify.nonce,
                form_id: formId,
                recipient_mode: selectedMode
            })
            .done(function(response) {
                if (response.success) {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("success", response.data.message);
                    }
                    
                    // Update initial form data to prevent false dirty state
                    if (WANotify.FormUtils) {
                        self.state.initialFormData["wanotify-form-settings"] = 
                            WANotify.FormUtils.getFormState("#wanotify-form-settings");
                            
                        // Reset dirty flag
                        self.state.formIsDirty = false;
                        
                        if (WANotify.UIState && typeof WANotify.UIState.updateDirtyStateIndicator === 'function') {
                            WANotify.UIState.updateDirtyStateIndicator();
                        }
                    }
                    
                    // Set flag for next save
                    self.state.wanotifyTestCompleted = true;
                } else {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("error", response.data.message);
                    }
                }
            })
            .fail(function(xhr) {
                console.error("Test form notification failed:", xhr.responseText);
                
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
                }
            })
            .always(function() {
                $btn.text(btnText).prop("disabled", false);
            });
        },
        
        /**
         * Clear logs
         * @param {jQuery} $btn Button element
         */
        clearLogs: function($btn) {
            if (!confirm(wanotify.i18n.confirm_clear_logs)) {
                return;
            }
            
            const btnText = $btn.text();
            
            $btn.text("Menghapus...").prop("disabled", true);
            
            $.post(wanotify.ajax_url, {
                action: "wanotify_clear_logs",
                nonce: wanotify.nonce
            })
            .done(function(response) {
                if (response.success) {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("success", response.data.message);
                    }
                    $("#wanotify-logs-container").html("<p>Log telah dibersihkan.</p>");
                } else {
                    if (WANotify.Notifications) {
                        WANotify.Notifications.showNotification("error", response.data.message);
                    }
                }
            })
            .fail(function() {
                if (WANotify.Notifications) {
                    WANotify.Notifications.showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
                }
            })
            .always(function() {
                $btn.text(btnText).prop("disabled", false);
            });
        }
    };
    
})(jQuery);
