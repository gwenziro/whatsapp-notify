<?php

/**
 * Logs AJAX Handler
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Ajax;

use WANotify\Logging\Logger;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class LogsAjaxHandler
 * 
 * Menangani AJAX request terkait logs
 */
class LogsAjaxHandler extends AjaxHandler
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

        // Register AJAX handler
        add_action('wp_ajax_wanotify_clear_logs', [$this, 'clear_logs']);
    }

    /**
     * AJAX handler untuk membersihkan log
     */
    public function clear_logs()
    {
        // Verifikasi request
        if (!$this->verify_request()) {
            return;
        }

        // Bersihkan log
        $result = $this->logger->clear_logs();

        // Log tindakan (ke log baru)
        $this->logger->info('Logs cleared by admin', [
            'user_id' => get_current_user_id()
        ]);

        // Kirim respons
        if ($result) {
            $this->send_success([
                'message' => __('Log berhasil dibersihkan.', 'whatsapp-notify')
            ]);
        } else {
            $this->send_error(__('Gagal membersihkan log.', 'whatsapp-notify'));
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
