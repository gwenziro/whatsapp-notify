/**
 * WhatsApp Notify - Form UI Manager Module
 * Menangani elemen UI dan perubahan tampilan formulir
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * FormUIManager Module
     */
    WANotify.FormUIManager = {
        /**
         * Initialize form UI manager module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            this.initFieldListHandler();
            console.log('FormUIManager module initialized');
        },
        
        /**
         * Initialize field list toggle handler
         */
        initFieldListHandler: function() {
            // Toggle field list when "include all fields" changes
            $("#include_all_fields").on("change", function() {
                if (WANotify.FormUtils) {
                    WANotify.FormUtils.toggleFieldList($(this).is(":checked"));
                } else {
                    // Fallback if FormUtils not available
                    const includeAllFields = $(this).is(":checked");
                    if (includeAllFields) {
                        $("#field_list").hide();
                        // Check all fields
                        $('#field_list input[type="checkbox"]').prop("checked", true);
                    } else {
                        $("#field_list").show();
                    }
                }
            });
            
            // Initialize initial field list state
            if ($("#include_all_fields").length) {
                if (WANotify.FormUtils) {
                    WANotify.FormUtils.toggleFieldList($("#include_all_fields").is(":checked"));
                } else {
                    // Fallback
                    const includeAllFields = $("#include_all_fields").is(":checked");
                    if (includeAllFields) {
                        $("#field_list").hide();
                    } else {
                        $("#field_list").show();
                    }
                }
            }
        }
    };
    
})(jQuery);
