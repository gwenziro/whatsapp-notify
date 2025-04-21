<?php

/**
 * GeneralSettings Controller
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Admin;

use WANotify\Api\ApiClient;
use WANotify\Core\Constants;
use WANotify\Logging\Logger;
use WANotify\Validation\Validator;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GeneralSettings
 * 
 * Menangani pengaturan umum plugin
 */
class GeneralSettings
{
    /**
     * API Client
     *
     * @var ApiClient
     */
    protected $api;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param ApiClient $api    API Client
     * @param Logger    $logger Logger
     */
    public function __construct(ApiClient $api, Logger $logger)
    {
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Render tab pengaturan umum
     */
    public function render()
    {
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, array());

        $api_url = isset($settings['api_url']) ? esc_url($settings['api_url']) : '';
        $default_recipient = isset($settings['default_recipient']) ? sanitize_text_field($settings['default_recipient']) : '';
        $default_template = isset($settings['default_template']) ? $settings['default_template'] : Constants::DEFAULT_TEMPLATE;
        $enable_logging = isset($settings['enable_logging']) ? (bool) $settings['enable_logging'] : false;
        $access_token = isset($settings['access_token']) ? sanitize_text_field($settings['access_token']) : '';

        // Include template
        include WANOTIFY_PLUGIN_DIR . 'templates/admin/general-settings.php';
    }

    /**
     * AJAX handler untuk menyimpan pengaturan umum
     */
    public function ajax_save_settings()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wanotify_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        $errors = [];

        // Validasi URL API
        $api_url = isset($_POST['api_url']) ? $_POST['api_url'] : '';
        $api_url_validation = Validator::validate_api_url($api_url);
        if (!$api_url_validation['is_valid']) {
            $errors['api_url'] = $api_url_validation['message'];
        }

        // Validasi Token Autentikasi
        $access_token = isset($_POST['access_token']) ? $_POST['access_token'] : '';
        $token_validation = Validator::validate_access_token($access_token);
        if (!$token_validation['is_valid']) {
            $errors['access_token'] = $token_validation['message'];
        }

        // Validasi Nomor WhatsApp Default
        $default_recipient = isset($_POST['default_recipient']) ? $_POST['default_recipient'] : '';
        $recipient_validation = Validator::validate_whatsapp_number($default_recipient);
        if (!$recipient_validation['is_valid']) {
            $errors['default_recipient'] = $recipient_validation['message'];
        }

        // Validasi Template Pesan Default
        $default_template = isset($_POST['default_template']) ? $_POST['default_template'] : '';
        $template_validation = Validator::validate_message_template($default_template);
        if (!$template_validation['is_valid']) {
            $errors['default_template'] = $template_validation['message'];
        }

        // Jika ada error, kirim respons error
        if (!empty($errors)) {
            wp_send_json_error([
                'message' => 'Harap perbaiki kesalahan berikut:',
                'errors' => $errors
            ]);
            return;
        }

        // Gunakan nilai yang sudah diformat
        $settings = [
            'api_url' => $api_url_validation['formatted'],
            'access_token' => $token_validation['formatted'],
            'default_recipient' => $recipient_validation['formatted'],
            'default_template' => $template_validation['formatted'],
            'enable_logging' => isset($_POST['enable_logging']) ? (bool) $_POST['enable_logging'] : false
        ];

        // Simpan pengaturan
        update_option(Constants::SETTINGS_OPTION_KEY, $settings);

        // Kirim respons sukses
        wp_send_json_success([
            'message' => 'Pengaturan berhasil disimpan!'
        ]);
    }

    /**
     * AJAX handler untuk menguji koneksi WhatsApp
     */
    public function ajax_test_connection()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wanotify_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        // Cek kelengkapan konfigurasi
        $config_check = $this->ensure_config_complete('test_connection');
        if (is_wp_error($config_check)) {
            $error_data = $config_check->get_error_data();
            wp_send_json_error([
                'message' => $config_check->get_error_message(),
                'redirect_url' => $error_data['redirect_url'],
                'incomplete_config' => true
            ]);
            return;
        }

        // Tes koneksi menggunakan API
        $result = $this->api->test_connection();

        // Kirim respons sesuai hasil
        if ($result['success']) {
            wp_send_json_success([
                'message' => 'Koneksi berhasil! Pesan tes telah dikirim.'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Koneksi gagal: ' . $result['message']
            ]);
        }
    }

    /**
     * AJAX handler untuk memeriksa kelengkapan konfigurasi
     */
    public function ajax_check_configuration()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wanotify_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        $config_status = $this->check_core_configuration();

        wp_send_json_success([
            'is_complete' => $config_status['is_complete'],
            'validation_results' => $config_status['validation_results'],
            'settings_url' => admin_url('admin.php?page=whatsapp-notify')
        ]);
    }

    /**
     * Memeriksa kelengkapan konfigurasi dasar
     * 
     * @return array Status dan detail konfigurasi yang belum lengkap
     */
    public function check_core_configuration()
    {
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
        $validation_results = [];
        $is_complete = true;

        // Validasi URL API
        if (empty($settings['api_url'])) {
            $validation_results['api_url'] = [
                'is_valid' => false,
                'message' => 'URL API Bot WhatsApp belum dikonfigurasi',
                'field_name' => 'URL API Bot WhatsApp'
            ];
            $is_complete = false;
        } else {
            $api_url_validation = Validator::validate_api_url($settings['api_url']);
            if (!$api_url_validation['is_valid']) {
                $validation_results['api_url'] = [
                    'is_valid' => false,
                    'message' => $api_url_validation['message'],
                    'field_name' => 'URL API Bot WhatsApp'
                ];
                $is_complete = false;
            }
        }

        // Validasi Token Autentikasi
        if (empty($settings['access_token'])) {
            $validation_results['access_token'] = [
                'is_valid' => false,
                'message' => 'Token Autentikasi belum dikonfigurasi',
                'field_name' => 'Token Autentikasi'
            ];
            $is_complete = false;
        } else {
            $token_validation = Validator::validate_access_token($settings['access_token']);
            if (!$token_validation['is_valid']) {
                $validation_results['access_token'] = [
                    'is_valid' => false,
                    'message' => $token_validation['message'],
                    'field_name' => 'Token Autentikasi'
                ];
                $is_complete = false;
            }
        }

        // Validasi Nomor Default
        if (empty($settings['default_recipient'])) {
            $validation_results['default_recipient'] = [
                'is_valid' => false,
                'message' => 'Nomor WhatsApp Default belum dikonfigurasi',
                'field_name' => 'Nomor WhatsApp Default'
            ];
            $is_complete = false;
        } else {
            $number_validation = Validator::validate_whatsapp_number($settings['default_recipient']);
            if (!$number_validation['is_valid']) {
                $validation_results['default_recipient'] = [
                    'is_valid' => false,
                    'message' => $number_validation['message'],
                    'field_name' => 'Nomor WhatsApp Default'
                ];
                $is_complete = false;
            }
        }

        // Validasi Template Default
        if (empty($settings['default_template'])) {
            $validation_results['default_template'] = [
                'is_valid' => false,
                'message' => 'Template Pesan Default belum dikonfigurasi',
                'field_name' => 'Template Pesan Default'
            ];
            $is_complete = false;
        } else {
            $template_validation = Validator::validate_message_template($settings['default_template']);
            if (!$template_validation['is_valid']) {
                $validation_results['default_template'] = [
                    'is_valid' => false,
                    'message' => $template_validation['message'],
                    'field_name' => 'Template Pesan Default'
                ];
                $is_complete = false;
            }
        }

        return [
            'is_complete' => $is_complete,
            'validation_results' => $validation_results
        ];
    }

    /**
     * Memastikan fitur hanya dapat diakses jika konfigurasi lengkap
     * 
     * @param string $action Nama aksi yang akan dijalankan 
     * @return bool|WP_Error True jika bisa dilanjutkan, WP_Error jika tidak
     */
    public function ensure_config_complete($action = '')
    {
        $config_status = $this->check_core_configuration();

        if (!$config_status['is_complete']) {
            $missing_fields = [];
            $first_missing = null;

            foreach ($config_status['validation_results'] as $field_key => $result) {
                if (!$result['is_valid']) {
                    $missing_fields[] = $result['field_name'];
                    if ($first_missing === null) {
                        $first_missing = $field_key;
                    }
                }
            }

            return new \WP_Error(
                'incomplete_config',
                'Pengaturan dasar belum lengkap. Silakan isi: ' . implode(', ', $missing_fields),
                [
                    'redirect_url' => admin_url('admin.php?page=whatsapp-notify&highlight=' . $first_missing),
                    'missing_fields' => $missing_fields
                ]
            );
        }

        return true;
    }
}
