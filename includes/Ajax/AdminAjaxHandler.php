<?php

/**
 * Admin AJAX Handler
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Ajax;

use WANotify\Admin\GeneralSettings;
use WANotify\Api\ApiClient;
use WANotify\Core\Constants;
use WANotify\Logging\Logger;
use WANotify\Validation\Validator;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminAjaxHandler
 * 
 * Menangani AJAX request dari admin panel
 */
class AdminAjaxHandler extends AjaxHandler
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
     * GeneralSettings instance
     *
     * @var GeneralSettings
     */
    protected $general_settings;

    /**
     * Constructor
     *
     * @param ApiClient $api API Client
     * @param Logger $logger Logger
     * @param GeneralSettings $general_settings GeneralSettings
     */
    public function __construct(ApiClient $api, Logger $logger, GeneralSettings $general_settings)
    {
        $this->api = $api;
        $this->logger = $logger;
        $this->general_settings = $general_settings;

        // Register AJAX handlers
        add_action('wp_ajax_wanotify_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_wanotify_check_configuration', [$this, 'check_configuration']);
    }

    /**
     * AJAX handler untuk menyimpan pengaturan umum
     */
    public function save_settings()
    {
        // Verifikasi request
        $data = $this->verify_request();
        if (!$data) {
            return;
        }

        $errors = [];

        // Validasi Token Autentikasi
        $access_token = isset($data['access_token']) ? $data['access_token'] : '';
        $token_validation = Validator::validate_access_token($access_token);

        if (!$token_validation['is_valid']) {
            $errors['access_token'] = $token_validation['message'];
        }

        // Validasi Nomor WhatsApp Default
        $default_recipient = isset($data['default_recipient']) ? $data['default_recipient'] : '';
        $recipient_validation = Validator::validate_whatsapp_number($default_recipient);

        if (!$recipient_validation['is_valid']) {
            $errors['default_recipient'] = $recipient_validation['message'];
        }

        // Validasi Template Pesan Default
        $default_template = isset($data['default_template']) ? $data['default_template'] : '';
        $template_validation = Validator::validate_message_template($default_template);

        if (!$template_validation['is_valid']) {
            $errors['default_template'] = $template_validation['message'];
        }

        // Jika ada error, kirim respons error
        if (!empty($errors)) {
            $this->send_error(
                __('Harap perbaiki kesalahan berikut:', 'whatsapp-notify'),
                ['errors' => $errors]
            );
            return;
        }

        // Gunakan nilai yang sudah diformat
        $settings = [
            'access_token' => $token_validation['formatted'],
            'default_recipient' => $recipient_validation['formatted'],
            'default_template' => $template_validation['formatted'],
            'enable_logging' => isset($data['enable_logging']) ? (bool) $data['enable_logging'] : false
        ];

        // Simpan pengaturan
        update_option(Constants::SETTINGS_OPTION_KEY, $settings);

        // Log tindakan
        $this->logger->info('General settings updated by admin', [
            'user_id' => get_current_user_id()
        ]);

        // Kirim respons sukses
        $this->send_success([
            'message' => __('Pengaturan berhasil disimpan!', 'whatsapp-notify')
        ]);
    }

    /**
     * AJAX handler untuk memeriksa kelengkapan konfigurasi
     */
    public function check_configuration()
    {
        // Verifikasi request
        if (!$this->verify_request()) {
            return;
        }

        $config_status = $this->general_settings->check_core_configuration();

        $this->send_success([
            'is_complete' => $config_status['is_complete'],
            'validation_results' => $config_status['validation_results'],
            'settings_url' => admin_url('admin.php?page=whatsapp-notify')
        ]);
    }

    /**
     * Handle request AJAX (implementasi method abstrak)
     */
    public function handle_request()
    {
        // Method ini tidak digunakan karena handler terdaftar secara individual
    }
}
