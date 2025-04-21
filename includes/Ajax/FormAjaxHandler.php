<?php

/**
 * Form Settings AJAX Handler
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Ajax;

use WANotify\Admin\AdminPage;
use WANotify\Core\Constants;
use WANotify\Form\FormData;
use WANotify\Form\FormSettingsManager;
use WANotify\Logging\Logger;
use WANotify\Notification\NotificationManager;
use WANotify\Validation\Validator;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FormAjaxHandler
 * 
 * Menangani AJAX request terkait formulir
 */
class FormAjaxHandler extends AjaxHandler
{
    /**
     * Notification Manager
     *
     * @var NotificationManager
     */
    protected $notification_manager;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * AdminPage instance
     *
     * @var AdminPage
     */
    protected $admin_page;

    /**
     * Constructor
     *
     * @param NotificationManager $notification_manager Notification Manager
     * @param Logger     $logger     Logger
     * @param AdminPage  $admin_page AdminPage
     */
    public function __construct(NotificationManager $notification_manager, Logger $logger, AdminPage $admin_page)
    {
        $this->notification_manager = $notification_manager;
        $this->logger = $logger;
        $this->admin_page = $admin_page;

        // Register AJAX handlers
        add_action('wp_ajax_wanotify_save_form_settings', [$this, 'save_form_settings']);
        add_action('wp_ajax_wanotify_test_form_notification', [$this, 'test_form_notification']);
        add_action('wp_ajax_wanotify_toggle_form_status', [$this, 'toggle_form_status']);
        add_action('wp_ajax_wanotify_get_forms_status', [$this, 'get_forms_status']);
        add_action('wp_ajax_wanotify_auto_adjust_form_settings', [$this, 'auto_adjust_form_settings']);
    }

    /**
     * AJAX handler untuk menyimpan pengaturan formulir
     */
    public function save_form_settings()
    {
        // Tambahkan logging untuk debug yang lebih detail
        $this->logger->info("FormAjaxHandler::save_form_settings called", [
            'post_data' => isset($_POST) ? $_POST : 'empty',
            'headers' => getallheaders(),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3) // Tambahkan backtrace untuk debugging
        ]);

        // Verifikasi request dengan metode alternatif
        try {
            // Verifikasi nonce secara manual untuk debugging
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wanotify_admin_nonce')) {
                $this->logger->error("Nonce verification failed", [
                    'nonce' => isset($_POST['nonce']) ? $_POST['nonce'] : 'missing'
                ]);
                $this->send_error(__('Permintaan tidak valid atau kedaluwarsa', 'whatsapp-notify'));
                return;
            }

            // Proses data dengan error handling yang kuat
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

            // Pastikan format data boolean konsisten
            $enabled = isset($_POST['enabled']) ?
                ($_POST['enabled'] === '1' || $_POST['enabled'] === 'true' || $_POST['enabled'] === true) : false;

            $recipient_mode = isset($_POST['recipient_mode']) ? sanitize_text_field($_POST['recipient_mode']) : 'default';

            // Logging untuk debugging
            $this->logger->info("Processing form settings", [
                'form_id' => $form_id,
                'enabled' => $enabled,
                'recipient_mode' => $recipient_mode
            ]);

            // Validasi form ID
            if ($form_id <= 0) {
                $this->send_error(__('ID formulir tidak valid.', 'whatsapp-notify'));
                return;
            }

            // Validasi recipient mode
            $errors = [];

            // Jika mode adalah manual, validasi nomor kustom
            if ($recipient_mode === Constants::RECIPIENT_MODE_MANUAL) {
                $recipient = isset($_POST['recipient']) ? $_POST['recipient'] : '';
                $recipient_validation = Validator::validate_whatsapp_number($recipient);

                if (!$recipient_validation['is_valid']) {
                    $errors['recipient'] = $recipient_validation['message'];
                } else {
                    $recipient = $recipient_validation['formatted']; // Gunakan nilai yang sudah diformat
                }
            } else {
                $recipient = $_POST['recipient'] ?? '';
            }

            // Jika mode adalah dynamic, pastikan ada field telepon tersedia
            if ($recipient_mode === Constants::RECIPIENT_MODE_DYNAMIC) {
                $form_data = new FormData($form_id);
                $phone_fields = $form_data->get_phone_fields();

                if (empty($phone_fields)) {
                    // Tidak ada field telepon, paksa gunakan default
                    $recipient_mode = Constants::RECIPIENT_MODE_DEFAULT;
                    $this->logger->warning("Form {$form_id} switched to default recipient mode: no phone fields available");
                }

                // Validasi bahwa field recipient dipilih
                $recipient_field = isset($_POST['recipient_field']) ? $_POST['recipient_field'] : '';
                if (empty($recipient_field) || $recipient_field === '--') {
                    $errors['recipient_field'] = __('Silakan pilih field untuk mengambil nomor WhatsApp', 'whatsapp-notify');
                }
            }

            // Validasi template pesan jika disediakan
            $message_template = isset($_POST['message_template']) ? $_POST['message_template'] : '';
            if (!empty($message_template)) {
                $template_validation = Validator::validate_message_template($message_template);
                if (!$template_validation['is_valid']) {
                    $errors['message_template'] = $template_validation['message'];
                } else {
                    $message_template = $template_validation['formatted'];
                }
            }

            // Jika ada error, kirim respons error
            if (!empty($errors)) {
                $this->send_error(
                    __('Harap perbaiki kesalahan berikut:', 'whatsapp-notify'),
                    ['errors' => $errors]
                );
                return;
            }

            // Siapkan data dengan format yang konsisten
            $form_settings = [
                'enabled' => (bool)$enabled, // Pastikan konversi eksplisit ke boolean
                'recipient_mode' => $recipient_mode,
                'recipient' => isset($_POST['recipient']) ? sanitize_text_field($_POST['recipient']) : '',
                'recipient_field' => isset($_POST['recipient_field']) ? sanitize_text_field($_POST['recipient_field']) : '',
                'message_template' => isset($_POST['message_template']) ? wp_kses_post($_POST['message_template']) : '',
                'included_fields' => isset($_POST['included_fields']) ? (array)$_POST['included_fields'] : ['*']
            ];

            // Log sebelum simpan
            $this->logger->info("Saving form settings", [
                'settings' => $form_settings,
                'form_id' => $form_id
            ]);

            // Simpan dengan error handling
            try {
                // PERBAIKAN: Periksa apakah ini adalah save setelah test notification
                $test_completed = isset($_POST['test_completed']) &&
                    filter_var($_POST['test_completed'], FILTER_VALIDATE_BOOLEAN);

                // Simpan pengaturan
                $success = FormSettingsManager::save_form_settings($form_id, $form_settings);

                // PERBAIKAN: Jika ini save setelah test, dan tidak ada perubahan sebenarnya,
                // kita tetap anggap sebagai sukses
                if (!$success && $test_completed) {
                    $this->logger->info("No changes after test notification - treating as success");
                    $success = true;
                    $no_changes = true;
                } else {
                    $no_changes = !$success;
                }

                // Selalu anggap sukses dalam kasus ini
                $this->send_success([
                    'message' => $no_changes
                        ? __('Pengaturan tidak berubah.', 'whatsapp-notify')
                        : __('Pengaturan formulir berhasil disimpan!', 'whatsapp-notify'),
                    'form_id' => $form_id,
                    'status' => $enabled,
                    'no_changes' => $no_changes,
                    'after_test' => $test_completed
                ]);
            } catch (\Exception $e) {
                $this->logger->error("Exception while saving settings", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->send_error(__('Gagal menyimpan pengaturan: ', 'whatsapp-notify') . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception in save_form_settings", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->send_error(__('Terjadi kesalahan server: ', 'whatsapp-notify') . $e->getMessage());
        }
    }

    /**
     * AJAX handler untuk menguji notifikasi formulir
     */
    public function test_form_notification()
    {
        // Tambahkan logging untuk debug
        $this->logger->info("FormAjaxHandler::test_form_notification called", [
            'post_data' => isset($_POST) ? $_POST : 'empty'
        ]);

        try {
            // Verifikasi request
            if (!$this->verify_request()) {
                return;
            }

            // Cek kelengkapan konfigurasi dasar
            $config_check = $this->admin_page->ensure_config_complete('test_notification');
            if (is_wp_error($config_check)) {
                $error_data = $config_check->get_error_data();
                $this->send_error(
                    $config_check->get_error_message(),
                    [
                        'redirect_url' => $error_data['redirect_url'],
                        'incomplete_config' => true
                    ]
                );
                return;
            }

            // Ambil form ID
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            if ($form_id <= 0) {
                $this->send_error(__('ID formulir tidak valid.', 'whatsapp-notify'));
                return;
            }

            // Ambil informasi formulir
            $form = $this->get_form_data($form_id);
            if (!$form) {
                $this->send_error(__('Formulir tidak ditemukan.', 'whatsapp-notify'));
                return;
            }

            // Ambil pengaturan formulir
            $form_settings = FormSettingsManager::get_form_settings($form_id);
            
            // PERBAIKAN: Pesan error yang lebih jelas saat form tidak aktif
            if (empty($form_settings) || !$form_settings['enabled']) {
                $this->send_error(__('Notifikasi tidak diaktifkan untuk formulir ini. Aktifkan terlebih dahulu untuk melakukan pengujian.', 'whatsapp-notify'));
                return;
            }

            // Gunakan mode penerima yang dikirim dari client
            $recipient_mode = isset($_POST['recipient_mode']) ?
                $_POST['recipient_mode'] : ($form_settings['recipient_mode'] ?? Constants::RECIPIENT_MODE_DEFAULT);

            // Tentukan penerima berdasarkan mode yang dipilih
            $recipient = '';
            $mode_label = '';

            switch ($recipient_mode) {
                case Constants::RECIPIENT_MODE_MANUAL:
                    // Mode Kustom: Gunakan nomor kustom dari pengaturan
                    $recipient = !empty($form_settings['recipient']) ? $form_settings['recipient'] : '';
                    $mode_label = __('Nomor Kustom', 'whatsapp-notify');

                    // Validasi nomor penerima
                    if (!empty($recipient)) {
                        $recipient_validation = Validator::validate_whatsapp_number($recipient);
                        if (!$recipient_validation['is_valid']) {
                            $this->send_error(__('Nomor WhatsApp tidak valid: ', 'whatsapp-notify') . $recipient_validation['message']);
                            return;
                        }
                        $recipient = $recipient_validation['formatted']; // Gunakan format standar
                    }
                    break;

                case Constants::RECIPIENT_MODE_DYNAMIC:
                    // Mode Dinamis: Untuk tes, gunakan nomor default karena tidak ada data form
                    $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
                    $recipient = isset($settings['default_recipient']) ? $settings['default_recipient'] : '';
                    $mode_label = __('Default (untuk pengujian)', 'whatsapp-notify');

                    // Tambahkan catatan bahwa kita menggunakan nomor default untuk pengujian
                    $test_note = __("Menggunakan nomor default untuk pengujian karena mode 'Ambil dari Field Form' membutuhkan data formulir yang tidak tersedia saat tes.", 'whatsapp-notify');
                    break;

                case Constants::RECIPIENT_MODE_DEFAULT:
                default:
                    // Mode Default: Gunakan nomor default
                    $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
                    $recipient = isset($settings['default_recipient']) ? $settings['default_recipient'] : '';
                    $mode_label = __('Default', 'whatsapp-notify');
                    break;
            }

            if (empty($recipient)) {
                $this->send_error(__('Nomor penerima tidak tersedia untuk pengujian. Silakan konfigurasi nomor default atau kustom.', 'whatsapp-notify'));
                return;
            }

            // Buat pesan tes
            $message = "🧪 *" . __('Ini adalah pesan tes dari WhatsApp Notify', 'whatsapp-notify') . "*\n\n";
            $message .= __('Formulir:', 'whatsapp-notify') . " {$form->title}\n";
            $message .= __('ID:', 'whatsapp-notify') . " {$form->id}\n";
            $message .= __('Mode:', 'whatsapp-notify') . " {$mode_label}\n";
            $message .= __('Waktu:', 'whatsapp-notify') . " " . date_i18n('Y-m-d H:i:s') . "\n\n";

            // Tambahkan catatan khusus untuk mode dinamis
            if (isset($test_note)) {
                $message .= __('Catatan:', 'whatsapp-notify') . " {$test_note}\n\n";
            }

            $message .= __('Jika Anda menerima pesan ini, berarti integrasi berfungsi dengan baik!', 'whatsapp-notify') . " 👍";

            // PERBAIKAN: Simpan pengaturan form termasuk recipient_mode yang dipilih untuk test
            // Agar konsisten dengan apa yang dikirimkan saat save form
            $form_settings_context = FormSettingsManager::get_form_settings($form_id);

            // Log tindakan
            $this->logger->info("Test notification sent for form {$form_id}", [
                'user_id' => get_current_user_id(),
                'recipient_mode' => $recipient_mode,
                'recipient' => $recipient,
                'settings' => $form_settings_context
            ]);

            // Kirim notifikasi tes menggunakan NotificationManager
            try {
                $result = $this->notification_manager->send('whatsapp', $recipient, $message, [
                    'form_id' => $form_id
                ]);

                // Kirim respons
                if ($result['success']) {
                    $this->send_success([
                        'message' =>
                        __('Pesan tes berhasil dikirim ke', 'whatsapp-notify') .
                            ' ' . $recipient . ' (' .
                            __('Mode:', 'whatsapp-notify') . ' ' . $mode_label . ')',
                        'test_completed' => true // Flag untuk frontend bahwa tes selesai
                    ]);
                } else {
                    $this->send_error(__('Gagal mengirim pesan tes:', 'whatsapp-notify') . ' ' . $result['message']);
                }
            } catch (\Exception $e) {
                $this->logger->error("Exception sending test notification", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->send_error(__('Gagal mengirim pesan tes: ', 'whatsapp-notify') . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception in test_form_notification", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->send_error(__('Terjadi kesalahan server: ', 'whatsapp-notify') . $e->getMessage());
        }
    }

    /**
     * AJAX handler untuk toggle status notifikasi formulir
     */
    public function toggle_form_status()
    {
        // Verifikasi request
        $data = $this->verify_request();
        if (!$data) {
            return;
        }

        // Validasi form ID
        $form_id = isset($data['form_id']) ? intval($data['form_id']) : 0;
        if ($form_id <= 0) {
            $this->send_error(__('ID formulir tidak valid.', 'whatsapp-notify'));
            return;
        }

        // Ambil status baru dengan filter_var untuk memastikan nilai boolean yang benar
        $enabled = isset($data['enabled']) ? filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN) : false;

        // Toggle status
        $success = FormSettingsManager::toggle_form_status($form_id, $enabled);

        // Log tindakan
        $this->logger->info("Form {$form_id} notification status toggled", [
            'user_id' => get_current_user_id(),
            'enabled' => $enabled
        ]);

        // Kirim respons
        if ($success) {
            $status_text = $enabled ?
                __('aktif', 'whatsapp-notify') :
                __('tidak aktif', 'whatsapp-notify');

            $this->send_success([
                'message' => sprintf(
                    __('Status notifikasi berhasil diubah menjadi %s.', 'whatsapp-notify'),
                    $status_text
                ),
                'status' => $enabled,
                'form_id' => $form_id
            ]);
        } else {
            $this->send_error(__('Gagal mengubah status notifikasi.', 'whatsapp-notify'));
        }
    }

    /**
     * AJAX handler untuk mengambil status formulir
     */
    public function get_forms_status()
    {
        // Verifikasi request
        $data = $this->verify_request();
        if (!$data) {
            return;
        }

        // Validasi form IDs
        $form_ids = isset($data['form_ids']) ? array_map('intval', $data['form_ids']) : [];

        if (empty($form_ids)) {
            $this->send_error(__('Tidak ada ID formulir yang valid.', 'whatsapp-notify'));
            return;
        }

        // Kumpulkan status untuk setiap formulir
        $statuses = [];
        foreach ($form_ids as $form_id) {
            $statuses[$form_id] = FormSettingsManager::is_enabled($form_id);
        }

        // Kirim respons
        $this->send_success([
            'statuses' => $statuses
        ]);
    }

    /**
     * AJAX handler untuk menyimpan pengaturan formulir yang otomatis disesuaikan
     */
    public function auto_adjust_form_settings()
    {
        // Verifikasi request
        $data = $this->verify_request();
        if (!$data) {
            return;
        }

        // Validasi form ID
        $form_id = isset($data['form_id']) ? intval($data['form_id']) : 0;
        if ($form_id <= 0) {
            $this->send_error(__('ID formulir tidak valid.', 'whatsapp-notify'));
            return;
        }

        // Paksa recipient_mode ke default
        $recipient_mode = Constants::RECIPIENT_MODE_DEFAULT;

        // Tangani nilai enabled dengan cara yang sangat eksplisit
        $enabled = isset($data['enabled']) ?
            filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN) :
            false;

        // Siapkan data pengaturan formulir
        $form_settings = [
            'enabled' => $enabled,
            'recipient_mode' => $recipient_mode,
            'recipient' => $data['recipient'] ?? '',
            'recipient_field' => $data['recipient_field'] ?? '',
            'message_template' => $data['message_template'] ?? '',
            'included_fields' => isset($data['included_fields']) ? (array) $data['included_fields'] : ['*']
        ];

        // Log perubahan otomatis
        $this->logger->info(
            sprintf('Form %d: Auto-adjusted recipient mode from \'dynamic\' to \'default\' because phone fields are no longer available', $form_id)
        );

        // Simpan pengaturan formulir
        $success = FormSettingsManager::save_form_settings($form_id, $form_settings);

        // Kirim respons
        if ($success) {
            $this->send_success([
                'message' => __('Pengaturan formulir otomatis disesuaikan!', 'whatsapp-notify'),
                'form_id' => $form_id,
                'status' => $enabled
            ]);
        } else {
            $this->send_error(__('Gagal menyesuaikan pengaturan formulir.', 'whatsapp-notify'));
        }
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

    /**
     * Handle request AJAX (implementasi method abstrak)
     */
    public function handle_request()
    {
        // Method ini tidak digunakan karena handler terdaftar secara individual
    }
}
