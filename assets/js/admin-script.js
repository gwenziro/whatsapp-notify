/**
 * WhatsApp Notify - Admin JavaScript Entry Point
 * Mengatur inisialisasi modul dan menyediakan state global
 */
(function($) {
    "use strict";
    
    // Global shared state
    const WANotifyState = {
        formIsDirty: false,
        initialFormData: {},
        currentForm: null,
        wanotifyTestCompleted: false
    };
    
    // DOM Ready
    $(function() {
        // Inisialisasi namespace global
        window.WANotify = window.WANotify || {};
        
        // Inisialisasi semua modul dalam urutan yang benar
        initModules();
        
        console.log('WhatsApp Notify Admin Initialized');
    });
    
    /**
     * Inisialisasi semua modul dengan urutan yang benar
     */
    function initModules() {
        // Modul core (penting) pertama
        initModule('Validator');
        initModule('Notifications');
        initModule('FormUtils');
        initModule('UIState');
        
        // Lalu modul fungsional
        initModule('FormSettings');
        initModule('FormToggle');
        initModule('ConfigChecker');
        
        // Log informasi debug
        console.log('Modules initialized:', Object.keys(window.WANotify).join(', '));
    }
    
    /**
     * Inisialisasi modul individual
     * @param {string} moduleName Nama modul untuk diinisialisasi
     * @returns {boolean} Status keberhasilan
     */
    function initModule(moduleName) {
        if (window.WANotify && 
            window.WANotify[moduleName] && 
            typeof window.WANotify[moduleName].init === 'function') {
            try {
                window.WANotify[moduleName].init(WANotifyState);
                return true;
            } catch (e) {
                console.error(`Error initializing module ${moduleName}:`, e);
            }
        } else {
            console.warn(`Module ${moduleName} not found or has no init method`);
        }
        return false;
    }
    
})(jQuery);
