<?php

/**
 * Kelas Bootstrap
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Core;

use WANotify\Ajax\AdminAjaxHandler;
use WANotify\Ajax\FormAjaxHandler;
use WANotify\Ajax\LogsAjaxHandler;
use WANotify\Ajax\TestConnectionHandler;
use WANotify\Logging\Logger;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Bootstrap
 * 
 * Menangani proses inisialisasi plugin
 */
class Bootstrap
{
    /**
     * Inisialisasi plugin
     */
    public static function init()
    {
        // Cek jika Fluent Forms sudah diinstall dan diaktifkan
        if (!defined('FLUENTFORM')) {
            add_action('admin_notices', [__CLASS__, 'missing_fluentform_notice']);
            return;
        }

        // Inisialisasi plugin
        $plugin = Plugin::get_instance();

        // Inisialisasi AJAX handlers
        self::init_ajax_handlers($plugin);

        // Load translations
        self::load_textdomain();
    }

    /**
     * Inisialisasi AJAX handlers - FIXED WITH ERROR HANDLING
     * 
     * @param Plugin $plugin Plugin instance
     */
    private static function init_ajax_handlers($plugin)
    {
        try {
            // Admin AJAX Handler
            new AdminAjaxHandler(
                $plugin->get_api(),
                $plugin->get_logger(),
                $plugin->get_admin()->get_general_settings()
            );

            // Form AJAX Handler - Pastikan NotificationManager sudah diinisialisasi
            $notification_manager = $plugin->get_notification_manager();
            if (!$notification_manager) {
                throw new \Exception("NotificationManager is not initialized");
            }

            new FormAjaxHandler(
                $notification_manager,
                $plugin->get_logger(),
                $plugin->get_admin()
            );

            // Logs AJAX Handler
            new LogsAjaxHandler($plugin->get_logger());

            // Test Connection AJAX Handler
            new TestConnectionHandler(
                $plugin->get_api(),
                $plugin->get_logger(),
                $plugin->get_admin()->get_general_settings()
            );
        } catch (\Exception $e) {
            // Log error sehingga kita bisa melihat masalah pada inisialisasi
            error_log('WhatsApp Notify initialization error: ' . $e->getMessage());

            if (is_admin()) {
                add_action('admin_notices', function () use ($e) {
                    echo '<div class="notice notice-error"><p>';
                    echo 'WhatsApp Notify initialization error: ' . esc_html($e->getMessage());
                    echo '</p></div>';
                });
            }
        }
    }

    /**
     * Aktivasi plugin
     */
    public static function activate()
    {
        // Buat direktori log jika diperlukan
        $log_dir = WANOTIFY_PLUGIN_DIR . 'logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // Buat file .htaccess untuk mencegah akses langsung ke log
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
        }

        // Tambahkan opsi default jika belum ada
        if (!get_option(Constants::SETTINGS_OPTION_KEY)) {
            add_option(Constants::SETTINGS_OPTION_KEY, [
                'api_url' => '',
                'access_token' => '',
                'default_recipient' => '',
                'default_template' => Constants::DEFAULT_TEMPLATE
            ]);
        }

        // Lakukan upgrade dari versi sebelumnya jika perlu
        self::upgrade_from_legacy();
    }

    /**
     * Upgrade dari versi lama ke versi baru
     */
    private static function upgrade_from_legacy()
    {
        // Cek apakah ada pengaturan lama (fluentwa_settings)
        $old_settings = get_option('fluentwa_settings', null);
        $new_settings = get_option(Constants::SETTINGS_OPTION_KEY, null);

        // Jika ada pengaturan lama dan belum ada yang baru
        if ($old_settings && empty($new_settings)) {
            // Salin pengaturan
            update_option(Constants::SETTINGS_OPTION_KEY, $old_settings);

            // Log aktivitas
            $logger = new Logger();
            $logger->info('Upgraded from legacy settings', [
                'old_settings_key' => 'fluentwa_settings',
                'new_settings_key' => Constants::SETTINGS_OPTION_KEY
            ]);
        }

        // Cek pengaturan formulir lama
        global $wpdb;
        $old_form_settings = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'fluentwa_form_settings_%'"
        );

        if (!empty($old_form_settings)) {
            foreach ($old_form_settings as $option) {
                // Ekstrak form ID dari nama opsi
                $form_id = str_replace('fluentwa_form_settings_', '', $option->option_name);

                // Buat nama opsi baru
                $new_option_name = Constants::FORM_SETTINGS_PREFIX . $form_id;

                // Salin nilai jika opsi baru belum ada
                if (!get_option($new_option_name)) {
                    $option_value = maybe_unserialize($option->option_value);
                    update_option($new_option_name, $option_value);
                }
            }

            // Log aktivitas
            if (isset($logger)) {
                $logger->info('Upgraded legacy form settings', [
                    'count' => count($old_form_settings)
                ]);
            } else {
                $logger = new Logger();
                $logger->info('Upgraded legacy form settings', [
                    'count' => count($old_form_settings)
                ]);
            }
        }
    }

    /**
     * Deaktivasi plugin
     */
    public static function deactivate()
    {
        // Pembersihan saat plugin dinonaktifkan
    }

    /**
     * Load textdomain untuk translasi
     */
    public static function load_textdomain()
    {
        load_plugin_textdomain(
            'whatsapp-notify',
            false,
            dirname(plugin_basename(WANOTIFY_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Tampilkan pemberitahuan ketika Fluent Forms tidak terinstall
     */
    public static function missing_fluentform_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php _e('WhatsApp Notify memerlukan plugin Fluent Forms untuk bekerja. Silakan install dan aktifkan Fluent Forms terlebih dahulu.', 'whatsapp-notify'); ?></p>
        </div>
<?php
    }
}
