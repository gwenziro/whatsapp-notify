/**
 * WhatsApp Notify - Form Settings Module
 * Koordinator untuk semua modul pengelolaan form
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * FormSettings Module
     */
    WANotify.FormSettings = {
        /**
         * Initialize form settings module
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            
            // Initialize sub-modules to properly structure form settings functionality
            this.initSubModules();
            
            console.log('FormSettings module initialized');
        },
        
        /**
         * Initialize sub-modules
         */
        initSubModules: function() {
            // Initialize form validation
            if (WANotify.FormValidation) {
                WANotify.FormValidation.init(this.state);
            }
            
            // Initialize test handlers
            if (WANotify.TestHandlers) {
                WANotify.TestHandlers.init(this.state);
            }
            
            // Initialize recipient manager
            if (WANotify.RecipientManager) {
                WANotify.RecipientManager.init(this.state);
            }
            
            // Initialize form data handler
            if (WANotify.FormDataHandler) {
                WANotify.FormDataHandler.init(this.state);
            }
            
            // Initialize form UI manager
            if (WANotify.FormUIManager) {
                WANotify.FormUIManager.init(this.state);
            }
        }
    };
    
})(jQuery);
