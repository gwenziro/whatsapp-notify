<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

// Daftar tab yang tersedia
$tabs = array(
    'general' => __('Pengaturan Umum', 'fluent-whatsapp-notifier'),
    'form_settings' => __('Pengaturan Formulir', 'fluent-whatsapp-notifier'),
    'logs' => __('Log Aktivitas', 'fluent-whatsapp-notifier'),
    'help' => __('Bantuan', 'fluent-whatsapp-notifier'),
);
?>

<div class="wrap fluentwa-admin">
    <div class="fluentwa-header">
        <h1>
            <span class="dashicons dashicons-whatsapp"></span>
            <?php _e('Fluent Forms WhatsApp Notifier', 'fluent-whatsapp-notifier'); ?>
        </h1>
        <p class="fluentwa-version">v<?php echo FLUENTWA_VERSION; ?></p>
    </div>
    
    <div class="fluentwa-notifications">
        <div id="fluentwa-notification-area"></div>
    </div>
    
    <div class="fluentwa-container">
        <div class="fluentwa-tabs">
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_id => $tab_name): ?>
                    <a href="?page=fluent-whatsapp-notifier&tab=<?php echo esc_attr($tab_id); ?>" 
                       class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        
        <div class="fluentwa-content">
            <?php
            switch ($active_tab) {
                case 'general':
                    $this->render_general_settings();
                    break;
                    
                case 'form_settings':
                    $this->render_form_settings();
                    break;
                    
                case 'logs':
                    $this->render_logs();
                    break;
                    
                case 'help':
                    $this->render_help();
                    break;
                    
                default:
                    $this->render_general_settings();
                    break;
            }
            ?>
        </div>
    </div>
    
    <div class="fluentwa-footer">
        <p>
            <?php _e('Terima kasih telah menggunakan Fluent Forms WhatsApp Notifier', 'fluent-whatsapp-notifier'); ?>
        </p>
    </div>
</div>