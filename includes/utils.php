<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class untuk fungsi-fungsi pembantu
 */
class FluentWA_Utils {
    /**
     * Format nomor telepon ke format internasional
     * 
     * @param string|array $number Nomor yang akan diformat
     * @return string Nomor yang sudah diformat
     */
    public static function format_phone_number($number) {
        // Handle jika nomor adalah array
        if (is_array($number)) {
            // Gunakan nilai pertama dari array atau string kosong jika array kosong
            $number = !empty($number) ? reset($number) : '';
        }
        
        // Pastikan nomor adalah string
        $number = (string)$number;
        
        // Bersihkan nomor dari karakter khusus
        $number = preg_replace('/[^0-9+]/', '', $number);
        
        // Skip nomor kosong
        if (empty($number)) {
            return '';
        }
        
        // Jika nomor dimulai dengan 0, ganti dengan kode negara Indonesia
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }
        
        // Jika belum ada tanda +, tambahkan
        if (substr($number, 0, 1) !== '+') {
            $number = '+' . $number;
        }
        
        return $number;
    }
    
    /**
     * Format timestamp menjadi tanggal dan waktu yang mudah dibaca
     * 
     * @param int $timestamp Timestamp UNIX
     * @return string Tanggal dan waktu yang diformat
     */
    public static function format_datetime($timestamp) {
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }
    
    /**
     * Sanitasi dan validasi URL
     * 
     * @param string $url URL yang akan divalidasi
     * @return string URL yang sudah disanitasi
     */
    public static function sanitize_url($url) {
        // Trim dan sanitasi
        $url = trim(esc_url_raw($url));
        
        // Hapus trailing slash
        return rtrim($url, '/');
    }
}
