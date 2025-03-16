<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

class FluentWA_Admin {
    private $api;
    private $logger;
    
    public function __construct($api, $logger) {
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
    
    // Tambahkan handler untuk test form notification
    add_action('wp_ajax_fluentwa_test_form_notification', array($this, 'ajax_test_form_notification'));
        
    }
    
    /**
     * Tambahkan menu admin ke dashboard
     */
    public function add_admin_menu() {
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
    public function enqueue_assets($hook) {
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
            'i18n' => array(
                'success' => __('Berhasil!', 'fluent-whatsapp-notifier'),
                'error' => __('Error!', 'fluent-whatsapp-notifier'),
                'saving' => __('Menyimpan...', 'fluent-whatsapp-notifier'),
                'testing' => __('Menguji koneksi...', 'fluent-whatsapp-notifier'),
                'confirm_clear_logs' => __('Apakah Anda yakin ingin menghapus semua log?', 'fluent-whatsapp-notifier')
            )
        ));
    }
    
    /**
     * Render halaman admin
     */
    public function render_admin_page() {
        // Ambil tab aktif
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Template utama
        include FLUENTWA_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Render tab pengaturan umum
     */
    private function render_general_settings() {
        $settings = get_option('fluentwa_settings', array());
        
        $api_url = isset($settings['api_url']) ? esc_url($settings['api_url']) : '';
        $default_recipient = isset($settings['default_recipient']) ? sanitize_text_field($settings['default_recipient']) : '';
        $default_template = isset($settings['default_template']) ? $settings['default_template'] : 
            "🔔 *Ada pengisian formulir baru!*\n\nFormulir: {form_name}\nWaktu: {submission_date}\n\n{form_data}";
        $enable_logging = isset($settings['enable_logging']) ? (bool) $settings['enable_logging'] : false;
        
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
                            placeholder="http://server-anda.com:3000/kirim-notifikasi" required>
                        <p class="fluentwa-help-text">
                            <?php _e('URL endpoint API bot WhatsApp Anda yang menangani pengiriman pesan.', 'fluent-whatsapp-notifier'); ?>
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
    public function ajax_save_settings() {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
        }
        
        // Validasi dan sanitasi data
        $settings = array(
            'api_url' => esc_url_raw($_POST['api_url']),
            'default_recipient' => sanitize_text_field($_POST['default_recipient']),
            'default_template' => wp_kses_post($_POST['default_template']),
            'enable_logging' => isset($_POST['enable_logging']) ? (bool) $_POST['enable_logging'] : false
        );
        
        // Simpan pengaturan
        update_option('fluentwa_settings', $settings);
        
        // Kirim respons sukses
        wp_send_json_success(array(
            'message' => 'Pengaturan berhasil disimpan!'
        ));
    }
    
    /**
 * AJAX handler untuk menyimpan pengaturan formulir
 */
public function ajax_save_form_settings() {
    // Cek nonce untuk keamanan
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
        wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
    }
    
    // Validasi form ID
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    
    if ($form_id <= 0) {
        wp_send_json_error(array('message' => 'ID formulir tidak valid.'));
    }
    
    // Siapkan data pengaturan formulir
    $form_settings = array(
        'enabled' => isset($_POST['enabled']) ? (bool) $_POST['enabled'] : false,
        'recipient_mode' => sanitize_text_field($_POST['recipient_mode']),
        'recipient' => sanitize_text_field($_POST['recipient']),
        'recipient_field' => sanitize_text_field($_POST['recipient_field']),
        'message_template' => wp_kses_post($_POST['message_template']),
        'included_fields' => isset($_POST['included_fields']) ? (array) $_POST['included_fields'] : array('*')
    );
    
    // Simpan pengaturan formulir
    update_option("fluentwa_form_settings_{$form_id}", $form_settings);
    
    // Kirim respons sukses
    wp_send_json_success(array(
        'message' => 'Pengaturan formulir berhasil disimpan!'
    ));
}
    
    /**
     * AJAX handler untuk menguji koneksi WhatsApp
     */
    public function ajax_test_connection() {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
        }
        
        // Tes koneksi menggunakan API
        $result = $this->api->test_connection();
        
        // Kirim respons sesuai hasil
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Koneksi berhasil! Pesan tes telah dikirim.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Koneksi gagal: ' . $result['message']
            ));
        }
    }
    
    /**
     * AJAX handler untuk menguji notifikasi formulir
     */
    public function ajax_test_form_notification() {
        // Cek nonce untuk keamanan
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fluentwa_admin_nonce')) {
            wp_send_json_error(array('message' => 'Permintaan tidak sah.'));
        }
        
        // Ambil form ID
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if ($form_id <= 0) {
            wp_send_json_error(array('message' => 'ID formulir tidak valid.'));
        }
        
        // Ambil informasi formulir
        $form = wpFluent()->table('fluentform_forms')->find($form_id);
        
        if (!$form) {
            wp_send_json_error(array('message' => 'Formulir tidak ditemukan.'));
        }
        
        // Ambil pengaturan formulir
        $form_settings = get_option("fluentwa_form_settings_{$form_id}", array());
        
        if (empty($form_settings) || !$form_settings['enabled']) {
            wp_send_json_error(array('message' => 'Notifikasi tidak diaktifkan untuk formulir ini.'));
        }
        
        // Tentukan penerima
        $recipient = !empty($form_settings['recipient']) ? 
                    $form_settings['recipient'] : 
                    get_option('fluentwa_settings')['default_recipient'] ?? '';
        
        if (empty($recipient)) {
            wp_send_json_error(array('message' => 'Nomor penerima belum dikonfigurasi.'));
        }
        
        // Buat pesan tes
        $message = "🧪 *Ini adalah pesan tes dari Fluent WhatsApp Notifier*\n\n";
        $message .= "Formulir: {$form->title}\n";
        $message .= "ID: {$form->id}\n";
        $message .= "Waktu: " . date_i18n('Y-m-d H:i:s') . "\n\n";
        $message .= "Jika Anda menerima pesan ini, berarti integrasi berfungsi dengan baik! 👍";
        
        // Kirim notifikasi tes
        $result = $this->api->send_notification($recipient, $message, $form_id);
        
        // Kirim respons
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Pesan tes berhasil dikirim ke ' . $recipient
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Gagal mengirim pesan tes: ' . $result['message']
            ));
        }
    }
    
    /**
     * AJAX handler untuk membersihkan log
     */
    public function ajax_clear_logs() {
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
 * Render tab pengaturan formulir
 */
private function render_form_settings() {
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
        $form_settings = get_option("fluentwa_form_settings_{$form_id}", array(
            'enabled' => false,
            'recipient' => '',
            'dynamic_recipient' => false,
            'recipient_field' => '',
            'message_template' => '',
            'included_fields' => array('*')
        ));
        
        // Ambil semua field formulir untuk pilihan field yang akan disertakan
        $form_fields = $this->get_form_fields($form_id);
        
        // Ambil hanya field telepon untuk pilihan field penerima dinamis
        $phone_fields = $this->get_phone_fields($form_id);
        
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
    private function render_logs() {
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
    private function render_help() {
        ?>
        <div class="fluentwa-settings-section">
            <h2><?php _e('Bantuan & Dokumentasi', 'fluent-whatsapp-notifier'); ?></h2>
            
            <div class="fluentwa-help-card">
                <h3><?php _e('Memulai', 'fluent-whatsapp-notifier'); ?></h3>
                <p><?php _e('Untuk memulai menggunakan plugin ini, Anda perlu:', 'fluent-whatsapp-notifier'); ?></p>
                <ol>
                    <li><?php _e('Menyiapkan Bot WhatsApp menggunakan whatsapp-web.js', 'fluent-whatsapp-notifier'); ?></li>
                    <li><?php _e('Mengonfigurasi URL API bot di halaman pengaturan umum', 'fluent-whatsapp-notifier'); ?></li>
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
    private function get_form_fields($form_id) {
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
private function get_phone_fields($form_id) {
    // Ambil semua field terlebih dahulu
    $all_fields = $this->get_form_fields($form_id);
    
    // Filter untuk hanya mengambil field telepon
    $phone_fields = array_filter($all_fields, function($field) {
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
    private function extract_fields($fields, &$result, $parent = '') {
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