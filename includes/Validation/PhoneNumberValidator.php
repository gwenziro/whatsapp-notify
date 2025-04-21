<?php
/**
 * Kelas PhoneNumberValidator
 *
 * @package WhatsApp_Notify
 * @subpackage Validation
 * @since 1.0.0
 */

namespace WANotify\Validation;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PhoneNumberValidator
 * 
 * Validasi nomor telepon untuk WhatsApp
 */
class PhoneNumberValidator
{
    /**
     * Validasi nomor WhatsApp
     *
     * @param string $number Nomor yang akan divalidasi
     * @return array Hasil validasi dengan status, pesan, dan nomor yang diformat
     */
    public static function validate($number)
    {
        // Konversi ke string dan trim
        $number = is_string($number) ? trim($number) : strval($number);
        
        $result = [
            'is_valid' => false,
            'message' => '',
            'formatted' => $number
        ];

        // Cek apakah kosong
        if (empty($number)) {
            $result['message'] = __('Nomor WhatsApp tidak boleh kosong', 'whatsapp-notify');
            return $result;
        }

        // Validasi format nomor - harus hanya berisi angka dan mungkin tanda + di awal
        if (!preg_match('/^\+?[0-9]+$/', $number)) {
            $result['message'] = __('Format nomor WhatsApp tidak valid. Hanya boleh berisi angka dan tanda + di awal', 'whatsapp-notify');
            return $result;
        }

        // Bersihkan dari karakter non-numerik kecuali + di awal (untuk keamanan ekstra)
        $clean_number = preg_replace('/[^0-9+]/', '', $number);

        // Cek panjang minimal (min. 10 digit not including +)
        $digits_only = str_replace('+', '', $clean_number);
        if (strlen($digits_only) < 10) {
            $result['message'] = __('Nomor WhatsApp terlalu pendek, minimal 10 digit', 'whatsapp-notify');
            return $result;
        }

        // Cek panjang maksimal (max. 15 digit including country code)
        if (strlen($digits_only) > 15) {
            $result['message'] = __('Nomor WhatsApp terlalu panjang, maksimal 15 digit', 'whatsapp-notify');
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
}
