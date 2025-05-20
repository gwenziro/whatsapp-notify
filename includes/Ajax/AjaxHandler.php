<?php

/**
 * Base AjaxHandler Class
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Ajax;

use WANotify\Utils\Security;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AjaxHandler
 * 
 * Kelas dasar untuk semua AJAX handler
 */
abstract class AjaxHandler
{
    /**
     * Nonce action untuk validasi
     *
     * @var string
     */
    protected $nonce_action = 'wanotify_admin_nonce';

    /**
     * Cek keamanan dan kembalikan data request
     *
     * @param string $capability Capability yang dibutuhkan
     * @return array|false Data request atau false jika validasi gagal
     */
    protected function verify_request($capability = 'manage_options')
    {
        // Verifikasi nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->nonce_action)) {
            $this->send_error(__('Permintaan tidak valid atau kedaluwarsa', 'whatsapp-notify'));
            return false;
        }

        // Verifikasi capability
        if (!Security::current_user_can($capability)) {
            $this->send_error(__('Anda tidak memiliki izin untuk melakukan tindakan ini', 'whatsapp-notify'));
            return false;
        }

        // Sanitisasi dan return data request
        return $this->sanitize_input($_POST);
    }

    /**
     * Kirim response sukses
     *
     * @param array $data Data untuk response
     * @param int $status_code HTTP status code (default: 200)
     */
    protected function send_success($data = [], $status_code = 200)
    {
        wp_send_json_success($data, $status_code);
    }

    /**
     * Kirim response error dengan format standar
     *
     * @param string $message Pesan error
     * @param array $data Data tambahan
     * @param int $status_code HTTP status code (default: 400)
     */
    protected function send_error($message, $data = [], $status_code = 400)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        wp_send_json_error($response, $status_code);
    }

    /**
     * Sanitisasi input dari request
     *
     * @param array $data Data input
     * @return array Data yang telah disanitasi
     */
    protected function sanitize_input($data)
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_input($value);
            } else {
                switch ($key) {
                    case 'message_template':
                    case 'default_template':
                        // PERBAIKAN: Pertahankan karakter khusus dalam template pesan
                        $sanitized[$key] = stripslashes($value); // Hilangkan escape yang otomatis ditambahkan WordPress
                        break;

                    case 'api_url':
                        // Sanitasi URL
                        $sanitized[$key] = esc_url_raw($value);
                        break;

                    case 'enabled':
                    case 'test_completed':
                        // Konversi ke boolean
                        $sanitized[$key] = Security::sanitize_bool($value);
                        break;
                    
                    case 'form_id':
                    case 'entry_id':
                        // Konversi ke integer
                        $sanitized[$key] = Security::sanitize_int($value);
                        break;

                    default:
                        // Default sanitasi untuk field lainnya
                        $sanitized[$key] = Security::sanitize_text($value);
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Handle request AJAX
     */
    abstract public function handle_request();
}
