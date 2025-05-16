/**
 * WhatsApp Notify - UI State Module
 * Menangani state UI seperti form dirty state dan navigasi
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * UIState Module
     */
    WANotify.UIState = {
        /**
         * Initialize UI state module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initDirtyStateIndicator();
            this.interceptNavigation();
            this.initBackButtonHandler();
            this.detectFormChanges();
            this.saveInitialFormData();
            console.log('UIState module initialized');
        },
        
        /**
         * Save initial form data for dirty state detection
         */
        saveInitialFormData: function() {
            // Simpan data form pengaturan umum
            if ($("#wanotify-general-settings").length && WANotify.FormUtils) {
                this.state.initialFormData["wanotify-general-settings"] = 
                    WANotify.FormUtils.getFormState("#wanotify-general-settings");
            }
            
            // Simpan data form pengaturan formulir
            if ($("#wanotify-form-settings").length && WANotify.FormUtils) {
                this.state.initialFormData["wanotify-form-settings"] = 
                    WANotify.FormUtils.getFormState("#wanotify-form-settings");
            }
        },
        
        /**
         * Detect changes in forms to update dirty state
         */
        detectFormChanges: function() {
            const self = this;
            
            // Deteksi perubahan pada form
            $("#wanotify-general-settings, #wanotify-form-settings").on(
                "change input keyup paste",
                function() {
                    // Gunakan timeout untuk menghindari terlalu banyak pemeriksaan
                    clearTimeout(window.wanotifyFormCheckTimer);
                    window.wanotifyFormCheckTimer = setTimeout(function() {
                        self.checkFormDirty();
                    }, 100);
                }
            );
        },
        
        /**
         * Check if any form is dirty (has unsaved changes)
         */
        checkFormDirty: function() {
            let isDirty = false;
            
            if (!WANotify.FormUtils) return false;
            
            // Cek form pengaturan umum
            if ($("#wanotify-general-settings").length) {
                const currentState = WANotify.FormUtils.getFormState("#wanotify-general-settings");
                if (this.state.initialFormData["wanotify-general-settings"] !== currentState) {
                    isDirty = true;
                    this.state.currentForm = document.getElementById("wanotify-general-settings");
                }
            }
            
            // Cek form pengaturan formulir
            if (!isDirty && $("#wanotify-form-settings").length) {
                const currentState = WANotify.FormUtils.getFormState("#wanotify-form-settings");
                if (this.state.initialFormData["wanotify-form-settings"] !== currentState) {
                    isDirty = true;
                    this.state.currentForm = document.getElementById("wanotify-form-settings");
                }
            }
            
            // Update flag global
            this.state.formIsDirty = isDirty;
            this.updateDirtyStateIndicator();
            
            return isDirty;
        },
        
        /**
         * Initialize indicator for dirty state
         */
        initDirtyStateIndicator: function() {
            // Tambahkan elemen indikator ke header jika belum ada
            if ($("#wanotify-dirty-state").length === 0) {
                $(".wanotify-header").append(
                    '<span id="wanotify-dirty-state" class="wanotify-dirty-state"></span>'
                );
            }
            this.updateDirtyStateIndicator();
        },
        
        /**
         * Update dirty state indicator
         */
        updateDirtyStateIndicator: function() {
            if (this.state.formIsDirty) {
                $("#wanotify-dirty-state")
                    .text("Perubahan Belum Disimpan")
                    .addClass("active");
            } else {
                $("#wanotify-dirty-state").text("").removeClass("active");
            }
        },
        
        /**
         * Intercept navigation to prevent leaving with unsaved changes
         */
        interceptNavigation: function() {
            const self = this;
            
            // Intercept beforeunload event
            $(window).on("beforeunload", function(e) {
                if (self.state.formIsDirty) {
                    // Standard for most browsers
                    e.returnValue = "Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin meninggalkan halaman ini?";
                    return e.returnValue;
                }
            });
        },
        
        /**
         * Handle back button
         */
        initBackButtonHandler: function() {
            const self = this;
            
            $(".wanotify-back-btn").on("click", function(e) {
                // Mencegah navigasi default
                e.preventDefault();
                
                const href = $(this).attr("href");
                
                // Periksa apakah ada perubahan yang belum disimpan
                if (self.state.formIsDirty) {
                    if (!confirm("Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin kembali?")) {
                        return;
                    }
                }
                
                // Jika kita berada di halaman konfigurasi formulir
                if ($("#wanotify-form-settings").length) {
                    const formId = $("#wanotify-form-settings").data("form-id");
                    const isEnabled = $("#enabled").is(":checked");
                    
                    // Simpan status terakhir yang kita lihat
                    localStorage.setItem(
                        "wanotifyLastStatus",
                        JSON.stringify({
                            formId: formId,
                            enabled: isEnabled,
                        })
                    );
                    
                    // Tambahkan flag untuk session
                    sessionStorage.setItem("wanotifyBackToList", "true");
                }
                
                // Reset form dirty state
                self.state.formIsDirty = false;
                
                // Arahkan ke halaman tujuan
                window.location.href = href;
            });
        }
    };
    
})(jQuery);
