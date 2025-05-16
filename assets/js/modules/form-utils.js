/**
 * WhatsApp Notify - Form Utils Module
 * Berisi fungsi-fungsi utilitas untuk pengelolaan form
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * FormUtils Module
     */
    WANotify.FormUtils = {
        /**
         * Initialize form utilities module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            console.log('FormUtils module initialized');
        },
        
        /**
         * Mendapatkan state form untuk perbandingan
         * @param {string} formSelector Selector form
         * @returns {string} JSON string representasi state form
         */
        getFormState: function(formSelector) {
            const $form = $(formSelector);
            const state = {};
            
            // Ambil nilai input text, textarea, dan select
            $form.find('input[type="text"], input[type="url"], input[type="number"], textarea, select').each(function() {
                state[this.id || this.name] = $(this).val();
            });
            
            // Ambil nilai checkbox secara eksplisit
            $form.find('input[type="checkbox"]').each(function() {
                state[this.id || this.name] = $(this).is(":checked");
            });
            
            // Ambil nilai radio button yang checked
            $form.find('input[type="radio"]:checked').each(function() {
                state[this.name] = $(this).val();
            });
            
            return JSON.stringify(state);
        },
        
        /**
         * Tampilkan error per field dari response server
         * @param {jQuery} $form Form yang berisi field-field
         * @param {Object} errors Object berisi error per field
         */
        displayFieldErrors: function($form, errors) {
            // Reset existing errors
            $form.find(".wanotify-field-error").remove();
            $form.find(".wanotify-form-input").removeClass("has-error");
            
            // Loop melalui semua error
            for (const fieldName in errors) {
                if (errors.hasOwnProperty(fieldName)) {
                    const errorMessage = errors[fieldName];
                    const $field = $form.find(`#${fieldName}`);
                    
                    if ($field.length) {
                        // Tambahkan kelas error pada container
                        const $container = $field.closest(".wanotify-form-input");
                        $container.addClass("has-error");
                        
                        // Tambahkan pesan error di bawah field
                        $field.after('<div class="wanotify-field-error">' + errorMessage + '</div>');
                    }
                }
            }
            
            // Focus pada field error pertama jika ada
            const $firstErrorField = $form.find(".wanotify-form-input.has-error")
                .first()
                .find("input, textarea, select");
                
            if ($firstErrorField.length) {
                $firstErrorField.focus();
            }
        },
        
        /**
         * Toggle field list based on "include all fields" checkbox
         * @param {boolean} includeAll Whether to include all fields
         */
        toggleFieldList: function(includeAll) {
            if (includeAll) {
                $("#field_list").hide();
                // Check/uncheck all fields
                $('#field_list input[type="checkbox"]').prop("checked", true);
            } else {
                $("#field_list").show();
            }
        },
        
        /**
         * Validasi field dengan fungsi validator tertentu
         * @param {jQuery} $field Field element
         * @param {Function} validatorFn Fungsi validator
         * @returns {Object} Hasil validasi
         */
        validateField: function($field, validatorFn) {
            // Reset state validasi
            this.removeFieldError($field);
            
            // Validasi dengan fungsi yang diberikan
            const result = validatorFn($field.val());
            
            if (!result.isValid) {
                this.showFieldError($field, result.message);
            }
            
            return result;
        },
        
        /**
         * Menampilkan error pada field
         * @param {jQuery} $field Field yang akan ditampilkan error
         * @param {string} message Pesan error
         */
        showFieldError: function($field, message) {
            const $container = $field.closest(".wanotify-form-input");
            $container.addClass("has-error");
            $field.after('<div class="wanotify-field-error">' + message + "</div>");
        },
        
        /**
         * Menampilkan peringatan pada field
         * @param {jQuery} $field Field yang akan ditampilkan peringatan
         * @param {string} message Pesan peringatan
         */
        showFieldWarning: function($field, message) {
            const $container = $field.closest(".wanotify-form-input");
            $container.addClass("has-warning");
            $field.after('<div class="wanotify-field-warning">' + message + "</div>");
        },
        
        /**
         * Menghapus error dari field
         * @param {jQuery} $field Field yang akan dihapus errornya
         */
        removeFieldError: function($field) {
            const $container = $field.closest(".wanotify-form-input");
            $container.removeClass("has-error has-warning");
            $field.siblings(".wanotify-field-error, .wanotify-field-warning").remove();
        }
    };
    
})(jQuery);
