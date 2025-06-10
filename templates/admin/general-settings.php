<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wanotify-admin-page">
    <h1><?php _e('Pengaturan Umum', 'whatsapp-notify'); ?></h1>
    
    <form id="wanotify-general-settings" method="post" action="options.php">
        <div class="wanotify-card">
            <div class="wanotify-card-header">
                <h2><?php _e('Konfigurasi API WhatsApp', 'whatsapp-notify'); ?></h2>
            </div>
            <div class="wanotify-card-body">
                <!-- Field Token Autentikasi -->
                <div class="wanotify-form-row">
                        <label for="access_token"><?php _e('Token Autentikasi', 'whatsapp-notify'); ?></label>
                    <div class="wanotify-form-input">
                        <input type="text" id="access_token" name="access_token" 
                               value="<?php echo esc_attr($access_token); ?>" 
                               placeholder="<?php _e('Masukkan token autentikasi', 'whatsapp-notify'); ?>" />
                        <p class="description">
                            <?php _e('Token untuk autentikasi dengan API WhatsApp.', 'whatsapp-notify'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Field lainnya -->
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
            </div>
        </div>
        
        <!-- Card-card lainnya -->
        
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
