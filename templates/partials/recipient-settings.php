<?php
/**
 * Recipient Settings Partial
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render recipient mode settings
 * 
 * @param array $form_settings Form settings
 * @param array $phone_fields Phone fields
 * @param array $settings Global settings
 */
function wanotify_render_recipient_settings($form_settings, $phone_fields, $settings) {
    $recipient_mode = isset($form_settings['recipient_mode']) ? $form_settings['recipient_mode'] : 'default';
    $recipient = isset($form_settings['recipient']) ? $form_settings['recipient'] : '';
    $recipient_field = isset($form_settings['recipient_field']) ? $form_settings['recipient_field'] : '';
    $default_number = isset($settings['default_recipient']) ? $settings['default_recipient'] : __('(belum dikonfigurasi)', 'whatsapp-notify');
?>
    <div class="wanotify-form-row">
        <label><?php _e('Mode Penerima Notifikasi', 'whatsapp-notify'); ?></label>
        <div class="wanotify-form-input">
            <div class="wanotify-radio-group">
                <!-- Default Mode -->
                <label class="wanotify-radio">
                    <input type="radio" name="recipient_mode" value="default" 
                        <?php checked($recipient_mode, 'default'); ?>
                        class="recipient-mode-selector">
                    <span><?php _e('Gunakan Nomor Default', 'whatsapp-notify'); ?></span>
                    <p class="wanotify-help-text">
                        <?php printf(__('Menggunakan nomor default dari pengaturan umum: %s', 'whatsapp-notify'), 
                            '<strong>' . esc_html($default_number) . '</strong>'); ?>
                    </p>
                </label>
                
                <!-- Manual Mode -->
                <label class="wanotify-radio">
                    <input type="radio" name="recipient_mode" value="manual" 
                        <?php checked($recipient_mode, 'manual'); ?>
                        class="recipient-mode-selector">
                    <span><?php _e('Gunakan Nomor Kustom', 'whatsapp-notify'); ?></span>
                    <div class="recipient-mode-settings recipient-manual-settings" 
                        style="<?php echo ($recipient_mode === 'manual') ? '' : 'display: none;'; ?>">
                        <input type="text" name="recipient" class="wanotify-input" 
                            value="<?php echo esc_attr($recipient); ?>" 
                            placeholder="<?php _e('Contoh: +628123456789', 'whatsapp-notify'); ?>">
                        <p class="wanotify-help-text">
                            <?php _e('Masukkan nomor WhatsApp penerima notifikasi.', 'whatsapp-notify'); ?>
                        </p>
                    </div>
                </label>
                
                <!-- Dynamic Mode -->
                <label class="wanotify-radio <?php echo empty($phone_fields) ? 'wanotify-radio-disabled' : ''; ?>">
                    <input type="radio" name="recipient_mode" value="dynamic" 
                        <?php checked($recipient_mode, 'dynamic'); ?>
                        class="recipient-mode-selector"
                        <?php echo empty($phone_fields) ? 'disabled' : ''; ?>>
                    <span><?php _e('Ambil dari Field Formulir', 'whatsapp-notify'); ?></span>
                    
                    <?php if (empty($phone_fields)): ?>
                    <span class="wanotify-option-tooltip" 
                        title="<?php _e('Opsi ini tidak tersedia karena tidak ada field telepon di formulir ini', 'whatsapp-notify'); ?>">
                        <span class="dashicons dashicons-info"></span>
                    </span>
                    <?php endif; ?>
                    
                    <div class="recipient-mode-settings recipient-dynamic-settings" 
                        style="<?php echo ($recipient_mode === 'dynamic') ? '' : 'display: none;'; ?>">
                        <select name="recipient_field" class="wanotify-select">
                            <option value=""><?php _e('-- Pilih Field --', 'whatsapp-notify'); ?></option>
                            <?php if (empty($phone_fields)): ?>
                                <option value="" disabled>
                                    <?php _e('Tidak ada field telepon dalam formulir ini', 'whatsapp-notify'); ?>
                                </option>
                            <?php else: ?>
                                <?php foreach ($phone_fields as $field): ?>
                                <option value="<?php echo esc_attr($field['name']); ?>" 
                                    <?php selected($recipient_field, $field['name']); ?>>
                                    <?php echo esc_html($field['label']); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="wanotify-help-text">
                            <?php _e('Pilih field formulir yang berisi nomor WhatsApp penerima.', 'whatsapp-notify'); ?>
                        </p>
                    </div>
                </label>
            </div>
        </div>
    </div>
<?php
}
