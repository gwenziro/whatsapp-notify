<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wanotify-settings-section">
    <div class="wanotify-section-header">
        <h2>
            <?php printf(__('Pengaturan WhatsApp untuk: %s', 'whatsapp-notify'), esc_html($form_title)); ?>
        </h2>
        <a href="?page=whatsapp-notify&tab=form_settings" class="button wanotify-back-btn">
            <span class="dashicons dashicons-arrow-left-alt"></span> 
            <?php _e('Kembali ke Daftar', 'whatsapp-notify'); ?>
        </a>
    </div>
    
    <form id="wanotify-form-settings" class="wanotify-form" data-form-id="<?php echo esc_attr($form_id); ?>">
        <div class="wanotify-form-row">
            <label for="enabled"><?php _e('Aktifkan Notifikasi', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <label class="wanotify-switch">
                    <input type="checkbox" id="enabled" name="enabled" <?php checked(!empty($form_settings['enabled'])); ?>>
                    <span class="wanotify-slider"></span>
                </label>
                <p class="wanotify-help-text">
                    <?php _e('Aktifkan pengiriman notifikasi WhatsApp untuk formulir ini.', 'whatsapp-notify'); ?>
                </p>
            </div>
        </div>
        
        <div class="wanotify-form-row">
            <label><?php _e('Mode Penerima Notifikasi', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <div class="wanotify-radio-group">
                    <label class="wanotify-radio">
                        <input type="radio" name="recipient_mode" value="default" 
                            <?php checked(isset($form_settings['recipient_mode']) ? $form_settings['recipient_mode'] : 'default', 'default'); ?>
                            class="recipient-mode-selector">
                        <span><?php _e('Gunakan Nomor Default', 'whatsapp-notify'); ?></span>
                        <p class="wanotify-help-text">
                            <?php 
                            $default_number = isset($settings['default_recipient']) ? $settings['default_recipient'] : __('(belum dikonfigurasi)', 'whatsapp-notify');
                            printf(__('Menggunakan nomor default dari pengaturan umum: %s', 'whatsapp-notify'), '<strong>' . esc_html($default_number) . '</strong>'); 
                            ?>
                        </p>
                    </label>
                    
                    <label class="wanotify-radio">
                        <input type="radio" name="recipient_mode" value="manual" 
                            <?php checked(isset($form_settings['recipient_mode']) ? $form_settings['recipient_mode'] : '', 'manual'); ?>
                            class="recipient-mode-selector">
                        <span><?php _e('Gunakan Nomor Kustom', 'whatsapp-notify'); ?></span>
                        <div class="recipient-mode-settings recipient-manual-settings" style="<?php echo (isset($form_settings['recipient_mode']) && $form_settings['recipient_mode'] === 'manual') ? '' : 'display: none;'; ?>">
                            <input type="text" name="recipient" class="wanotify-input" 
                                value="<?php echo esc_attr(isset($form_settings['recipient']) ? $form_settings['recipient'] : ''); ?>" 
                                placeholder="<?php _e('Contoh: +628123456789', 'whatsapp-notify'); ?>">
                            <p class="wanotify-help-text"><?php _e('Masukkan nomor WhatsApp penerima notifikasi.', 'whatsapp-notify'); ?></p>
                        </div>
                    </label>
                    
                    <!-- Opsi "Ambil dari Field Formulir" -->
                    <label class="wanotify-radio <?php echo empty($phone_fields) ? 'wanotify-radio-disabled' : ''; ?>">
                        <input type="radio" name="recipient_mode" value="dynamic" 
                            <?php checked(isset($form_settings['recipient_mode']) ? $form_settings['recipient_mode'] : '', 'dynamic'); ?>
                            class="recipient-mode-selector"
                            <?php echo empty($phone_fields) ? 'disabled' : ''; ?>>
                        <span><?php _e('Ambil dari Field Formulir', 'whatsapp-notify'); ?></span>
                        
                        <?php if (empty($phone_fields)): ?>
                        <span class="wanotify-option-tooltip" title="<?php _e('Opsi ini tidak tersedia karena tidak ada field telepon di formulir ini', 'whatsapp-notify'); ?>">
                            <span class="dashicons dashicons-info"></span>
                        </span>
                        <?php endif; ?>
                        
                        <div class="recipient-mode-settings recipient-dynamic-settings" style="<?php echo (isset($form_settings['recipient_mode']) && $form_settings['recipient_mode'] === 'dynamic') ? '' : 'display: none;'; ?>">
                            <select name="recipient_field" class="wanotify-select">
                                <option value=""><?php _e('-- Pilih Field --', 'whatsapp-notify'); ?></option>
                                <?php if (empty($phone_fields)): ?>
                                    <option value="" disabled><?php _e('Tidak ada field telepon dalam formulir ini', 'whatsapp-notify'); ?></option>
                                <?php else: ?>
                                    <?php foreach ($phone_fields as $field): ?>
                                    <option value="<?php echo esc_attr($field['name']); ?>" <?php selected(isset($form_settings['recipient_field']) ? $form_settings['recipient_field'] : '', $field['name']); ?>>
                                        <?php echo esc_html($field['label']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p class="wanotify-help-text"><?php _e('Pilih field formulir yang berisi nomor WhatsApp penerima.', 'whatsapp-notify'); ?></p>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="wanotify-form-row">
            <label for="message_template"><?php _e('Template Pesan', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <textarea id="message_template" name="message_template" class="wanotify-textarea" rows="8"><?php 
                    echo esc_textarea($form_settings['message_template'] ?? ''); 
                ?></textarea>
                <p class="wanotify-help-text">
                    <?php _e('Template pesan notifikasi. Kosongkan untuk menggunakan template default.', 'whatsapp-notify'); ?>
                    <br><?php _e('Anda dapat menggunakan variabel yang tersedia seperti di pengaturan umum dan nama-nama field formulir di dalam kurung kurawal.', 'whatsapp-notify'); ?>
                    <br><br><strong><?php _e('Field yang tersedia:', 'whatsapp-notify'); ?></strong>
                    <?php foreach ($form_fields as $field): ?>
                        <br><code>{<?php echo esc_html($field['name']); ?>}</code> - <?php echo esc_html($field['label']); ?>
                    <?php endforeach; ?>
                </p>
            </div>
        </div>
        
        <div class="wanotify-form-row">
            <label><?php _e('Field yang Disertakan', 'whatsapp-notify'); ?></label>
            <div class="wanotify-form-input">
                <div class="wanotify-fields-selector">
                    <div class="wanotify-checkbox-group">
                        <label class="wanotify-checkbox">
                            <input type="checkbox" id="include_all_fields" name="include_all_fields" value="all"
                                <?php checked(isset($form_settings['included_fields']) && in_array('*', $form_settings['included_fields'])); ?>>
                            <span><?php _e('Semua Field', 'whatsapp-notify'); ?></span>
                        </label>
                    </div>
                    
                    <div class="wanotify-checkbox-group wanotify-field-list" id="field_list" <?php echo (isset($form_settings['included_fields']) && in_array('*', $form_settings['included_fields'])) ? 'style="display:none"' : ''; ?>>
                        <?php foreach ($form_fields as $field): ?>
                            <label class="wanotify-checkbox">
                                <input type="checkbox" name="included_fields[]" value="<?php echo esc_attr($field['name']); ?>"
                                    <?php checked(isset($form_settings['included_fields']) && (in_array($field['name'], $form_settings['included_fields']) || in_array('*', $form_settings['included_fields']))); ?>>
                                <span><?php echo esc_html($field['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="wanotify-help-text">
                    <?php _e('Pilih field yang akan disertakan dalam pesan notifikasi ketika menggunakan variabel {form_data}.', 'whatsapp-notify'); ?>
                </p>
            </div>
        </div>
        
        <div class="wanotify-form-actions">
            <button type="submit" class="button button-primary wanotify-submit-btn"><?php _e('Simpan Pengaturan', 'whatsapp-notify'); ?></button>
            <button type="button" id="wanotify-test-form-notification" class="button"><?php _e('Kirim Tes Notifikasi', 'whatsapp-notify'); ?></button>
            <!-- Tooltip container akan ditambahkan secara dinamis oleh JavaScript -->
        </div>
    </form>
</div>
