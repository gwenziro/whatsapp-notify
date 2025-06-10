/**
 * WhatsApp Notify - Validator Module
 * Menyediakan fungsi validasi untuk berbagai input
 */
(function($) {
    "use strict";
    
    // Namespace
    window.WANotify = window.WANotify || {};
    
    /**
     * Validator Module
     */
    WANotify.Validator = {
        /**
         * Initialize validator
         * @param {Object} state Global state
         */
        init: function(state) {
            this.state = state;
            console.log('Validator module initialized');
        },
        
        /**
         * Validasi nomor WhatsApp
         * @param {string} number Nomor yang akan divalidasi
         * @returns {object} Hasil validasi
         */
        validateWhatsAppNumber: function(number) {
            number = number.trim();
            const result = {
                isValid: false,
                message: "",
                formatted: number
            };
            
            // Cek apakah kosong
            if (!number) {
                result.message = "Nomor WhatsApp tidak boleh kosong";
                return result;
            }
            
            // Validasi format - harus hanya berisi angka dan mungkin tanda + di awal
            if (!/^\+?[0-9]+$/.test(number)) {
                result.message = "Format nomor WhatsApp tidak valid. Hanya boleh berisi angka dan tanda + di awal";
                return result;
            }
            
            // Bersihkan dari karakter non-numerik kecuali + di awal
            const cleanNumber = number.replace(/[^\d+]/g, "");
            
            // Cek panjang minimal
            const digitsOnly = cleanNumber.replace(/\+/g, "");
            if (digitsOnly.length < 10) {
                result.message = "Nomor WhatsApp terlalu pendek, minimal 10 digit";
                return result;
            }
            
            // Cek panjang maksimal
            if (digitsOnly.length > 15) {
                result.message = "Nomor WhatsApp terlalu panjang, maksimal 15 digit";
                return result;
            }
            
            // Format ulang untuk standarisasi
            let formattedNumber = cleanNumber;
            // Jika diawali 0, ganti dengan +62 (untuk Indonesia)
            if (formattedNumber.startsWith("0")) {
                formattedNumber = "+62" + formattedNumber.substring(1);
            }
            // Jika tidak diawali +, tambahkan +
            else if (!formattedNumber.startsWith("+")) {
                formattedNumber = "+" + formattedNumber;
            }
            
            result.isValid = true;
            result.formatted = formattedNumber;
            return result;
        },
        
        /**
         * Validasi URL API
         * @param {string} url URL yang akan divalidasi
         * @returns {object} Hasil validasi
         */
        validateApiUrl: function(url) {
            // Untuk mencegah error, periksa apakah url undefined atau null
            if (url === undefined || url === null) {
                return {
                    isValid: true, // Anggap valid karena field ini sekarang otomatis dari konstanta
                    message: "",
                    formatted: ""
                };
            }
            
            url = url.trim();
            
            if (url === "") {
                return {
                    isValid: false,
                    message: "URL API tidak boleh kosong",
                    formatted: url
                };
            }
            
            // Cek format URL
            if (!/^https?:\/\/.+/i.test(url)) {
                return {
                    isValid: false,
                    message: "URL harus diawali dengan http:// atau https://",
                    formatted: url
                };
            }
            
            // Hapus trailing slash jika ada
            const formatted = url.replace(/\/+$/, "");
            
            return {
                isValid: true,
                message: "",
                formatted: formatted
            };
        },
        
        /**
         * Validasi template pesan
         * @param {string} template Template yang akan divalidasi
         * @returns {object} Hasil validasi
         */
        validateMessageTemplate: function(template) {
            template = template.trim();
            const result = {
                isValid: false,
                message: "",
                formatted: template,
                isWarning: false
            };
            
            // Cek apakah kosong
            if (!template) {
                result.message = "Template pesan tidak boleh kosong";
                return result;
            }
            
            // Cek panjang minimal
            if (template.length < 10) {
                result.message = "Template pesan terlalu pendek, minimal 10 karakter";
                return result;
            }
            
            // Cek apakah memiliki minimal satu placeholder
            if (!template.includes("{") || !template.includes("}")) {
                result.message = "Template sebaiknya memiliki minimal satu placeholder seperti {form_name} atau {form_data}";
                // Ini hanya peringatan, bukan error fatal
                result.isWarning = true;
            }
            
            result.isValid = true;
            return result;
        },
        
        /**
         * Validasi token akses/autentikasi
         * @param {string} token Token yang akan divalidasi
         * @returns {object} Hasil validasi
         */
        validateAccessToken: function(token) {
            // Pastikan token tidak undefined atau null
            if (token === undefined || token === null) {
                return {
                    isValid: false,
                    message: "Token autentikasi tidak boleh kosong",
                    formatted: ""
                };
            }
            
            token = token.trim();
            
            if (token === "") {
                return {
                    isValid: false,
                    message: "Token autentikasi tidak boleh kosong",
                    formatted: token
                };
            }
            
            if (token.length < 6) {
                return {
                    isValid: false,
                    message: "Token autentikasi terlalu pendek (minimal 6 karakter)",
                    formatted: token
                };
            }
            
            return {
                isValid: true,
                message: "",
                formatted: token
            };
        }
    };
    
})(jQuery);
