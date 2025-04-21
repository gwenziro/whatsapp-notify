<?php

/**
 * Kelas UrlValidator
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Validation;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class UrlValidator
 * 
 * Validasi URL
 */
class UrlValidator
{
    /**
     * Validasi URL API
     *
     * @param string $url URL yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan URL yang diformat
     */
    public static function validate($url)
    {
        $url = trim($url);
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $url
        ];

        // Cek apakah kosong
        if (empty($url)) {
            $result['message'] = __('URL API tidak boleh kosong', 'whatsapp-notify');
            return $result;
        }

        // Cek format URL dengan filter_var
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $result['message'] = __('Format URL tidak valid. URL harus diawali dengan http:// atau https://', 'whatsapp-notify');
            return $result;
        }

        // Cek apakah skema adalah http atau https
        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            $result['message'] = __('URL harus menggunakan protokol http:// atau https://', 'whatsapp-notify');
            return $result;
        }

        // Cek apakah memiliki host
        if (!isset($parsed['host']) || empty($parsed['host'])) {
            $result['message'] = __('URL tidak memiliki alamat host yang valid', 'whatsapp-notify');
            return $result;
        }

        // Hapus trailing slash jika ada
        $result['formatted'] = rtrim($url, '/');
        $result['is_valid'] = true;
        return $result;
    }
}
