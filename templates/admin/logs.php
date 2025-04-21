<?php
/**
 * Admin Log Template
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wanotify-logs-section">
    <div class="wanotify-section-header">
        <h2><?php _e('Log Aktivitas', 'whatsapp-notify'); ?></h2>
        <div class="wanotify-logs-actions">
            <button id="wanotify-clear-logs" class="button"><?php _e('Bersihkan Log', 'whatsapp-notify'); ?></button>
        </div>
    </div>

    <p class="wanotify-logs-info"><?php _e('Log aktivitas plugin membantu troubleshooting jika terjadi masalah.', 'whatsapp-notify'); ?></p>

    <div class="wanotify-logs-container" id="wanotify-logs-container">
        <?php if (empty($logs)) : ?>
            <p><?php _e('Tidak ada log aktivitas.', 'whatsapp-notify'); ?></p>
        <?php else : ?>
            <div class="wanotify-log-entries">
                <?php foreach ($logs as $log) : 
                    // Extract log level for styling
                    $log_level = '';
                    $css_class = '';
                    
                    if (strpos($log, '[INFO]') !== false) {
                        $log_level = 'info';
                        $css_class = 'log-info';
                    } elseif (strpos($log, '[ERROR]') !== false) {
                        $log_level = 'error';
                        $css_class = 'log-error';
                    } elseif (strpos($log, '[WARNING]') !== false) {
                        $log_level = 'warning';
                        $css_class = 'log-warning';
                    } elseif (strpos($log, '[DEBUG]') !== false) {
                        $log_level = 'debug';
                        $css_class = 'log-debug';
                    }
                ?>
                    <pre class="wanotify-log-entry <?php echo esc_attr($css_class); ?>" data-level="<?php echo esc_attr($log_level); ?>"><?php echo esc_html($log); ?></pre>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
