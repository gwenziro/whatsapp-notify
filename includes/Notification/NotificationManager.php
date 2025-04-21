<?php

/**
 * Notification Manager Class
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Notification;

use WANotify\Logging\Logger;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NotificationManager
 * 
 * Mengelola pengiriman notifikasi dengan berbagai tipe
 */
class NotificationManager
{
    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Daftar handler notifikasi yang terdaftar
     *
     * @var array
     */
    protected $handlers = [];

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
     * Daftarkan handler notifikasi
     *
     * @param string $type Type notifikasi
     * @param object $handler Handler notifikasi
     * @return void
     */
    public function register_handler($type, $handler)
    {
        $this->handlers[$type] = $handler;
        $this->logger->info("Notification handler registered for type: $type");
    }

    /**
     * Kirim notifikasi
     *
     * @param string $type Type notifikasi
     * @param string $recipient Penerima notifikasi
     * @param string $message Pesan notifikasi
     * @param array $params Parameter tambahan
     * @return array Response dari handler
     */
    public function send($type, $recipient, $message, $params = [])
    {
        if (!isset($this->handlers[$type])) {
            $this->logger->error("No handler registered for notification type: $type");
            return [
                'success' => false,
                'message' => "No handler registered for notification type: $type"
            ];
        }

        $this->logger->info("Sending notification", [
            'type' => $type,
            'recipient' => $recipient
        ]);

        return $this->handlers[$type]->send($recipient, $message, $params);
    }

    /**
     * Dapatkan handler berdasarkan type
     *
     * @param string $type Type notifikasi
     * @return object|null Handler notifikasi atau null jika tidak ditemukan
     */
    public function get_handler($type)
    {
        return isset($this->handlers[$type]) ? $this->handlers[$type] : null;
    }
}
