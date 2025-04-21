<?php

/**
 * WhatsApp API Client
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Api;

use WANotify\Core\Constants;
use WANotify\Logging\Logger;
use WANotify\Utils\Formatter;
use WANotify\Utils\Security;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WhatsAppApiClient
 * 
 * Client untuk WhatsApp API
 */
class WhatsAppApiClient extends ApiClient
{
    /**
     * API URL setting yang di-cache
     *
     * @var string
     */
    private $api_url = null;

    /**
     * API token yang di-cache
     *
     * @var string
     */
    private $access_token = null;

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger)
    {
        parent::__construct($logger);

        // Cache pengaturan API
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
        if (!empty($settings['api_url'])) {
            $this->api_url = Security::sanitize_url($settings['api_url']);
        }
        if (!empty($settings['access_token'])) {
            $this->access_token = $settings['access_token'];
        }
    }

    /**
     * Kirim notifikasi WhatsApp
     *
     * @param string $recipient Nomor penerima
     * @param string $message Pesan notifikasi
     * @param int|null $form_id ID form (opsional)
     * @return array Response data
     */
    public function send_notification($recipient, $message, $form_id = null)
    {
        // Verifikasi pengaturan API
        if (!$this->verify_api_config()) {
            return $this->get_config_error_response();
        }

        // Validasi penerima
        if (empty($recipient)) {
            $this->logger->error('Nomor penerima kosong');
            return $this->format_response(false, 'Nomor penerima tidak boleh kosong');
        }

        // Pastikan format nomor benar
        $recipient = Formatter::phone_number($recipient);

        // Cek apakah setelah format masih kosong
        if (empty($recipient)) {
            $this->logger->error('Format nomor penerima tidak valid');
            return $this->format_response(false, 'Format nomor penerima tidak valid');
        }

        // Bangun URL endpoint lengkap dengan konstanta
        $endpoint_url = $this->api_url . '/' . Constants::ENDPOINT_PERSONAL;

        // Siapkan data untuk dikirim
        $data = [
            'nomorTujuan' => $recipient,
            'pesanNotifikasi' => $message
        ];

        // Tambahkan form ID jika ada
        if ($form_id) {
            $data['form_id'] = $form_id;
        }

        $this->logger->info('Mengirim notifikasi WhatsApp', [
            'recipient' => $recipient,
            'form_id' => $form_id,
            'endpoint' => $endpoint_url
        ]);

        // Kirim request ke API
        $response = $this->send_api_request($endpoint_url, $data);

        return $response;
    }

    /**
     * Kirim notifikasi grup WhatsApp
     *
     * @param string $group_id ID grup WhatsApp
     * @param string $message Pesan notifikasi
     * @param int|null $form_id ID form (opsional)
     * @return array Response data
     */
    public function send_group_notification($group_id, $message, $form_id = null)
    {
        // Verifikasi pengaturan API
        if (!$this->verify_api_config()) {
            return $this->get_config_error_response();
        }

        // Validasi ID grup
        if (empty($group_id)) {
            $this->logger->error('ID grup kosong');
            return $this->format_response(false, 'ID grup tidak boleh kosong');
        }

        // Bangun URL endpoint grup
        $endpoint_url = $this->api_url . '/' . Constants::ENDPOINT_GROUP;

        // Siapkan data untuk dikirim
        $data = [
            'groupId' => $group_id,
            'pesanNotifikasi' => $message
        ];

        // Tambahkan form ID jika ada
        if ($form_id) {
            $data['form_id'] = $form_id;
        }

        $this->logger->info('Mengirim notifikasi grup WhatsApp', [
            'group_id' => $group_id,
            'form_id' => $form_id,
            'endpoint' => $endpoint_url
        ]);

        // Kirim request ke API
        $response = $this->send_api_request($endpoint_url, $data);

        return $response;
    }

    /**
     * Ambil daftar grup WhatsApp
     *
     * @return array Response data dengan daftar grup
     */
    public function get_group_list()
    {
        // Verifikasi pengaturan API
        if (!$this->verify_api_config()) {
            return $this->get_config_error_response();
        }

        // Bangun URL endpoint daftar grup
        $endpoint_url = $this->api_url . '/' . Constants::ENDPOINT_GROUP_LIST;

        $this->logger->info('Mengambil daftar grup WhatsApp', [
            'endpoint' => $endpoint_url
        ]);

        // Kirim request GET ke API
        $response = wp_remote_get($endpoint_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-access-token' => $this->access_token
            ],
            'timeout' => 30
        ]);

        // Periksa error
        if (is_wp_error($response)) {
            $this->logger->error('Error saat mengambil daftar grup', [
                'error' => $response->get_error_message()
            ]);

            return $this->format_response(false, $response->get_error_message());
        }

        // Periksa response code
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200) {
            $this->logger->info('Daftar grup berhasil diambil', [
                'count' => isset($body['data']) ? count($body['data']) : 0
            ]);

            return $this->format_response(true, 'Daftar grup berhasil diambil', $body);
        } else {
            $this->log_error_response('mengambil daftar grup', $body, $status_code);

            return $this->format_response(
                false,
                'Error dari API WhatsApp: ' . $status_code,
                $body
            );
        }
    }

    /**
     * Tes koneksi dengan API
     *
     * @return array Response data
     */
    public function test_connection()
    {
        // Verifikasi pengaturan API
        if (!$this->verify_api_config()) {
            return $this->get_config_error_response();
        }

        // Ambil nomor default untuk uji koneksi
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
        $recipient = isset($settings['default_recipient']) ? $settings['default_recipient'] : '';

        if (empty($recipient)) {
            $this->logger->error('Test connection failed: Default recipient not set');
            return $this->format_response(false, 'Nomor tujuan default tidak dikonfigurasi');
        }

        $message = "🧪 *Ini adalah pesan tes dari WhatsApp Notify*\n\nJika Anda menerima pesan ini, berarti integrasi berfungsi dengan baik! 👍";

        // Log request details for debugging
        $this->logger->info('Testing connection with parameters', [
            'base_url' => $this->api_url,
            'recipient' => $recipient,
            'token_length' => strlen($this->access_token)
        ]);

        return $this->send_notification($recipient, $message);
    }

    /**
     * Kirim permintaan ke API WhatsApp
     *
     * @param string $endpoint_url URL endpoint
     * @param array $data Data yang akan dikirim
     * @return array Response data yang diformat
     */
    private function send_api_request($endpoint_url, $data)
    {
        // Tambahkan token sebagai parameter query jika URL tidak berisi parameter
        if (strpos($endpoint_url, '?') === false) {
            $endpoint_url .= '?token=' . urlencode($this->access_token);
        } else {
            $endpoint_url .= '&token=' . urlencode($this->access_token);
        }

        // Kirim request ke API dengan menambahkan token autentikasi
        $response = wp_remote_post($endpoint_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-access-token' => $this->access_token, // Tambahkan juga sebagai header
            ],
            'body' => json_encode($data),
            'timeout' => 30,
            'data_format' => 'body'
        ]);

        // Periksa error
        if (is_wp_error($response)) {
            $this->logger->error('Error saat mengirim request ke API', [
                'error' => $response->get_error_message()
            ]);

            return $this->format_response(false, $response->get_error_message());
        }

        // Periksa response code
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200) {
            $this->logger->info('Request API berhasil', [
                'response' => $body
            ]);

            return $this->format_response(true, 'Request berhasil', $body);
        } else {
            $this->log_error_response('mengirim request ke API', $body, $status_code);

            return $this->format_response(
                false,
                'Error dari API WhatsApp: ' . $status_code,
                $body
            );
        }
    }

    /**
     * Verifikasi konfigurasi API
     *
     * @return bool True jika konfigurasi lengkap, false jika tidak
     */
    private function verify_api_config()
    {
        // Ambil pengaturan API jika belum di-cache
        if (empty($this->api_url) || empty($this->access_token)) {
            $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
            $this->api_url = isset($settings['api_url']) ? Security::sanitize_url($settings['api_url']) : '';
            $this->access_token = isset($settings['access_token']) ? $settings['access_token'] : '';
        }

        return !empty($this->api_url) && !empty($this->access_token);
    }

    /**
     * Dapatkan respon error untuk konfigurasi yang tidak lengkap
     *
     * @return array Response data error
     */
    private function get_config_error_response()
    {
        if (empty($this->api_url)) {
            $this->logger->error('API URL tidak dikonfigurasi');
            return $this->format_response(false, 'API URL tidak dikonfigurasi');
        }

        if (empty($this->access_token)) {
            $this->logger->error('Token autentikasi tidak dikonfigurasi');
            return $this->format_response(false, 'Token autentikasi tidak dikonfigurasi');
        }

        return $this->format_response(false, 'Konfigurasi API tidak lengkap');
    }
}
