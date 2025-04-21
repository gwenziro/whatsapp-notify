<?php

/**
 * Admin Page Controller
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Admin;

use WANotify\Api\ApiClient;
use WANotify\Core\Constants;
use WANotify\Logging\Logger;
use WANotify\Validation\Validator;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminPage
 * 
 * Kelas utama untuk menangani halaman admin
 */
class AdminPage
{
    /**
     * API Client
     *
     * @var ApiClient
     */
    protected $api;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Tab aktif
     *
     * @var string
     */
    protected $active_tab;

    /**
     * Instance GeneralSettings
     * 
     * @var GeneralSettings
     */
    protected $general_settings;

    /**
     * Instance FormSettings
     * 
     * @var FormSettings
     */
    protected $form_settings;

    /**
     * Instance LogViewer
     * 
     * @var LogViewer
     */
    protected $log_viewer;

    /**
     * Instance HelpPage
     * 
     * @var HelpPage
     */
    protected $help_page;

    /**
     * Constructor
     *
     * @param ApiClient $api    API Client
     * @param Logger    $logger Logger
     */
    public function __construct(ApiClient $api, Logger $logger)
    {
        $this->api = $api;
        $this->logger = $logger;

        // Inisialisasi sub-controller
        $this->general_settings = new GeneralSettings($api, $logger);
        $this->form_settings = new FormSettings($api, $logger);
        $this->log_viewer = new LogViewer($logger);
        $this->help_page = new HelpPage();

        // Tambahkan menu admin
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Daftarkan assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Tambahkan menu admin ke dashboard
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('WhatsApp Notify', 'whatsapp-notify'),
            __('WA Notify', 'whatsapp-notify'),
            'manage_options',
            'whatsapp-notify',
            array($this, 'render_admin_page'),
            'dashicons-whatsapp',
            30
        );
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
            WANOTIFY_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            WANOTIFY_VERSION
        );

        // Daftarkan JS
        wp_enqueue_script(
            'wanotify-admin-script',
            WANOTIFY_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            WANOTIFY_VERSION,
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

    /**
     * Register Ajax handlers
     */
    protected function register_ajax_handlers()
    {
        // Hapus register ajax handlers di sini karena sudah di-handle oleh Bootstrap.php
        // untuk mencegah duplikasi dan potensi konflik
    }

    /**
     * Render halaman admin
     */
    public function render_admin_page()
    {
        // Ambil tab aktif
        $this->active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        // Template utama
        include WANOTIFY_PLUGIN_DIR . 'templates/admin/main.php';
    }

    /**
     * Render konten tab sesuai tab yang aktif
     */
    public function render_tab_content()
    {
        switch ($this->active_tab) {
            case 'general':
                $this->general_settings->render();
                break;

            case 'form_settings':
                $this->form_settings->render();
                break;

            case 'logs':
                $this->log_viewer->render();
                break;

            case 'help':
                $this->help_page->render();
                break;

            default:
                $this->general_settings->render();
                break;
        }
    }

    /**
     * Memeriksa kelengkapan konfigurasi dasar
     * 
     * @return array Status dan detail konfigurasi yang belum lengkap
     */
    public function check_core_configuration()
    {
        return $this->general_settings->check_core_configuration();
    }

    /**
     * Memastikan fitur hanya dapat diakses jika konfigurasi lengkap
     * 
     * @param string $action Nama aksi yang akan dijalankan 
     * @return bool|WP_Error True jika bisa dilanjutkan, WP_Error jika tidak
     */
    public function ensure_config_complete($action = '')
    {
        return $this->general_settings->ensure_config_complete($action);
    }

    /**
     * Mendapatkan komponen GeneralSettings
     *
     * @return GeneralSettings
     */
    public function get_general_settings()
    {
        return $this->general_settings;
    }

    /**
     * Mendapatkan komponen FormSettings
     *
     * @return FormSettings
     */
    public function get_form_settings()
    {
        return $this->form_settings;
    }

    /**
     * Mendapatkan komponen LogViewer
     *
     * @return LogViewer
     */
    public function get_log_viewer()
    {
        return $this->log_viewer;
    }

    /**
     * Mendapatkan komponen HelpPage
     *
     * @return HelpPage
     */
    public function get_help_page()
    {
        return $this->help_page;
    }
}
