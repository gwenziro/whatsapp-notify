<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wanotify-settings-section">
    <h2><?php _e('Pengaturan Umum', 'whatsapp-notify'); ?></h2>
    <p><?php _e('Konfigurasi pengaturan dasar untuk integrasi WhatsApp.', 'whatsapp-notify'); ?></p>

    <form id="wanotify-general-settings" class="wanotify-form">
        <div class="wanotify-form-row">
            <label for="api_url"><?php _e('URL API Bot WhatsApp', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <input type="url" id="api_url" name="api_url" class="wanotify-input"
                    value="<?php echo esc_attr($api_url); ?>"
                    placeholder="http://server-anda.com:3000" required>
                <p class="wanotify-help-text">
                    <?php _e('Masukkan URL dasar (base URL) API bot WhatsApp Anda tanpa endpoint dan tanpa garis miring di akhir. Contoh: http://server-anda.com:3000', 'whatsapp-notify'); ?>
                </p>
            </div>
        </div>

        <div class="wanotify-form-row">
            <label for="access_token"><?php _e('Token Autentikasi', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <input type="text" id="access_token" name="access_token" class="wanotify-input"
                    value="<?php echo esc_attr($access_token); ?>"
                    placeholder="f4d3b6a2e1c9" required>
                <p class="wanotify-help-text">
                    <?php _e('Token keamanan untuk autentikasi dengan API bot WhatsApp. Token dapat berisi huruf, angka, dan beberapa karakter khusus.', 'whatsapp-notify'); ?>
                </p>
            </div>
        </div>

        <div class="wanotify-form-row">
            <label for="default_recipient"><?php _e('Nomor WhatsApp Default', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <input type="text" id="default_recipient" name="default_recipient" class="wanotify-input"
                    value="<?php echo esc_attr($default_recipient); ?>"
                    placeholder="+628123456789">
                <p class="wanotify-help-text">
                    <?php _e('Nomor WhatsApp default yang akan menerima notifikasi. Format: +628123456789 atau 08123456789', 'whatsapp-notify'); ?>
                </p>
            </div>
        </div>

        <div class="wanotify-form-row">
            <label for="default_template"><?php _e('Template Pesan Default', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <textarea id="default_template" name="default_template" class="wanotify-textarea" rows="8"><?php echo esc_textarea($default_template); ?></textarea>
                <p class="wanotify-help-text">
                    <?php _e('Template pesan default untuk notifikasi. Anda dapat menggunakan variabel:', 'whatsapp-notify'); ?>
                    <br><code>{form_name}</code> - <?php _e('Nama formulir', 'whatsapp-notify'); ?>
                    <br><code>{form_id}</code> - <?php _e('ID formulir', 'whatsapp-notify'); ?>
                    <br><code>{submission_date}</code> - <?php _e('Tanggal & waktu pengisian', 'whatsapp-notify'); ?>
                    <br><code>{form_data}</code> - <?php _e('Semua data formulir', 'whatsapp-notify'); ?>
                    <br><code>{field_name}</code> - <?php _e('Nilai field tertentu', 'whatsapp-notify'); ?>
                </p>
            </div>
        </div>

        <div class="wanotify-form-row">
            <label for="enable_logging"><?php _e('Aktifkan Pencatatan Log', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <label class="wanotify-switch">
                    <input type="checkbox" id="enable_logging" name="enable_logging" <?php checked($enable_logging); ?>>
                    <span class="wanotify-slider"></span>
                </label>
                <p class="wanotify-help-text">
                    <?php _e('Catat aktivitas plugin untuk membantu pemecahan masalah.', 'whatsapp-notify'); ?>
                </p>
            </div>
        </div>

        <div class="wanotify-form-actions">
            <button type="submit" class="button button-primary wanotify-submit-btn">
                <?php _e('Simpan Pengaturan', 'whatsapp-notify'); ?>
            </button>

            <button type="button" id="wanotify-test-connection" class="button">
                <?php _e('Tes Koneksi', 'whatsapp-notify'); ?>
            </button>
        </div>
    </form>
</div>
