<?php

/**
 * Validator untuk Fluent WhatsApp Notifier
 * 
 * @package Fluent_WhatsApp_Notifier
 * @since 1.0.0
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FluentWA_Validator
 * 
 * Kelas untuk memvalidasi input dalam plugin
 */
class FluentWA_Validator
{

    /**
     * Validasi nomor WhatsApp
     * 
     * @param string $number Nomor yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan nomor yang diformat
     */
    public static function validate_whatsapp_number($number)
    {
        $number = trim($number);
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $number
        ];

        // Cek apakah kosong
        if (empty($number)) {
            $result['message'] = __('Nomor WhatsApp tidak boleh kosong', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Validasi format nomor - harus hanya berisi angka dan mungkin tanda + di awal
        if (!preg_match('/^\+?[0-9]+$/', $number)) {
            $result['message'] = __('Format nomor WhatsApp tidak valid. Hanya boleh berisi angka dan tanda + di awal', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Bersihkan dari karakter non-numerik kecuali + di awal (untuk keamanan ekstra)
        $clean_number = preg_replace('/[^0-9+]/', '', $number);

        // Cek panjang minimal (min. 10 digit not including +)
        $digits_only = str_replace('+', '', $clean_number);
        if (strlen($digits_only) < 10) {
            $result['message'] = __('Nomor WhatsApp terlalu pendek, minimal 10 digit', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Cek panjang maksimal (max. 15 digit including country code)
        if (strlen($digits_only) > 15) {
            $result['message'] = __('Nomor WhatsApp terlalu panjang, maksimal 15 digit', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Format ulang untuk standarisasi
        // Jika diawali 0, ganti dengan +62 (untuk Indonesia)
        if (substr($clean_number, 0, 1) === '0') {
            $clean_number = '+62' . substr($clean_number, 1);
        }
        // Jika tidak diawali +, tambahkan +
        else if (substr($clean_number, 0, 1) !== '+') {
            $clean_number = '+' . $clean_number;
        }

        $result['is_valid'] = true;
        $result['formatted'] = $clean_number;
        return $result;
    }

    /**
     * Validasi URL API
     * 
     * @param string $url URL yang akan divalidasi
     * @return array Hasil validasi dengan status dan pesan
     */
    public static function validate_api_url($url)
    {
        $url = trim($url);
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $url
        ];

        // Cek apakah kosong
        if (empty($url)) {
            $result['message'] = __('URL API tidak boleh kosong', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Cek format URL dengan filter_var
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $result['message'] = __('Format URL tidak valid. URL harus diawali dengan http:// atau https://', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Cek apakah skema adalah http atau https
        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            $result['message'] = __('URL harus menggunakan protokol http:// atau https://', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Cek apakah memiliki host
        if (!isset($parsed['host']) || empty($parsed['host'])) {
            $result['message'] = __('URL tidak memiliki alamat host yang valid', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Hapus trailing slash jika ada
        $result['formatted'] = rtrim($url, '/');
        $result['is_valid'] = true;
        return $result;
    }

    /**
     * Validasi template pesan
     * 
     * @param string $template Template yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan dan info warning jika ada
     */
    public static function validate_message_template($template)
    {
        $template = trim($template);
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $template,
            'is_warning' => false
        ];

        // Cek apakah kosong
        if (empty($template)) {
            $result['message'] = __('Template pesan tidak boleh kosong', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Cek panjang minimal
        if (strlen($template) < 10) {
            $result['message'] = __('Template pesan terlalu pendek, minimal 10 karakter', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Cek apakah memiliki minimal satu placeholder
        if (strpos($template, '{') === false || strpos($template, '}') === false) {
            $result['message'] = __('Template sebaiknya memiliki minimal satu placeholder seperti {form_name} atau {form_data}', 'fluent-whatsapp-notifier');
            // Ini hanya peringatan, bukan error fatal
            $result['is_warning'] = true;
        }

        $result['is_valid'] = true;
        return $result;
    }

    /**
     * Validasi token akses/autentikasi
     * 
     * @param string $token Token yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan token yang diformat
     */
    public static function validate_access_token($token)
    {
        $token = trim($token);
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $token
        ];

        // Cek apakah kosong
        if (empty($token)) {
            $result['message'] = __('Token autentikasi tidak boleh kosong', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Hanya validasi panjang minimum dan hapus regex yang terlalu ketat
        if (strlen($token) < 6) {
            $result['message'] = __('Token autentikasi terlalu pendek, minimal 6 karakter', 'fluent-whatsapp-notifier');
            return $result;
        }

        // Hilangkan validasi karakter yang terlalu ketat
        // Biarkan semua karakter non-spasi diizinkan

        $result['is_valid'] = true;
        return $result;
    }
}
