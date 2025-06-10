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
     * Pelacak handler yang sudah terdaftar (statis untuk bertahan antar request)
     *
     * @var array
     */
    private static $registered_handlers = [];

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
     * @param bool $log_registration Apakah akan mencatat registrasi ke log
     * @return void
     */
    public function register_handler($type, $handler, $log_registration = true)
    {
        $this->handlers[$type] = $handler;
        
        // Hanya log jika tipe handler belum pernah terdaftar dalam sesi ini
        // dan log_registration diset ke true
        if ($log_registration && !isset(self::$registered_handlers[$type])) {
            $this->logger->info("Notification handler registered for type: $type", [], 'initialization');
            self::$registered_handlers[$type] = true;
        }
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
