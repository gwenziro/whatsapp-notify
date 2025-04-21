<?php
/**
 * Main Admin Template
 * 
 * @package WhatsApp_Notify
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

// Daftar tab yang tersedia
$tabs = array(
    'general' => __('Pengaturan Umum', 'whatsapp-notify'),
    'form_settings' => __('Pengaturan Formulir', 'whatsapp-notify'),
    'logs' => __('Log Aktivitas', 'whatsapp-notify'),
    'help' => __('Bantuan', 'whatsapp-notify'),
);
?>

<div class="wrap wanotify-admin">
    <div class="wanotify-header">
        <h1>
            <span class="dashicons dashicons-whatsapp"></span>
            <?php _e('WhatsApp Notify', 'whatsapp-notify'); ?>
        </h1>
        <p class="wanotify-version">v<?php echo WANOTIFY_VERSION; ?></p>
        <span id="wanotify-dirty-state" class="wanotify-dirty-state"></span>
    </div>
    
    <?php include WANOTIFY_PLUGIN_DIR . 'templates/partials/notification-bar.php'; ?>
    
    <div class="wanotify-container">
        <div class="wanotify-tabs">
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_id => $tab_name): ?>
                    <a href="?page=whatsapp-notify&tab=<?php echo esc_attr($tab_id); ?>" 
                       class="nav-tab <?php echo $this->active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        
        <div class="wanotify-content">
            <?php $this->render_tab_content(); ?>
        </div>
    </div>
    
    <div class="wanotify-footer">
        <p>
            <?php _e('Terima kasih telah menggunakan WhatsApp Notify', 'whatsapp-notify'); ?>
        </p>
    </div>
</div>
