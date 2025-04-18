<?php
/**
 * Plugin Name: Fluent Forms WhatsApp Notifier
 * Description: Kirim notifikasi WhatsApp saat formulir Fluent Forms disubmit
 * Version: 1.0.0
 * Author: Exernia
 * Text Domain: fluent-whatsapp-notifier
 * Domain Path: /languages
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

// Definisi konstanta plugin
define('FLUENTWA_VERSION', '1.0.0');
define('FLUENTWA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLUENTWA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLUENTWA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Fungsi yang berjalan saat plugin diaktifkan - DENGAN PENGECEKAN UNTUK MENCEGAH REDECLARATION
if (!function_exists('fluentwa_activate')) {
    function fluentwa_activate() {
        // Buat tabel kustom jika diperlukan
        // Tambahkan opsi default jika belum ada
        if (!get_option('fluentwa_settings')) {
            add_option('fluentwa_settings', array(
                'api_url' => '',
                'access_token' => '',
                'default_recipient' => '',
                'default_template' => "🔔 *Ada pengisian formulir baru!*\n\nFormulir: {form_name}\nWaktu: {submission_date}\n\n{form_data}"
            ));
        }
        
        // Buat direktori log jika diperlukan
        $log_dir = FLUENTWA_PLUGIN_DIR . 'logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // Buat file .htaccess untuk mencegah akses langsung ke log
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
        }
    }
}
register_activation_hook(__FILE__, 'fluentwa_activate');

// Fungsi yang berjalan saat plugin dideaktivasi - DENGAN PENGECEKAN
if (!function_exists('fluentwa_deactivate')) {
    function fluentwa_deactivate() {
        // Pembersihan saat plugin dinonaktifkan
    }
}
register_deactivation_hook(__FILE__, 'fluentwa_deactivate');

// Muat file yang diperlukan
require_once FLUENTWA_PLUGIN_DIR . 'includes/constants.php';
require_once FLUENTWA_PLUGIN_DIR . 'includes/utils.php';
require_once FLUENTWA_PLUGIN_DIR . 'includes/class-validator.php';
require_once FLUENTWA_PLUGIN_DIR . 'includes/class-admin.php';
require_once FLUENTWA_PLUGIN_DIR . 'includes/class-api.php';
require_once FLUENTWA_PLUGIN_DIR . 'includes/class-form-handler.php';
require_once FLUENTWA_PLUGIN_DIR . 'includes/class-logger.php';

// Inisialisasi plugin - DENGAN PENGECEKAN
if (!function_exists('fluentwa_init')) {
    function fluentwa_init() {
        // Cek jika Fluent Forms sudah diinstall dan diaktifkan
        if (!defined('FLUENTFORM')) {
            add_action('admin_notices', 'fluentwa_missing_fluentform_notice');
            return;
        }
        
        // Inisialisasi komponen plugin
        $logger = new FluentWA_Logger();
        $api = new FluentWA_API($logger);
        new FluentWA_Form_Handler($api, $logger);
        new FluentWA_Admin($api, $logger);
        
        // Load translations
        load_plugin_textdomain('fluent-whatsapp-notifier', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}
add_action('plugins_loaded', 'fluentwa_init');

// Tampilkan pemberitahuan jika Fluent Forms tidak terinstall - DENGAN PENGECEKAN
if (!function_exists('fluentwa_missing_fluentform_notice')) {
    function fluentwa_missing_fluentform_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Fluent Forms WhatsApp Notifier memerlukan plugin Fluent Forms untuk bekerja. Silakan install dan aktifkan Fluent Forms terlebih dahulu.', 'fluent-whatsapp-notifier'); ?></p>
        </div>
        <?php
    }
}