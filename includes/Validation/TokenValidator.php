<?php

/**
 * Kelas TokenValidator
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
 * Class TokenValidator
 * 
 * Validasi token API
 */
class TokenValidator
{
    /**
     * Validasi token akses/autentikasi
     *
     * @param string $token Token yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan token yang diformat
     */
    public static function validate($token)
    {
        $token = trim($token);
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $token
        ];

        // Cek apakah kosong
        if (empty($token)) {
            $result['message'] = __('Token autentikasi tidak boleh kosong', 'whatsapp-notify');
            return $result;
        }

        // Validasi panjang minimum
        if (strlen($token) < 6) {
            $result['message'] = __('Token autentikasi terlalu pendek, minimal 6 karakter', 'whatsapp-notify');
            return $result;
        }

        $result['is_valid'] = true;
        return $result;
    }
}
