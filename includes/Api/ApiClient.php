<?php

/**
 * Abstract API Client
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Api;

use WANotify\Logging\Logger;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ApiClient
 * 
 * Basis kelas untuk API clients
 */
abstract class ApiClient
{
    /**
     * Logger instance
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Kirim notifikasi ke penerima
     *
     * @param string $recipient Nomor penerima
     * @param string $message Pesan notifikasi 
     * @param int|null $form_id ID form (opsional)
     * @return array Response data
     */
    abstract public function send_notification($recipient, $message, $form_id = null);

    /**
     * Test koneksi API
     *
     * @return array Response data
     */
    abstract public function test_connection();

    /**
     * Log response error untuk debugging
     *
     * @param string $action Aksi yang dilakukan
     * @param mixed $response Response data
     * @param int $status_code HTTP status code
     * @return void
     */
    protected function log_error_response($action, $response, $status_code = null)
    {
        $this->logger->error("Error during {$action}", [
            'status_code' => $status_code,
            'response' => $response
        ]);
    }

    /**
     * Format response untuk konsistensi
     *
     * @param bool $success Status sukses
     * @param string $message Pesan
     * @param array $data Data tambahan (opsional)
     * @return array Formatted response array
     */
    protected function format_response($success, $message, $data = [])
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
    }
}
