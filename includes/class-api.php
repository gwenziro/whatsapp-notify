<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

class FluentWA_API {
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    /**
     * Kirim notifikasi WhatsApp
     */
    public function send_notification($recipient, $message, $form_id = null) {
        $settings = get_option('fluentwa_settings', array());
        $base_url = isset($settings['api_url']) ? FluentWA_Utils::sanitize_url($settings['api_url']) : '';
        $access_token = isset($settings['access_token']) ? $settings['access_token'] : '';
        
        if (empty($base_url)) {
            $this->logger->error('API URL tidak dikonfigurasi');
            return array(
                'success' => false,
                'message' => 'API URL tidak dikonfigurasi'
            );
        }
        
        if (empty($access_token)) {
            $this->logger->error('Token autentikasi tidak dikonfigurasi');
            return array(
                'success' => false,
                'message' => 'Token autentikasi tidak dikonfigurasi'
            );
        }
        
        // Validasi penerima
        if (empty($recipient)) {
            $this->logger->error('Nomor penerima kosong');
            return array(
                'success' => false,
                'message' => 'Nomor penerima tidak boleh kosong'
            );
        }
        
        // Pastikan format nomor benar
        $recipient = FluentWA_Utils::format_phone_number($recipient);
        
        // Cek apakah setelah format masih kosong
        if (empty($recipient)) {
            $this->logger->error('Format nomor penerima tidak valid');
            return array(
                'success' => false,
                'message' => 'Format nomor penerima tidak valid'
            );
        }
        
        // Bangun URL endpoint lengkap dengan konstanta
        $endpoint_url = $base_url . '/' . FluentWA_Constants::ENDPOINT_PERSONAL;
        
        // Siapkan data untuk dikirim
        $data = array(
            'nomorTujuan' => $recipient,
            'pesanNotifikasi' => $message
        );
        
        // Tambahkan form ID jika ada
        if ($form_id) {
            $data['form_id'] = $form_id;
        }
        
        $this->logger->info('Mengirim notifikasi WhatsApp', array(
            'recipient' => $recipient,
            'form_id' => $form_id,
            'endpoint' => $endpoint_url
        ));
        
        // Tambahkan token sebagai parameter query jika URL tidak berisi parameter
        if (strpos($endpoint_url, '?') === false) {
            $endpoint_url .= '?token=' . urlencode($access_token);
        } else {
            $endpoint_url .= '&token=' . urlencode($access_token);
        }
        
        // Kirim request ke API dengan menambahkan token autentikasi
        $response = wp_remote_post($endpoint_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-access-token' => $access_token, // Tambahkan juga sebagai header
            ),
            'body' => json_encode($data),
            'timeout' => 30,
            'data_format' => 'body'
        ));
        
        // Periksa error
        if (is_wp_error($response)) {
            $this->logger->error('Error saat mengirim notifikasi', array(
                'error' => $response->get_error_message()
            ));
            
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        // Periksa response code
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 200) {
            $this->logger->info('Notifikasi berhasil dikirim', array(
                'response' => $body
            ));
            
            return array(
                'success' => true,
                'message' => 'Notifikasi berhasil dikirim',
                'data' => $body
            );
        } else {
            $this->logger->error('Error dari API WhatsApp', array(
                'status' => $status_code,
                'response' => $body
            ));
            
            return array(
                'success' => false,
                'message' => 'Error dari API WhatsApp: ' . $status_code,
                'data' => $body
            );
        }
    }
    
    /**
     * Tes koneksi dengan API
     */
    public function test_connection() {
        $settings = get_option('fluentwa_settings', array());
        $base_url = isset($settings['api_url']) ? FluentWA_Utils::sanitize_url($settings['api_url']) : '';
        $recipient = isset($settings['default_recipient']) ? $settings['default_recipient'] : '';
        $access_token = isset($settings['access_token']) ? $settings['access_token'] : '';
        
        if (empty($base_url) || empty($recipient)) {
            $this->logger->error('Test connection failed: API URL or recipient missing');
            return array(
                'success' => false,
                'message' => 'API URL atau nomor tujuan tidak dikonfigurasi'
            );
        }
        
        if (empty($access_token)) {
            $this->logger->error('Test connection failed: Access token missing');
            return array(
                'success' => false,
                'message' => 'Token autentikasi tidak dikonfigurasi'
            );
        }
        
        $message = "🧪 *Ini adalah pesan tes dari Fluent WhatsApp Notifier*\n\nJika Anda menerima pesan ini, berarti integrasi berfungsi dengan baik! 👍";
        
        // Log request details for debugging
        $this->logger->info('Testing connection with parameters', [
            'base_url' => $base_url,
            'recipient' => $recipient,
            'token_length' => strlen($access_token)
        ]);
        
        return $this->send_notification($recipient, $message);
    }
}