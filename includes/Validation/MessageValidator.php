<?php

/**
 * Kelas MessageValidator
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
 * Class MessageValidator
 * 
 * Validasi template pesan
 */
class MessageValidator
{
    /**
     * Validasi template pesan
     *
     * @param string $template Template yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan template yang diformat
     */
    public static function validate($template)
    {
        // Simpan template asli tanpa mengubah karakter khusus
        $original_template = $template;
        
        $template = trim($template);
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $original_template, // Gunakan template asli
            'is_warning' => false
        ];

        // Cek apakah kosong
        if (empty($template)) {
            $result['message'] = __('Template pesan tidak boleh kosong', 'whatsapp-notify');
            return $result;
        }

        // Cek panjang minimal
        if (strlen($template) < 10) {
            $result['message'] = __('Template pesan terlalu pendek, minimal 10 karakter', 'whatsapp-notify');
            return $result;
        }

        // Cek apakah memiliki minimal satu placeholder
        if (strpos($template, '{') === false || strpos($template, '}') === false) {
            $result['message'] = __('Template sebaiknya memiliki minimal satu placeholder seperti {form_name} atau {form_data}', 'whatsapp-notify');
            // Ini hanya peringatan, bukan error fatal
            $result['is_warning'] = true;
        }

        $result['is_valid'] = true;
        return $result;
    }
}
