<?php

/**
 * LogViewer Controller
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Admin;

use WANotify\Logging\Logger;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class LogViewer
 * 
 * Menangani tampilan log aktivitas
 */
class LogViewer
{
    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Logger $logger Logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Render tab log aktivitas
     */
    public function render()
    {
        $logs = $this->logger->get_logs();

        // Include template
        include WANOTIFY_PLUGIN_DIR . 'templates/admin/logs.php';
    }

    /**
     * AJAX handler untuk membersihkan log
     */
    public function ajax_clear_logs()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wanotify_admin_nonce')) {
            wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
        }

        // Bersihkan log
        $result = $this->logger->clear_logs();

        // Kirim respons
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Log berhasil dibersihkan.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Gagal membersihkan log.'
            ));
        }
    }
}
