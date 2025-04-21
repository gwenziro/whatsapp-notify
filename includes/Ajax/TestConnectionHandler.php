<?php

/**
 * Test Connection AJAX Handler
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Ajax;

use WANotify\Api\ApiClient;
use WANotify\Logging\Logger;
use WANotify\Admin\GeneralSettings;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class TestConnectionHandler
 * 
 * Menangani AJAX request untuk tes koneksi
 */
class TestConnectionHandler extends AjaxHandler
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
        add_action('wp_ajax_wanotify_test_connection', [$this, 'test_connection']);
    }

    /**
     * AJAX handler untuk menguji koneksi WhatsApp
     */
    public function test_connection()
    {
        // Tambahkan logging untuk debug
        $this->logger->info("TestConnectionHandler::test_connection called", [
            'post_data' => isset($_POST) ? $_POST : 'empty'
        ]);

        try {
            // Verifikasi request
            if (!$this->verify_request()) {
                return;
            }

            // Cek kelengkapan konfigurasi
            $config_check = $this->general_settings->ensure_config_complete('test_connection');

            if (is_wp_error($config_check)) {
                $error_data = $config_check->get_error_data();
                $this->send_error(
                    $config_check->get_error_message(),
                    [
                        'redirect_url' => $error_data['redirect_url'],
                        'incomplete_config' => true
                    ]
                );
                return;
            }

            // Tes koneksi dengan error handling
            try {
                $result = $this->api->test_connection();

                // Kirim respons sesuai hasil
                if ($result['success']) {
                    $this->logger->info('Connection test successful', [
                        'user_id' => get_current_user_id()
                    ]);

                    $this->send_success([
                        'message' => __('Koneksi berhasil! Pesan tes telah dikirim.', 'whatsapp-notify')
                    ]);
                } else {
                    $this->logger->error('Connection test failed', [
                        'user_id' => get_current_user_id(),
                        'error' => $result['message']
                    ]);

                    $this->send_error(
                        __('Koneksi gagal: ', 'whatsapp-notify') . $result['message'],
                        $result['data'] ?? []
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error("Exception during connection test", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->send_error(__('Koneksi gagal: ', 'whatsapp-notify') . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception in test_connection", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->send_error(__('Terjadi kesalahan server: ', 'whatsapp-notify') . $e->getMessage());
        }
    }

    /**
     * Handle request AJAX (implementasi method abstrak)
     */
    public function handle_request()
    {
        // Method ini tidak digunakan karena handler terdaftar secara individual
    }
}
