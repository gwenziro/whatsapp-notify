<?php

/**
 * Kelas Plugin utama
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Core;

use WANotify\Admin\AdminPage;
use WANotify\Api\WhatsAppApiClient;
use WANotify\Form\FormHandler;
use WANotify\Logging\Logger;
use WANotify\Notification\NotificationManager;
use WANotify\Notification\WhatsAppNotification;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 * 
 * Kontainer utama untuk plugin
 */
class Plugin
{
    /**
     * Instance singleton dari plugin
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Komponen logger
     *
     * @var Logger
     */
    private $logger;

    /**
     * Komponen API
     *
     * @var WhatsAppApiClient
     */
    private $api;

    /**
     * Komponen NotificationManager
     *
     * @var NotificationManager
     */
    private $notification_manager;

    /**
     * Komponen Admin
     *
     * @var AdminPage
     */
    private $admin;

    /**
     * Komponen Form Handler
     *
     * @var FormHandler
     */
    private $form_handler;

    /**
     * Mendapatkan instance singleton
     *
     * @return Plugin
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Inisialisasi komponen logger terlebih dahulu karena akan digunakan oleh komponen lain
        $this->logger = new Logger();

        // Inisialisasi API client
        $this->api = new WhatsAppApiClient($this->logger);

        // Inisialisasi notification manager
        $this->notification_manager = new NotificationManager($this->logger);

        // Daftarkan handler WhatsApp untuk notification manager
        $whatsapp_notification = new WhatsAppNotification($this->api, $this->logger);
        $this->notification_manager->register_handler('whatsapp', $whatsapp_notification);

        // Inisialisasi admin
        $this->admin = new AdminPage($this->api, $this->logger);

        // Inisialisasi form handler dengan notification manager
        $this->form_handler = new FormHandler($this->notification_manager, $this->logger);

        // Log inisialisasi plugin
        $this->logger->info('Plugin initialized', [
            'version' => WANOTIFY_VERSION
        ]);
    }

    /**
     * Mendapatkan komponen logger
     *
     * @return Logger
     */
    public function get_logger()
    {
        return $this->logger;
    }

    /**
     * Mendapatkan komponen API
     *
     * @return WhatsAppApiClient
     */
    public function get_api()
    {
        return $this->api;
    }

    /**
     * Mendapatkan komponen notification manager
     *
     * @return NotificationManager
     */
    public function get_notification_manager()
    {
        return $this->notification_manager;
    }

    /**
     * Mendapatkan komponen admin
     *
     * @return AdminPage
     */
    public function get_admin()
    {
        return $this->admin;
    }

    /**
     * Mendapatkan komponen form handler
     *
     * @return FormHandler
     */
    public function get_form_handler()
    {
        return $this->form_handler;
    }
}
