<?php

/**
 * FormSettings Controller
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Admin;

use WANotify\Api\ApiClient;
use WANotify\Core\Constants;
use WANotify\Form\FormData;
use WANotify\Form\FormSettingsManager;
use WANotify\Logging\Logger;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FormSettings
 * 
 * Menangani pengaturan formulir di admin area
 */
class FormSettings
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
     * Constructor
     *
     * @param ApiClient $api    API Client
     * @param Logger    $logger Logger
     */
    public function __construct(ApiClient $api, Logger $logger)
    {
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Render tab pengaturan formulir
     */
    public function render()
    {
        // Ambil form ID dari URL jika ada
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

        if ($form_id > 0) {
            $this->render_form_config($form_id);
        } else {
            $this->render_forms_list();
        }
    }

    /**
     * Render daftar formulir
     */
    private function render_forms_list()
    {
        // Ambil daftar formulir
        $forms = FormSettingsManager::get_all_forms();

        include WANOTIFY_PLUGIN_DIR . 'templates/admin/form-list.php';
    }

    /**
     * Render konfigurasi untuk formulir tertentu
     * 
     * @param int $form_id ID formulir
     */
    private function render_form_config($form_id)
    {
        // Ambil data formulir dari database
        $form = $this->get_form_data($form_id);

        if (!$form) {
            echo '<div class="notice notice-error"><p>' . __('Formulir tidak ditemukan.', 'whatsapp-notify') . '</p></div>';
            return;
        }

        $form_title = $form->title;
        $form_settings = FormSettingsManager::get_form_settings($form_id);

        // Inisialisasi FormData untuk mendapatkan field formulir
        $form_data = new FormData($form_id);
        $form_fields = $form_data->get_all_fields();
        $phone_fields = $form_data->get_phone_fields();

        // Ambil pengaturan umum
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);

        include WANOTIFY_PLUGIN_DIR . 'templates/admin/form-config.php';
    }

    /**
     * Ambil data formulir dari database
     * 
     * @param int $form_id ID formulir
     * @return object|null Data formulir atau null jika tidak ditemukan
     */
    private function get_form_data($form_id)
    {
        if (!function_exists('wpFluent')) {
            return null;
        }

        return wpFluent()->table('fluentform_forms')->find($form_id);
    }
}
