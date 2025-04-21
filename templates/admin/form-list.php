<?php
/**
 * Form List Template
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wanotify-settings-section">
    <h2><?php _e('Pengaturan Formulir', 'whatsapp-notify'); ?></h2>
    <p><?php _e('Konfigurasikan notifikasi WhatsApp untuk formulir Fluent Forms tertentu.', 'whatsapp-notify'); ?></p>
    
    <?php if (empty($forms)): ?>
        <div class="notice notice-warning inline">
            <p><?php _e('Tidak ada formulir Fluent Forms yang ditemukan. Pastikan plugin Fluent Forms sudah diinstal dan formulir telah dibuat.', 'whatsapp-notify'); ?></p>
        </div>
    <?php else: ?>
        <div class="wanotify-form-list-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Judul Formulir', 'whatsapp-notify'); ?></th>
                        <th><?php _e('ID Formulir', 'whatsapp-notify'); ?></th>
                        <th><?php _e('Status Notifikasi', 'whatsapp-notify'); ?></th>
                        <th><?php _e('Aksi', 'whatsapp-notify'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <?php 
                        $is_enabled = \WANotify\Form\FormSettingsManager::is_enabled($form->id);
                        ?>
                        <tr>
                            <td><?php echo esc_html($form->title); ?></td>
                            <td><?php echo esc_html($form->id); ?></td>
                            <td>
                                <label class="wanotify-toggle">
                                    <input type="checkbox" class="wanotify-status-toggle" 
                                        data-form-id="<?php echo esc_attr($form->id); ?>" 
                                        <?php checked($is_enabled); ?>>
                                    <span class="wanotify-toggle-slider"></span>
                                    <span class="wanotify-toggle-status">
                                        <?php echo $is_enabled ? 
                                            esc_html__('Aktif', 'whatsapp-notify') : 
                                            esc_html__('Tidak Aktif', 'whatsapp-notify'); ?>
                                    </span>
                                </label>
                            </td>
                            <td>
                                <a href="?page=whatsapp-notify&tab=form_settings&form_id=<?php echo esc_attr($form->id); ?>" 
                                    class="button button-small">
                                    <?php _e('Konfigurasi', 'whatsapp-notify'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
