<?php

/**
 * Asset Loader Class
 *
 * @package WhatsApp_Notify
 * @subpackage Utils
 * @since 1.0.0
 */

namespace WANotify\Utils;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AssetLoader
 * 
 * Mengelola pendaftaran dan pemuatan asset (CSS dan JS)
 */
class AssetLoader
{
    /**
     * Plugin URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Plugin Version
     *
     * @var string
     */
    private $version;

    /**
     * Constructor
     *
     * @param string $plugin_url URL plugin
     * @param string $version    Versi plugin
     */
    public function __construct($plugin_url, $version)
    {
        $this->plugin_url = $plugin_url;
        $this->version = $version;
    }

    /**
     * Register assets loading hook
     */
    public function register()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Daftarkan asset CSS & JS
     *
     * @param string $hook Current admin page
     */
    public function enqueue_assets($hook)
    {
        // Hanya daftarkan di halaman plugin ini
        if (strpos($hook, 'whatsapp-notify') === false) {
            return;
        }

        // Daftarkan CSS
        wp_enqueue_style(
            'wanotify-admin-style',
            $this->plugin_url . 'assets/css/admin-style.css',
            array(),
            $this->version
        );

        // Daftarkan modul JS dasar
        $base_modules = array(
            'validator',
            'notifications',
            'form-utils',
            'ui-state'
        );

        // Daftarkan modul JS form-settings
        $form_modules = array(
            'form-validation',
            'test-handlers',
            'recipient-manager',
            'form-data-handler',
            'form-ui-manager',
            'form-settings'
        );

        // Daftarkan modul lainnya
        $other_modules = array(
            'form-toggle',
            'config-checker'
        );

        // Gabungkan semua modul
        $js_modules = array_merge($base_modules, $form_modules, $other_modules);

        // Daftarkan setiap modul dengan dependensi jQuery
        foreach ($js_modules as $module) {
            wp_enqueue_script(
                "wanotify-{$module}",
                $this->plugin_url . "assets/js/modules/{$module}.js",
                array('jquery'),
                $this->version,
                true
            );
        }

        // Daftarkan script utama dengan dependensi pada modul
        $dependencies = array_merge(['jquery'], array_map(function($module) {
            return "wanotify-{$module}";
        }, $js_modules));

        wp_enqueue_script(
            'wanotify-admin-script',
            $this->plugin_url . 'assets/js/admin-script.js',
            $dependencies,
            $this->version,
            true
        );

        // Berikan data untuk JS
        wp_localize_script('wanotify-admin-script', 'wanotify', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wanotify_admin_nonce'),
            'settings_url' => admin_url('admin.php?page=whatsapp-notify'),
            'i18n' => array(
                'success' => __('Berhasil!', 'whatsapp-notify'),
                'error' => __('Error!', 'whatsapp-notify'),
                'saving' => __('Menyimpan...', 'whatsapp-notify'),
                'testing' => __('Menguji koneksi...', 'whatsapp-notify'),
                'confirm_clear_logs' => __('Apakah Anda yakin ingin menghapus semua log?', 'whatsapp-notify'),
                'activating' => __('Mengaktifkan...', 'whatsapp-notify'),
                'deactivating' => __('Menonaktifkan...', 'whatsapp-notify'),
                'phone_field_required' => __('Opsi ini tidak tersedia karena tidak ada field telepon di formulir', 'whatsapp-notify'),
                'settings_auto_adjusted' => __('Pengaturan penerima notifikasi disesuaikan otomatis karena field telepon tidak tersedia lagi', 'whatsapp-notify'),
            )
        ));
    }
}
