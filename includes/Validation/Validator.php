<?php

/**
 * Kelas Validator
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
 * Class Validator
 * 
 * Kelas utama untuk validasi data
 */
class Validator
{
    /**
     * Validasi nomor WhatsApp
     *
     * @param string $number Nomor yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan nomor yang diformat
     */
    public static function validate_whatsapp_number($number)
    {
        return PhoneNumberValidator::validate($number);
    }

    /**
     * Validasi URL API
     *
     * @param string $url URL yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan URL yang diformat
     */
    public static function validate_api_url($url)
    {
        return UrlValidator::validate($url);
    }

    /**
     * Validasi template pesan
     *
     * @param string $template Template yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan template yang diformat
     */
    public static function validate_message_template($template)
    {
        return MessageValidator::validate($template);
    }

    /**
     * Validasi token akses/autentikasi
     *
     * @param string $token Token yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan token yang diformat
     */
    public static function validate_access_token($token)
    {
        return TokenValidator::validate($token);
    }
}
