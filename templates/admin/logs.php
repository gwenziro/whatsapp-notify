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

<div class="wrap wanotify-admin-page">
    <h1><?php _e('Log Aktivitas', 'whatsapp-notify'); ?></h1>
    
    <div class="wanotify-card">
        <div class="wanotify-card-header">
            <h2><?php _e('Log Aktivitas Plugin', 'whatsapp-notify'); ?></h2>
            
            <div class="wanotify-card-actions">
                <button id="wanotify-clear-logs" class="button button-secondary">
                    <?php _e('Bersihkan Log', 'whatsapp-notify'); ?>
                </button>
            </div>
        </div>
        <div class="wanotify-card-body">
            <div id="wanotify-logs-container" class="wanotify-logs">
                <?php if (empty($logs)): ?>
                    <p><?php _e('Tidak ada log aktivitas.', 'whatsapp-notify'); ?></p>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        // Parse log entry untuk tampilan yang lebih baik
                        $log_class = 'wanotify-log-item';
                        
                        // Ekstrak level untuk styling
                        if (preg_match('/^\[([^\]]+)\]/', $log, $level_match)) {
                            $level = strtolower($level_match[1]);
                            $log_class .= ' wanotify-log-' . $level;
                            
                            // Highlight log penting
                            if ($level === 'error' || $level === 'warning') {
                                $log_class .= ' wanotify-log-highlight';
                            }
                            
                            // Highlight log aktivitas penting
                            $important_terms = ['berhasil', 'sukses', 'success', 'sent', 'dikirim', 'terkirim'];
                            foreach ($important_terms as $term) {
                                if (stripos($log, $term) !== false) {
                                    $log_class .= ' wanotify-log-success';
                                    break;
                                }
                            }
                        }
                        
                        // Format JSON data
                        $formatted_log = $log;
                        if (preg_match('/(.*?)(\s\{.*\})$/', $log, $json_match)) {
                            $log_text = $json_match[1];
                            $log_data = $json_match[2];
                            
                            // Format data untuk tampilan yang lebih baik
                            $json_data = json_decode($log_data, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $formatted_data = '<div class="wanotify-log-data">';
                                foreach ($json_data as $key => $value) {
                                    if (is_array($value)) {
                                        $value = json_encode($value);
                                    }
                                    $formatted_data .= "<span class='wanotify-log-key'>{$key}:</span> <span class='wanotify-log-value'>{$value}</span> ";
                                }
                                $formatted_data .= '</div>';
                                $formatted_log = $log_text . $formatted_data;
                            }
                        }
                    ?>
                        <div class="<?php echo esc_attr($log_class); ?>">
                            <?php echo wp_kses_post($formatted_log); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Tambahkan gaya untuk log */
.wanotify-logs {
    max-height: 600px;
    overflow-y: auto;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}

.wanotify-log-item {
    padding: 8px 10px;
    margin-bottom: 5px;
    border-left: 3px solid #ccc;
    background: #fff;
    font-family: monospace;
    word-break: break-word;
}

.wanotify-log-info {
    border-left-color: #2271b1;
}

.wanotify-log-error {
    border-left-color: #d63638;
    background-color: #fef7f7;
}

.wanotify-log-warning {
    border-left-color: #dba617;
    background-color: #fffbf0;
}

.wanotify-log-success {
    border-left-color: #00a32a;
    background-color: #f0fff5;
}

.wanotify-log-highlight {
    font-weight: bold;
}

.wanotify-log-data {
    margin-top: 5px;
    padding-left: 10px;
    font-size: 0.9em;
    color: #666;
}

.wanotify-log-key {
    font-weight: bold;
    color: #2271b1;
}

.wanotify-log-value {
    color: #3c434a;
    margin-right: 5px;
}
</style>
