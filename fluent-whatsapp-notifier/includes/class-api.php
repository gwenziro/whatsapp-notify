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
    $api_url = isset($settings['api_url']) ? esc_url_raw($settings['api_url']) : '';
    
    if (empty($api_url)) {
        $this->logger->error('API URL tidak dikonfigurasi');
        return array(
            'success' => false,
            'message' => 'API URL tidak dikonfigurasi'
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
    $recipient = $this->format_phone_number($recipient);
    
    // Cek apakah setelah format masih kosong
    if (empty($recipient)) {
        $this->logger->error('Format nomor penerima tidak valid');
        return array(
            'success' => false,
            'message' => 'Format nomor penerima tidak valid'
        );
    }
    
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
        'form_id' => $form_id
    ));
    
    // Kirim request ke API
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
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
     * * Format nomor telepon ke format internasional
     */
     private function format_phone_number($number) {
    // Handle if number is an array
    if (is_array($number)) {
        // Use first value from array or empty string if array is empty
        $number = !empty($number) ? reset($number) : '';
        $this->logger->info('Nomor telepon berbentuk array, menggunakan nilai: ' . $number);
    }
    
    // Ensure it's a string
    $number = (string)$number;
    
    // Bersihkan nomor dari karakter khusus
    $number = preg_replace('/[^0-9+]/', '', $number);
    
    // Skip empty numbers
    if (empty($number)) {
        return '';
    }
    
    // Jika nomor dimulai dengan 0, ganti dengan kode negara Indonesia
    if (substr($number, 0, 1) === '0') {
        $number = '62' . substr($number, 1);
    }
    
    // Jika belum ada tanda +, tambahkan
    if (substr($number, 0, 1) !== '+') {
        $number = '+' . $number;
    }
    
    return $number;
         
     }
    
    /**
     * Tes koneksi dengan API
     */
    public function test_connection() {
        $settings = get_option('fluentwa_settings', array());
        $api_url = isset($settings['api_url']) ? esc_url_raw($settings['api_url']) : '';
        $recipient = isset($settings['default_recipient']) ? $settings['default_recipient'] : '';
        
        if (empty($api_url) || empty($recipient)) {
            return array(
                'success' => false,
                'message' => 'API URL atau nomor tujuan tidak dikonfigurasi'
            );
        }
        
        $message = "🧪 *Ini adalah pesan tes dari Fluent WhatsApp Notifier*\n\nJika Anda menerima pesan ini, berarti integrasi berfungsi dengan baik! 👍";
        
        return $this->send_notification($recipient, $message);
    }
}