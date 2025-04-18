<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

class FluentWA_Admin
{
    private $api;
    private $logger;

    public function __construct($api, $logger)
    {
        $this->api = $api;
        $this->logger = $logger;

        // Tambahkan menu admin
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Daftarkan assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Daftarkan ajax handlers
        add_action('wp_ajax_fluentwa_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_fluentwa_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_fluentwa_save_form_settings', array($this, 'ajax_save_form_settings'));
        add_action('wp_ajax_fluentwa_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_fluentwa_test_form_notification', array($this, 'ajax_test_form_notification'));
        add_action('wp_ajax_fluentwa_toggle_form_status', array($this, 'ajax_toggle_form_status'));
        add_action('wp_ajax_fluentwa_get_forms_status', array($this, 'ajax_get_forms_status'));
        add_action('wp_ajax_fluentwa_auto_adjust_form_settings', array($this, 'ajax_auto_adjust_form_settings'));
        add_action('wp_ajax_fluentwa_check_configuration', array($this, 'ajax_check_configuration'));
    }

    /**
     * Tambahkan menu admin ke dashboard
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('WhatsApp Notifier', 'fluent-whatsapp-notifier'),
            __('WA Notifier', 'fluent-whatsapp-notifier'),
            'manage_options',
            'fluent-whatsapp-notifier',
            array($this, 'render_admin_page'),
            'dashicons-whatsapp',
            30
        );
    }

    /**
     * Daftarkan asset CSS & JS
     */
    public function enqueue_assets($hook)
    {
        // Hanya daftarkan di halaman plugin ini
        if (strpos($hook, 'fluent-whatsapp-notifier') === false) {
            return;
        }

        // Daftarkan CSS
        wp_enqueue_style(
            'fluentwa-admin-style',
            FLUENTWA_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            FLUENTWA_VERSION
        );

        // Daftarkan JS
        wp_enqueue_script(
            'fluentwa-admin-script',
            FLUENTWA_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            FLUENTWA_VERSION,
            true
        );

        // Berikan data untuk JS
        wp_localize_script('fluentwa-admin-script', 'fluentWA', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fluentwa_admin_nonce'),
            'settings_url' => admin_url('admin.php?page=fluent-whatsapp-notifier'),
            'i18n' => array(
                'success' => __('Berhasil!', 'fluent-whatsapp-notifier'),
                'error' => __('Error!', 'fluent-whatsapp-notifier'),
                'saving' => __('Menyimpan...', 'fluent-whatsapp-notifier'),
                'testing' => __('Menguji koneksi...', 'fluent-whatsapp-notifier'),
                'confirm_clear_logs' => __('Apakah Anda yakin ingin menghapus semua log?', 'fluent-whatsapp-notifier'),
                'activating' => __('Mengaktifkan...', 'fluent-whatsapp-notifier'),
                'deactivating' => __('Menonaktifkan...', 'fluent-whatsapp-notifier'),
                'phone_field_required' => __('Opsi ini tidak tersedia karena tidak ada field telepon di formulir', 'fluent-whatsapp-notifier'),
                'settings_auto_adjusted' => __('Pengaturan penerima notifikasi disesuaikan otomatis karena field telepon tidak tersedia lagi', 'fluent-whatsapp-notifier'),
            )
        ));
    }

    /**
     * Render halaman admin
     */
    public function render_admin_page()
    {
        // Ambil tab aktif
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        // Template utama
        include FLUENTWA_PLUGIN_DIR . 'templates/admin-page.php';
    }

    /**
     * Render tab pengaturan umum
     */
    private function render_general_settings()
    {
        $settings = get_option(FluentWA_Constants::SETTINGS_OPTION_KEY, array());

        $api_url = isset($settings['api_url']) ? esc_url($settings['api_url']) : '';
        $default_recipient = isset($settings['default_recipient']) ? sanitize_text_field($settings['default_recipient']) : '';
        $default_template = isset($settings['default_template']) ? $settings['default_template'] : FluentWA_Constants::DEFAULT_TEMPLATE;
        $enable_logging = isset($settings['enable_logging']) ? (bool) $settings['enable_logging'] : false;
        $access_token = isset($settings['access_token']) ? sanitize_text_field($settings['access_token']) : '';

        // Tampilkan form pengaturan
?>
        <div class="fluentwa-settings-section">
            <h2><?php _e('Pengaturan Umum', 'fluent-whatsapp-notifier'); ?></h2>
            <p><?php _e('Konfigurasi pengaturan dasar untuk integrasi WhatsApp.', 'fluent-whatsapp-notifier'); ?></p>

            <form id="fluentwa-general-settings" class="fluentwa-form">
                <div class="fluentwa-form-row">
                    <label for="api_url"><?php _e('URL API Bot WhatsApp', 'fluent-whatsapp-notifier'); ?></label>
                    <div class="fluentwa-form-input">
                        <input type="url" id="api_url" name="api_url" class="fluentwa-input"
                            value="<?php echo esc_attr($api_url); ?>"
                            placeholder="http://server-anda.com:3000" required>
                        <p class="fluentwa-help-text">
                            <?php _e('Masukkan URL dasar (base URL) API bot WhatsApp Anda tanpa endpoint dan tanpa garis miring di akhir. Contoh: http://server-anda.com:3000', 'fluent-whatsapp-notifier'); ?>
                        </p>
                    </div>
                </div>

                <div class="fluentwa-form-row">
                    <label for="access_token"><?php _e('Token Autentikasi', 'fluent-whatsapp-notifier'); ?></label>
                    <div class="fluentwa-form-input">
                        <input type="text" id="access_token" name="access_token" class="fluentwa-input"
                            value="<?php echo esc_attr($access_token); ?>"
                            placeholder="f4d3b6a2e1c9" required>
                        <p class="fluentwa-help-text">
                            <?php _e('Token keamanan untuk autentikasi dengan API bot WhatsApp. Token dapat berisi huruf, angka, dan beberapa karakter khusus.', 'fluent-whatsapp-notifier'); ?>
                        </p>
                    </div>
                </div>

                <div class="fluentwa-form-row">
                    <label for="default_recipient"><?php _e('Nomor WhatsApp Default', 'fluent-whatsapp-notifier'); ?></label>
                    <div class="fluentwa-form-input">
                        <input type="text" id="default_recipient" name="default_recipient" class="fluentwa-input"
                            value="<?php echo esc_attr($default_recipient); ?>"
                            placeholder="+628123456789">
                        <p class="fluentwa-help-text">
                            <?php _e('Nomor WhatsApp default yang akan menerima notifikasi. Format: +628123456789 atau 08123456789', 'fluent-whatsapp-notifier'); ?>
                        </p>
                    </div>
                </div>

                <div class="fluentwa-form-row">
                    <label for="default_template"><?php _e('Template Pesan Default', 'fluent-whatsapp-notifier'); ?></label>
                    <div class="fluentwa-form-input">
                        <textarea id="default_template" name="default_template" class="fluentwa-textarea" rows="8"><?php echo esc_textarea($default_template); ?></textarea>
                        <p class="fluentwa-help-text">
                            <?php _e('Template pesan default untuk notifikasi. Anda dapat menggunakan variabel:', 'fluent-whatsapp-notifier'); ?>
                            <br><code>{form_name}</code> - <?php _e('Nama formulir', 'fluent-whatsapp-notifier'); ?>
                            <br><code>{form_id}</code> - <?php _e('ID formulir', 'fluent-whatsapp-notifier'); ?>
                            <br><code>{submission_date}</code> - <?php _e('Tanggal & waktu pengisian', 'fluent-whatsapp-notifier'); ?>
                            <br><code>{form_data}</code> - <?php _e('Semua data formulir', 'fluent-whatsapp-notifier'); ?>
                            <br><code>{field_name}</code> - <?php _e('Nilai field tertentu', 'fluent-whatsapp-notifier'); ?>
                        </p>
                    </div>
                </div>

                <div class="fluentwa-form-row">
                    <label for="enable_logging"><?php _e('Aktifkan Pencatatan Log', 'fluent-whatsapp-notifier'); ?></label>
                    <div class="fluentwa-form-input">
                        <label class="fluentwa-switch">
                            <input type="checkbox" id="enable_logging" name="enable_logging" <?php checked($enable_logging); ?>>
                            <span class="fluentwa-slider"></span>
                        </label>
                        <p class="fluentwa-help-text">
                            <?php _e('Catat aktivitas plugin untuk membantu pemecahan masalah.', 'fluent-whatsapp-notifier'); ?>
                        </p>
                    </div>
                </div>

                <div class="fluentwa-form-actions">
                    <button type="submit" class="button button-primary fluentwa-submit-btn">
                        <?php _e('Simpan Pengaturan', 'fluent-whatsapp-notifier'); ?>
                    </button>

                    <button type="button" id="fluentwa-test-connection" class="button">
                        <?php _e('Tes Koneksi', 'fluent-whatsapp-notifier'); ?>
                    </button>
                </div>
            </form>
        </div>
    <?php
    }

    /**
     * AJAX handler untuk menyimpan pengaturan umum
     */
    public function ajax_save_settings()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        $errors = [];

        // Validasi URL API
        $api_url = isset($_POST['api_url']) ? $_POST['api_url'] : '';
        $api_url_validation = FluentWA_Validator::validate_api_url($api_url);
        if (!$api_url_validation['is_valid']) {
            $errors['api_url'] = $api_url_validation['message'];
        }

        // Validasi Token Autentikasi
        $access_token = isset($_POST['access_token']) ? $_POST['access_token'] : '';
        $token_validation = FluentWA_Validator::validate_access_token($access_token);
        if (!$token_validation['is_valid']) {
            $errors['access_token'] = $token_validation['message'];
        }

        // Validasi Nomor WhatsApp Default
        $default_recipient = isset($_POST['default_recipient']) ? $_POST['default_recipient'] : '';
        $recipient_validation = FluentWA_Validator::validate_whatsapp_number($default_recipient);
        if (!$recipient_validation['is_valid']) {
            $errors['default_recipient'] = $recipient_validation['message'];
        }

        // Validasi Template Pesan Default
        $default_template = isset($_POST['default_template']) ? $_POST['default_template'] : '';
        $template_validation = FluentWA_Validator::validate_message_template($default_template);
        if (!$template_validation['is_valid']) {
            $errors['default_template'] = $template_validation['message'];
        }

        // Jika ada error, kirim respons error
        if (!empty($errors)) {
            wp_send_json_error([
                'message' => 'Harap perbaiki kesalahan berikut:',
                'errors' => $errors
            ]);
            return;
        }

        // Gunakan nilai yang sudah diformat
        $settings = [
            'api_url' => $api_url_validation['formatted'],
            'access_token' => $token_validation['formatted'],
            'default_recipient' => $recipient_validation['formatted'],
            'default_template' => $template_validation['formatted'],
            'enable_logging' => isset($_POST['enable_logging']) ? (bool) $_POST['enable_logging'] : false
        ];

        // Simpan pengaturan
        update_option(FluentWA_Constants::SETTINGS_OPTION_KEY, $settings);

        // Kirim respons sukses
        wp_send_json_success([
            'message' => 'Pengaturan berhasil disimpan!'
        ]);
    }

    /**
     * Memeriksa kelengkapan konfigurasi dasar
     * @return array Status dan detail konfigurasi yang belum lengkap
     */
    public function check_core_configuration()
    {
        $settings = get_option(FluentWA_Constants::SETTINGS_OPTION_KEY, []);
        $validation_results = [];
        $is_complete = true;

        // Validasi URL API
        if (empty($settings['api_url'])) {
            $validation_results['api_url'] = [
                'is_valid' => false,
                'message' => 'URL API Bot WhatsApp belum dikonfigurasi',
                'field_name' => 'URL API Bot WhatsApp'
            ];
            $is_complete = false;
        } else {
            $api_url_validation = FluentWA_Validator::validate_api_url($settings['api_url']);
            if (!$api_url_validation['is_valid']) {
                $validation_results['api_url'] = [
                    'is_valid' => false,
                    'message' => $api_url_validation['message'],
                    'field_name' => 'URL API Bot WhatsApp'
                ];
                $is_complete = false;
            }
        }

        // Validasi Token Autentikasi
        if (empty($settings['access_token'])) {
            $validation_results['access_token'] = [
                'is_valid' => false,
                'message' => 'Token Autentikasi belum dikonfigurasi',
                'field_name' => 'Token Autentikasi'
            ];
            $is_complete = false;
        } else {
            $token_validation = FluentWA_Validator::validate_access_token($settings['access_token']);
            if (!$token_validation['is_valid']) {
                $validation_results['access_token'] = [
                    'is_valid' => false,
                    'message' => $token_validation['message'],
                    'field_name' => 'Token Autentikasi'
                ];
                $is_complete = false;
            }
        }

        // Validasi Nomor Default
        if (empty($settings['default_recipient'])) {
            $validation_results['default_recipient'] = [
                'is_valid' => false,
                'message' => 'Nomor WhatsApp Default belum dikonfigurasi',
                'field_name' => 'Nomor WhatsApp Default'
            ];
            $is_complete = false;
        } else {
            $number_validation = FluentWA_Validator::validate_whatsapp_number($settings['default_recipient']);
            if (!$number_validation['is_valid']) {
                $validation_results['default_recipient'] = [
                    'is_valid' => false,
                    'message' => $number_validation['message'],
                    'field_name' => 'Nomor WhatsApp Default'
                ];
                $is_complete = false;
            }
        }

        // Validasi Template Default
        if (empty($settings['default_template'])) {
            $validation_results['default_template'] = [
                'is_valid' => false,
                'message' => 'Template Pesan Default belum dikonfigurasi',
                'field_name' => 'Template Pesan Default'
            ];
            $is_complete = false;
        } else {
            $template_validation = FluentWA_Validator::validate_message_template($settings['default_template']);
            if (!$template_validation['is_valid']) {
                $validation_results['default_template'] = [
                    'is_valid' => false,
                    'message' => $template_validation['message'],
                    'field_name' => 'Template Pesan Default'
                ];
                $is_complete = false;
            }
        }

        return [
            'is_complete' => $is_complete,
            'validation_results' => $validation_results
        ];
    }

    /**
     * Memastikan fitur hanya dapat diakses jika konfigurasi lengkap
     * @param string $action Nama aksi yang akan dijalankan 
     * @return bool|WP_Error True jika bisa dilanjutkan, WP_Error jika tidak
     */
    public function ensure_config_complete($action = '')
    {
        $config_status = $this->check_core_configuration();

        if (!$config_status['is_complete']) {
            $missing_fields = [];
            $first_missing = null;

            foreach ($config_status['validation_results'] as $field_key => $result) {
                if (!$result['is_valid']) {
                    $missing_fields[] = $result['field_name'];
                    if ($first_missing === null) {
                        $first_missing = $field_key;
                    }
                }
            }

            return new WP_Error(
                'incomplete_config',
                'Pengaturan dasar belum lengkap. Silakan isi: ' . implode(', ', $missing_fields),
                [
                    'redirect_url' => admin_url('admin.php?page=fluent-whatsapp-notifier&highlight=' . $first_missing),
                    'missing_fields' => $missing_fields
                ]
            );
        }

        return true;
    }

    /**
     * AJAX handler untuk menguji koneksi WhatsApp
     */
    public function ajax_test_connection()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        // Cek kelengkapan konfigurasi
        $config_check = $this->ensure_config_complete('test_connection');
        if (is_wp_error($config_check)) {
            $error_data = $config_check->get_error_data();
            wp_send_json_error([
                'message' => $config_check->get_error_message(),
                'redirect_url' => $error_data['redirect_url'],
                'incomplete_config' => true
            ]);
            return;
        }

        // Tes koneksi menggunakan API
        $result = $this->api->test_connection();

        // Kirim respons sesuai hasil
        if ($result['success']) {
            wp_send_json_success([
                'message' => 'Koneksi berhasil! Pesan tes telah dikirim.'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Koneksi gagal: ' . $result['message']
            ]);
        }
    }

    /**
     * AJAX handler untuk membersihkan log
     */
    public function ajax_clear_logs()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
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

    /**
     * AJAX handler untuk memeriksa kelengkapan konfigurasi
     */
    public function ajax_check_configuration()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        $config_status = $this->check_core_configuration();

        wp_send_json_success([
            'is_complete' => $config_status['is_complete'],
            'validation_results' => $config_status['validation_results'],
            'settings_url' => admin_url('admin.php?page=fluent-whatsapp-notifier')
        ]);
    }

    /**
     * AJAX handler untuk menyimpan pengaturan formulir
     */
    public function ajax_save_form_settings()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        // Validasi form ID
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        if ($form_id <= 0) {
            wp_send_json_error(['message' => 'ID formulir tidak valid.']);
        }

        // Validasi enabled flag
        $enabled = false;
        if (isset($_POST['enabled'])) {
            $enabled_value = $_POST['enabled'];
            if ($enabled_value === '1' || $enabled_value === 'true' || $enabled_value === true || $enabled_value === 1) {
                $enabled = true;
            }
        }

        // Validasi recipient mode
        $recipient_mode = sanitize_text_field($_POST['recipient_mode']);
        $errors = [];

        // Jika mode adalah manual, validasi nomor kustom
        if ($recipient_mode === FluentWA_Constants::RECIPIENT_MODE_MANUAL) {
            $recipient = isset($_POST['recipient']) ? $_POST['recipient'] : '';
            $recipient_validation = FluentWA_Validator::validate_whatsapp_number($recipient);

            if (!$recipient_validation['is_valid']) {
                $errors['recipient'] = $recipient_validation['message'];
            } else {
                $recipient = $recipient_validation['formatted']; // Gunakan nilai yang sudah diformat
            }
        } else {
            $recipient = sanitize_text_field($_POST['recipient']);
        }

        // Jika mode adalah dynamic, pastikan ada field telepon tersedia
        if ($recipient_mode === FluentWA_Constants::RECIPIENT_MODE_DYNAMIC) {
            $phone_fields = $this->get_phone_fields($form_id);
            if (empty($phone_fields)) {
                // Tidak ada field telepon, paksa gunakan default
                $recipient_mode = FluentWA_Constants::RECIPIENT_MODE_DEFAULT;
            }

            // Validasi bahwa field recipient dipilih
            $recipient_field = isset($_POST['recipient_field']) ? $_POST['recipient_field'] : '';
            if (empty($recipient_field) || $recipient_field === '--') {
                $errors['recipient_field'] = 'Silakan pilih field untuk mengambil nomor WhatsApp';
            }
        }

        // Validasi template pesan jika disediakan
        $message_template = isset($_POST['message_template']) ? $_POST['message_template'] : '';
        if (!empty($message_template)) {
            $template_validation = FluentWA_Validator::validate_message_template($message_template);
            if (!$template_validation['is_valid']) {
                $errors['message_template'] = $template_validation['message'];
            } else {
                $message_template = $template_validation['formatted'];
            }
        }

        // Jika ada error, kirim respons error
        if (!empty($errors)) {
            wp_send_json_error([
                'message' => 'Harap perbaiki kesalahan berikut:',
                'errors' => $errors
            ]);
            return;
        }

        // Siapkan data pengaturan formulir
        $form_settings = [
            'enabled' => $enabled,
            'recipient_mode' => $recipient_mode,
            'recipient' => $recipient,
            'recipient_field' => sanitize_text_field($_POST['recipient_field']),
            'message_template' => $message_template,
            'included_fields' => isset($_POST['included_fields']) ? (array) $_POST['included_fields'] : ['*']
        ];

        // Simpan pengaturan formulir
        update_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, $form_settings);

        // Kirim respons sukses
        wp_send_json_success([
            'message' => 'Pengaturan formulir berhasil disimpan!',
            'form_id' => $form_id,
            'status' => $enabled
        ]);
    }

    /**
     * AJAX handler untuk toggle status notifikasi formulir
     */
    public function ajax_toggle_form_status()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
        }

        // Validasi form ID
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

        if ($form_id <= 0) {
            wp_send_json_error(array('message' => 'ID formulir tidak valid.'));
        }

        // Ambil status baru dengan filter_var untuk memastikan nilai boolean yang benar
        $enabled = isset($_POST['enabled']) ? filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN) : false;

        // Ambil pengaturan saat ini
        $form_settings = get_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, array());

        // Jika pengaturan belum ada, inisialisasi dengan default
        if (empty($form_settings)) {
            $form_settings = array(
                'recipient_mode' => FluentWA_Constants::RECIPIENT_MODE_DEFAULT,
                'recipient' => '',
                'recipient_field' => '',
                'message_template' => '',
                'included_fields' => array('*')
            );
        }

        // Update status
        $form_settings['enabled'] = $enabled;

        // Simpan ke database
        update_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, $form_settings);

        // Kirim respons
        $status_text = $enabled ? 'aktif' : 'tidak aktif';
        wp_send_json_success(array(
            'message' => "Status notifikasi berhasil diubah menjadi {$status_text}.",
            'status' => $enabled,
            'form_id' => $form_id
        ));
    }

    /**
     * AJAX handler untuk mengambil status formulir
     */
    public function ajax_get_forms_status()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
        }

        // Validasi form IDs
        $form_ids = isset($_POST['form_ids']) ? array_map('intval', $_POST['form_ids']) : array();

        if (empty($form_ids)) {
            wp_send_json_error(array('message' => 'Tidak ada ID formulir yang valid.'));
        }

        // Kumpulkan status untuk setiap formulir
        $statuses = array();
        foreach ($form_ids as $form_id) {
            $form_settings = get_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, array());
            $statuses[$form_id] = isset($form_settings['enabled']) ? (bool) $form_settings['enabled'] : false;
        }

        // Kirim respons
        wp_send_json_success(array(
            'statuses' => $statuses
        ));
    }

    /**
     * AJAX handler untuk menguji notifikasi formulir
     */
    public function ajax_test_form_notification()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(['message' => 'Permintaan tidak sah.']);
        }

        // Cek kelengkapan konfigurasi dasar
        $config_check = $this->ensure_config_complete('test_notification');
        if (is_wp_error($config_check)) {
            $error_data = $config_check->get_error_data();
            wp_send_json_error([
                'message' => $config_check->get_error_message(),
                'redirect_url' => $error_data['redirect_url'],
                'incomplete_config' => true
            ]);
            return;
        }

        // Ambil form ID
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        if ($form_id <= 0) {
            wp_send_json_error(['message' => 'ID formulir tidak valid.']);
        }

        // Ambil informasi formulir
        $form = wpFluent()->table('fluentform_forms')->find($form_id);
        if (!$form) {
            wp_send_json_error(['message' => 'Formulir tidak ditemukan.']);
        }

        // Ambil pengaturan formulir
        $form_settings = get_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, []);
        if (empty($form_settings) || !$form_settings['enabled']) {
            wp_send_json_error(['message' => 'Notifikasi tidak diaktifkan untuk formulir ini.']);
        }

        // Gunakan mode penerima yang dikirim dari client
        $recipient_mode = isset($_POST['recipient_mode']) ?
            sanitize_text_field($_POST['recipient_mode']) : ($form_settings['recipient_mode'] ?? FluentWA_Constants::RECIPIENT_MODE_DEFAULT);

        // Tentukan penerima berdasarkan mode yang dipilih
        $recipient = '';
        $mode_label = '';

        switch ($recipient_mode) {
            case FluentWA_Constants::RECIPIENT_MODE_MANUAL:
                // Mode Kustom: Gunakan nomor kustom dari pengaturan
                $recipient = !empty($form_settings['recipient']) ? $form_settings['recipient'] : '';
                $mode_label = 'Nomor Kustom';

                // Validasi nomor penerima
                if (!empty($recipient)) {
                    $recipient_validation = FluentWA_Validator::validate_whatsapp_number($recipient);
                    if (!$recipient_validation['is_valid']) {
                        wp_send_json_error(['message' => 'Nomor WhatsApp tidak valid: ' . $recipient_validation['message']]);
                        return;
                    }
                    $recipient = $recipient_validation['formatted']; // Gunakan format standar
                }
                break;

            case FluentWA_Constants::RECIPIENT_MODE_DYNAMIC:
                // Mode Dinamis: Untuk tes, gunakan nomor default karena tidak ada data form
                $recipient = get_option(FluentWA_Constants::SETTINGS_OPTION_KEY)['default_recipient'] ?? '';
                $mode_label = 'Default (untuk pengujian)';

                // Tambahkan catatan bahwa kita menggunakan nomor default untuk pengujian
                $test_note = "Menggunakan nomor default untuk pengujian karena mode 'Ambil dari Field Form' membutuhkan data formulir yang tidak tersedia saat tes.";
                break;

            case FluentWA_Constants::RECIPIENT_MODE_DEFAULT:
            default:
                // Mode Default: Gunakan nomor default
                $recipient = get_option(FluentWA_Constants::SETTINGS_OPTION_KEY)['default_recipient'] ?? '';
                $mode_label = 'Default';
                break;
        }

        if (empty($recipient)) {
            wp_send_json_error(['message' => 'Nomor penerima tidak tersedia untuk pengujian. Silakan konfigurasi nomor default atau kustom.']);
            return;
        }

        // Buat pesan tes
        $message = "🧪 *Ini adalah pesan tes dari Fluent WhatsApp Notifier*\n\n";
        $message .= "Formulir: {$form->title}\n";
        $message .= "ID: {$form->id}\n";
        $message .= "Mode: " . $mode_label . "\n";
        $message .= "Waktu: " . date_i18n('Y-m-d H:i:s') . "\n\n";

        // Tambahkan catatan khusus untuk mode dinamis
        if (isset($test_note)) {
            $message .= "Catatan: " . $test_note . "\n\n";
        }

        $message .= "Jika Anda menerima pesan ini, berarti integrasi berfungsi dengan baik! 👍";

        // Kirim notifikasi tes
        $result = $this->api->send_notification($recipient, $message, $form_id);

        // Kirim respons
        if ($result['success']) {
            wp_send_json_success([
                'message' => 'Pesan tes berhasil dikirim ke ' . $recipient . ' (Mode: ' . $mode_label . ')'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Gagal mengirim pesan tes: ' . $result['message']
            ]);
        }
    }

    /**
     * AJAX handler untuk menyimpan pengaturan formulir yang otomatis disesuaikan
     */
    public function ajax_auto_adjust_form_settings()
    {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
        }

        // Validasi form ID
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

        if ($form_id <= 0) {
            wp_send_json_error(array('message' => 'ID formulir tidak valid.'));
        }

        // Paksa recipient_mode ke default
        $recipient_mode = FluentWA_Constants::RECIPIENT_MODE_DEFAULT;

        // Tangani nilai enabled dengan cara yang sangat eksplisit
        $enabled = false; // Default ke false
        if (isset($_POST['enabled'])) {
            $enabled_value = $_POST['enabled'];
            if ($enabled_value === '1' || $enabled_value === 'true' || $enabled_value === true || $enabled_value === 1) {
                $enabled = true;
            }
        }

        // Ambil pengaturan yang ada dan pertahankan nilai lain yang tidak berubah
        $current_settings = get_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, array());

        // Siapkan data pengaturan formulir dengan fokus pada perubahan recipient_mode
        $form_settings = array(
            'enabled' => $enabled,
            'recipient_mode' => $recipient_mode,
            'recipient' => sanitize_text_field($_POST['recipient']),
            'recipient_field' => sanitize_text_field($_POST['recipient_field']),
            'message_template' => wp_kses_post($_POST['message_template']),
            'included_fields' => isset($_POST['included_fields']) ? (array) $_POST['included_fields'] : array('*')
        );

        // Log perubahan otomatis
        if (
            isset($this->logger) && $this->logger &&
            isset($current_settings['recipient_mode']) &&
            $current_settings['recipient_mode'] === FluentWA_Constants::RECIPIENT_MODE_DYNAMIC
        ) {
            $this->logger->log_info("Form {$form_id}: Auto-adjusted recipient mode from 'dynamic' to 'default' because phone fields are no longer available");
        }

        // Simpan pengaturan formulir
        update_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, $form_settings);

        // Kirim respons sukses
        wp_send_json_success(array(
            'message' => 'Pengaturan formulir otomatis disesuaikan!',
            'form_id' => $form_id,
            'status' => $enabled
        ));
    }

    /**
     * Render tab pengaturan formulir
     */
    private function render_form_settings()
    {
        // Ambil form ID dari URL jika ada
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

        if ($form_id > 0) {
            // Tampilkan pengaturan untuk formulir tertentu
            $form = wpFluent()->table('fluentform_forms')->find($form_id);

            if (!$form) {
                echo '<div class="notice notice-error"><p>Formulir tidak ditemukan.</p></div>';
                return;
            }

            $form_title = $form->title;
            $form_settings = get_option(FluentWA_Constants::FORM_SETTINGS_PREFIX . $form_id, array(
                'enabled' => false,
                'recipient' => '',
                'recipient_field' => '',
                'message_template' => '',
                'included_fields' => array('*')
            ));

            // Ambil semua field formulir untuk pilihan field yang akan disertakan
            $form_fields = $this->get_form_fields($form_id);

            // Ambil hanya field telepon untuk pilihan field penerima dinamis
            $phone_fields = $this->get_phone_fields($form_id);

            // Ambil pengaturan umum
            $settings = get_option(FluentWA_Constants::SETTINGS_OPTION_KEY, array());

            // Tampilkan template pengaturan formulir
            include FLUENTWA_PLUGIN_DIR . 'templates/form-settings.php';
        } else {
            // Tampilkan daftar formulir
            $forms = wpFluent()->table('fluentform_forms')
                ->select(['id', 'title'])
                ->orderBy('id', 'DESC')
                ->get();

            include FLUENTWA_PLUGIN_DIR . 'templates/form-settings.php';
        }
    }

    /**
     * Render tab log aktivitas
     */
    private function render_logs()
    {
    ?>
        <div class="fluentwa-settings-section">
            <h2><?php _e('Log Aktivitas', 'fluent-whatsapp-notifier'); ?></h2>
            <p><?php _e('Catatan aktivitas plugin untuk membantu pemecahan masalah.', 'fluent-whatsapp-notifier'); ?></p>

            <div class="fluentwa-logs-actions">
                <button id="fluentwa-clear-logs" class="button">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Bersihkan Log', 'fluent-whatsapp-notifier'); ?>
                </button>
            </div>

            <div id="fluentwa-logs-container" class="fluentwa-logs-container">
                <?php
                $logs = $this->logger->get_logs();

                if (empty($logs)) {
                    echo '<p>' . __('Tidak ada log yang tersedia.', 'fluent-whatsapp-notifier') . '</p>';
                } else {
                    foreach ($logs as $log) {
                        $log_type = 'info';
                        if (strpos($log, '[ERROR]') !== false) {
                            $log_type = 'error';
                        }

                        echo '<div class="fluentwa-log-entry log-type-' . $log_type . '">' . esc_html($log) . '</div>';
                    }
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Render tab bantuan
     */
    private function render_help()
    {
    ?>
        <div class="fluentwa-settings-section">
            <h2><?php _e('Bantuan & Dokumentasi', 'fluent-whatsapp-notifier'); ?></h2>

            <div class="fluentwa-help-card">
                <h3><?php _e('Memulai', 'fluent-whatsapp-notifier'); ?></h3>
                <p><?php _e('Untuk memulai menggunakan plugin ini, Anda perlu:', 'fluent-whatsapp-notifier'); ?></p>
                <ol>
                    <li><?php _e('Menyiapkan Bot WhatsApp menggunakan whatsapp-web.js', 'fluent-whatsapp-notifier'); ?></li>
                    <li><?php _e('Mengonfigurasi URL API bot di halaman pengaturan umum', 'fluent-whatsapp-notifier'); ?></li>
                    <li><?php _e('Menambahkan token autentikasi yang sama dengan yang digunakan di server bot', 'fluent-whatsapp-notifier'); ?></li>
                    <li><?php _e('Mengaktifkan notifikasi untuk formulir yang diinginkan', 'fluent-whatsapp-notifier'); ?></li>
                </ol>
            </div>

            <div class="fluentwa-help-card">
                <h3><?php _e('FAQ', 'fluent-whatsapp-notifier'); ?></h3>

                <div class="fluentwa-faq-item">
                    <h4><?php _e('Bagaimana cara menyiapkan bot WhatsApp?', 'fluent-whatsapp-notifier'); ?></h4>
                    <div class="fluentwa-faq-answer">
                        <p><?php _e('Anda perlu menyiapkan server Node.js dengan library whatsapp-web.js. Lihat dokumentasi di GitHub untuk detail lebih lanjut.', 'fluent-whatsapp-notifier'); ?></p>
                    </div>
                </div>

                <div class="fluentwa-faq-item">
                    <h4><?php _e('Apakah plugin ini aman dan legal?', 'fluent-whatsapp-notifier'); ?></h4>
                    <div class="fluentwa-faq-answer">
                        <p><?php _e('Plugin ini menggunakan library tidak resmi untuk WhatsApp. Untuk penggunaan resmi, sebaiknya gunakan WhatsApp Business API.', 'fluent-whatsapp-notifier'); ?></p>
                    </div>
                </div>

                <div class="fluentwa-faq-item">
                    <h4><?php _e('Apa saja variabel yang bisa digunakan di template?', 'fluent-whatsapp-notifier'); ?></h4>
                    <div class="fluentwa-faq-answer">
                        <p><?php _e('Anda dapat menggunakan {form_name}, {form_id}, {submission_date}, {form_data}, dan nama field formulir seperti {nama}, {email}, dll.', 'fluent-whatsapp-notifier'); ?></p>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Ambil daftar field formulir
     */
    private function get_form_fields($form_id)
    {
        // Pastikan Fluent Forms API tersedia
        if (!function_exists('wpFluent')) {
            return array();
        }

        // Ambil struktur formulir
        $form = wpFluent()->table('fluentform_forms')->find($form_id);

        if (!$form) {
            return array();
        }

        // Parse form fields dari JSON
        $form_fields = array();
        $form_structure = json_decode($form->form_fields, true);

        // Pengecekan error JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (isset($this->logger) && $this->logger) {
                $this->logger->log_error("Error parsing form fields JSON for form ID {$form_id}: " . json_last_error_msg());
            }
            return array();
        }

        if (!empty($form_structure['fields'])) {
            $this->extract_fields($form_structure['fields'], $form_fields);
        }

        return $form_fields;
    }

    /**
     * Ambil daftar field formulir dengan tipe telepon/mobile
     * 
     * @param int $form_id ID formulir
     * @return array Array field telepon
     */
    private function get_phone_fields($form_id)
    {
        // Ambil semua field terlebih dahulu
        $all_fields = $this->get_form_fields($form_id);

        // Filter untuk hanya mengambil field telepon
        $phone_fields = array_filter($all_fields, function ($field) {
            // Daftar kemungkinan tipe field telepon di Fluent Forms
            $phone_field_types = ['phone', 'input_phone', 'phone_number', 'mobile', 'tel'];

            // Jika field element type langsung cocok
            if (in_array($field['type'], $phone_field_types)) {
                return true;
            }

            // Jika field adalah input dengan nama atau label yang mengandung kata kunci telepon
            if ($field['type'] === 'input_text' || $field['type'] === 'input_number') {
                $name_lower = strtolower($field['name']);
                $label_lower = strtolower($field['label']);

                // Periksa jika namanya mengandung kata kunci telepon
                $keywords = ['phone', 'telp', 'telepon', 'hp', 'mobile', 'wa', 'whatsapp', 'nomor'];
                foreach ($keywords as $keyword) {
                    if (strpos($name_lower, $keyword) !== false || strpos($label_lower, $keyword) !== false) {
                        return true;
                    }
                }
            }

            return false;
        });

        return array_values($phone_fields); // Reset array keys
    }

    /**
     * Ekstrak field dari struktur formulir
     */
    private function extract_fields($fields, &$result, $parent = '')
    {
        foreach ($fields as $field) {
            if (!isset($field['element']) || $field['element'] === 'container') {
                if (isset($field['columns'])) {
                    foreach ($field['columns'] as $column) {
                        if (isset($column['fields'])) {
                            $this->extract_fields($column['fields'], $result, $parent);
                        }
                    }
                }
                continue;
            }

            // Skip button elements
            if ($field['element'] === 'button') {
                continue;
            }

            // Get field attributes
            if (isset($field['attributes']['name'])) {
                $name = $field['attributes']['name'];
                $label = isset($field['settings']['label']) ? $field['settings']['label'] : $name;

                if ($parent) {
                    $name = $parent . '.' . $name;
                    $label = $parent . ' - ' . $label;
                }

                $result[] = array(
                    'name' => $name,
                    'label' => $label,
                    'type' => $field['element']
                );
            }
        }
    }
}
?>