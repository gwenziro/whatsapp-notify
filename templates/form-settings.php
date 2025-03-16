<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
?>

<?php if ($form_id > 0): ?>
    <!-- Pengaturan untuk formulir tertentu -->
    <div class="fluentwa-settings-section">
        <div class="fluentwa-section-header">
            <h2>
                <?php printf(__('Pengaturan WhatsApp untuk: %s', 'fluent-whatsapp-notifier'), esc_html($form_title)); ?>
            </h2>
            <a href="?page=fluent-whatsapp-notifier&tab=form_settings" class="button">
                <span class="dashicons dashicons-arrow-left-alt"></span> 
                <?php _e('Kembali ke Daftar', 'fluent-whatsapp-notifier'); ?>
            </a>
        </div>
        
        <form id="fluentwa-form-settings" class="fluentwa-form" data-form-id="<?php echo $form_id; ?>">
            <div class="fluentwa-form-row">
                <label for="enabled"><?php _e('Aktifkan Notifikasi', 'fluent-whatsapp-notifier'); ?></label>
                <div class="fluentwa-form-input">
                    <label class="fluentwa-switch">
                        <input type="checkbox" id="enabled" name="enabled" <?php checked($form_settings['enabled'] ?? false); ?>>
                        <span class="fluentwa-slider"></span>
                    </label>
                    <p class="fluentwa-help-text">
                        <?php _e('Aktifkan pengiriman notifikasi WhatsApp untuk formulir ini.', 'fluent-whatsapp-notifier'); ?>
                    </p>
                </div>
            </div>
            
           <div class="fluentwa-form-row">
    <label><?php _e('Mode Penerima Notifikasi', 'fluent-whatsapp-notifier'); ?></label>
    <div class="fluentwa-form-input">
        <div class="fluentwa-radio-group">
            <label class="fluentwa-radio">
                <input type="radio" name="recipient_mode" value="default" 
                       <?php checked(isset($form_settings['recipient_mode']) ? $form_settings['recipient_mode'] : 'default', 'default'); ?>
                       class="recipient-mode-selector">
                <span><?php _e('Gunakan Nomor Default', 'fluent-whatsapp-notifier'); ?></span>
                <p class="fluentwa-help-text">
                    <?php 
                    $default_number = isset($settings['default_recipient']) ? $settings['default_recipient'] : __('(belum dikonfigurasi)', 'fluent-whatsapp-notifier');
                    printf(__('Menggunakan nomor default dari pengaturan umum: %s', 'fluent-whatsapp-notifier'), '<strong>' . esc_html($default_number) . '</strong>'); 
                    ?>
                </p>
            </label>
            
            <label class="fluentwa-radio">
                <input type="radio" name="recipient_mode" value="manual" 
                       <?php checked(isset($form_settings['recipient_mode']) ? $form_settings['recipient_mode'] : '', 'manual'); ?>
                       class="recipient-mode-selector">
                <span><?php _e('Gunakan Nomor Kustom', 'fluent-whatsapp-notifier'); ?></span>
                <div class="recipient-mode-settings recipient-manual-settings" style="<?php echo (isset($form_settings['recipient_mode']) && $form_settings['recipient_mode'] === 'manual') ? '' : 'display: none;'; ?>">
                    <input type="text" name="recipient" class="fluentwa-input" 
                           value="<?php echo esc_attr(isset($form_settings['recipient']) ? $form_settings['recipient'] : ''); ?>" 
                           placeholder="Contoh: +628123456789">
                    <p class="fluentwa-help-text"><?php _e('Masukkan nomor WhatsApp penerima notifikasi.', 'fluent-whatsapp-notifier'); ?></p>
                </div>
            </label>
            
            <label class="fluentwa-radio">
                <input type="radio" name="recipient_mode" value="dynamic" 
                       <?php checked(isset($form_settings['recipient_mode']) ? $form_settings['recipient_mode'] : '', 'dynamic'); ?>
                       class="recipient-mode-selector">
                <span><?php _e('Ambil dari Field Formulir', 'fluent-whatsapp-notifier'); ?></span>
                <div class="recipient-mode-settings recipient-dynamic-settings" style="<?php echo (isset($form_settings['recipient_mode']) && $form_settings['recipient_mode'] === 'dynamic') ? '' : 'display: none;'; ?>">
                    <select name="recipient_field" class="fluentwa-select">
                        <option value=""><?php _e('-- Pilih Field --', 'fluent-whatsapp-notifier'); ?></option>
                        <?php if (empty($phone_fields)): ?>
                            <option value="" disabled><?php _e('Tidak ada field telepon dalam formulir ini', 'fluent-whatsapp-notifier'); ?></option>
                        <?php else: ?>
                            <?php foreach ($phone_fields as $field): ?>
                            <option value="<?php echo esc_attr($field['name']); ?>" <?php selected(isset($form_settings['recipient_field']) ? $form_settings['recipient_field'] : '', $field['name']); ?>>
                                <?php echo esc_html($field['label']); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="fluentwa-help-text"><?php _e('Pilih field formulir yang berisi nomor WhatsApp penerima.', 'fluent-whatsapp-notifier'); ?></p>
                </div>
            </label>
        </div>
    </div>
</div>
            
            <div class="fluentwa-form-row">
                <label for="message_template"><?php _e('Template Pesan', 'fluent-whatsapp-notifier'); ?></label>
                <div class="fluentwa-form-input">
                    <textarea id="message_template" name="message_template" class="fluentwa-textarea" rows="8"><?php 
                        echo esc_textarea($form_settings['message_template'] ?? ''); 
                    ?></textarea>
                    <p class="fluentwa-help-text">
                        <?php _e('Template pesan notifikasi. Kosongkan untuk menggunakan template default.', 'fluent-whatsapp-notifier'); ?>
                        <br><?php _e('Anda dapat menggunakan variabel yang tersedia seperti di pengaturan umum dan nama-nama field formulir di dalam kurung kurawal.', 'fluent-whatsapp-notifier'); ?>
                        <br><br><strong><?php _e('Field yang tersedia:', 'fluent-whatsapp-notifier'); ?></strong>
                        <?php foreach ($form_fields as $field): ?>
                            <br><code>{<?php echo $field['name']; ?>}</code> - <?php echo esc_html($field['label']); ?>
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>
            
            <div class="fluentwa-form-row">
                <label><?php _e('Field yang Disertakan', 'fluent-whatsapp-notifier'); ?></label>
                <div class="fluentwa-form-input">
                    <div class="fluentwa-fields-selector">
                        <div class="fluentwa-checkbox-group">
                            <label class="fluentwa-checkbox">
                                <input type="checkbox" id="include_all_fields" name="include_all_fields" value="all"
                                    <?php checked(isset($form_settings['included_fields']) && in_array('*', $form_settings['included_fields'])); ?>>
                                <span><?php _e('Semua Field', 'fluent-whatsapp-notifier'); ?></span>
                            </label>
                        </div>
                        
                        <div class="fluentwa-checkbox-group fluentwa-field-list" id="field_list">
                            <?php foreach ($form_fields as $field): ?>
                                <label class="fluentwa-checkbox">
                                    <input type="checkbox" name="included_fields[]" value="<?php echo esc_attr($field['name']); ?>"
                                        <?php checked(isset($form_settings['included_fields']) && in_array($field['name'], $form_settings['included_fields'])); ?>>
                                    <span><?php echo esc_html($field['label']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p class="fluentwa-help-text">
                        <?php _e('Pilih field yang akan disertakan dalam pesan notifikasi ketika menggunakan variabel {form_data}.', 'fluent-whatsapp-notifier'); ?>
                    </p>
                </div>
            </div>
            
            <div class="fluentwa-form-actions">
                <button type="submit" class="button button-primary fluentwa-submit-btn">
                    <?php _e('Simpan Pengaturan', 'fluent-whatsapp-notifier'); ?>
                </button>
                
                <button type="button" id="fluentwa-test-form-notification" class="button">
                    <?php _e('Kirim Tes Notifikasi', 'fluent-whatsapp-notifier'); ?>
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Daftar semua formulir -->
    <div class="fluentwa-settings-section">
        <h2><?php _e('Pengaturan Formulir', 'fluent-whatsapp-notifier'); ?></h2>
        <p><?php _e('Konfigurasikan notifikasi WhatsApp untuk formulir Fluent Forms tertentu.', 'fluent-whatsapp-notifier'); ?></p>
        
        <div class="fluentwa-form-list-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Judul Formulir', 'fluent-whatsapp-notifier'); ?></th>
                        <th><?php _e('ID Formulir', 'fluent-whatsapp-notifier'); ?></th>
                        <th><?php _e('Status Notifikasi', 'fluent-whatsapp-notifier'); ?></th>
                        <th><?php _e('Aksi', 'fluent-whatsapp-notifier'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($forms)): ?>
                        <tr>
                            <td colspan="4"><?php _e('Tidak ada formulir Fluent Forms yang ditemukan.', 'fluent-whatsapp-notifier'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                            <?php 
                            $form_settings = get_option("fluentwa_form_settings_{$form->id}", array());
                            $is_enabled = !empty($form_settings) && isset($form_settings['enabled']) && $form_settings['enabled'] === true;
                            ?>
                            <tr>
                                <td><?php echo esc_html($form->title); ?></td>
                                <td><?php echo esc_html($form->id); ?></td>
                                <td>
                                    <?php if ($is_enabled): ?>
                                        <span class="fluentwa-status fluentwa-status-active">
                                            <?php _e('Aktif', 'fluent-whatsapp-notifier'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="fluentwa-status fluentwa-status-inactive">
                                            <?php _e('Tidak Aktif', 'fluent-whatsapp-notifier'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?page=fluent-whatsapp-notifier&tab=form_settings&form_id=<?php echo $form->id; ?>" 
                                       class="button button-small">
                                        <?php _e('Konfigurasi', 'fluent-whatsapp-notifier'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>