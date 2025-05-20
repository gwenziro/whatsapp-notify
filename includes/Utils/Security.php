<?php

/**
 * Security utility class
 *
 * @package WhatsApp_Notify
 * @subpackage Utils
 * @since 1.0.0
 */

namespace WANotify\Utils;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Security
 * 
 * Kelas utilitas untuk keamanan
 */
class Security
{
    /**
     * Sanitasi dan validasi URL
     * 
     * @param string $url URL yang akan divalidasi
     * @return string URL yang sudah disanitasi
     */
    public static function sanitize_url($url)
    {
        // Trim dan sanitasi
        $url = trim(esc_url_raw($url));

        // Hapus trailing slash
        return rtrim($url, '/');
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool True if valid, false otherwise
     */
    public static function verify_ajax_nonce($nonce, $action = 'wanotify_admin_nonce')
    {
        if (!isset($nonce) || !wp_verify_nonce($nonce, $action)) {
            return false;
        }

        return true;
    }

    /**
     * Check user capabilities
     *
     * @param string $capability Capability to check
     * @return bool True if user has capability, false otherwise
     */
    public static function current_user_can($capability = 'manage_options')
    {
        return current_user_can($capability);
    }
    
    /**
     * Sanitasi input teks
     *
     * @param string $text Teks yang akan disanitasi
     * @return string Teks yang sudah disanitasi
     */
    public static function sanitize_text($text)
    {
        return sanitize_text_field($text);
    }
    
    /**
     * Sanitasi integer
     *
     * @param mixed $value Nilai yang akan disanitasi
     * @return int Nilai integer yang sudah disanitasi
     */
    public static function sanitize_int($value)
    {
        return intval($value);
    }
    
    /**
     * Sanitasi boolean
     *
     * @param mixed $value Nilai yang akan disanitasi menjadi boolean
     * @return bool Nilai boolean yang sudah disanitasi
     */
    public static function sanitize_bool($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Validasi dan sanitasi array
     *
     * @param mixed $value Nilai yang akan divalidasi sebagai array
     * @param mixed $default Nilai default jika bukan array
     * @return array Nilai array yang sudah divalidasi
     */
    public static function sanitize_array($value, $default = [])
    {
        return is_array($value) ? $value : $default;
    }
    
    /**
     * Sanitasi HTML untuk template pesan
     *
     * @param string $html HTML yang akan disanitasi
     * @return string HTML yang sudah disanitasi
     */
    public static function sanitize_template_html($html)
    {
        // Gunakan stripslashes_deep untuk menghilangkan escape backslash otomatis
        return stripslashes_deep($html);
    }
}
