<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

class FluentWA_Logger {
    private $log_file;
    private $max_logs = 500; // Maksimum jumlah entri log
    
    public function __construct() {
        $this->log_file = FLUENTWA_PLUGIN_DIR . 'logs/whatsapp-notifier.log';
        
        // Pastikan direktori log ada
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // Tambahkan file .htaccess untuk keamanan
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
        }
    }
    
    /**
     * Log informasi
     */
    public function info($message, $data = array()) {
        $this->write_log('INFO', $message, $data);
    }
    
    /**
     * Log error
     */
    public function error($message, $data = array()) {
        $this->write_log('ERROR', $message, $data);
    }
    
    /**
     * Log warning
     */
    public function warning($message, $data = array()) {
        $this->write_log('WARNING', $message, $data);
    }
    
    /**
     * Log debug info
     */
    public function debug($message, $data = array()) {
        // Cek apakah debug logging diaktifkan
        $settings = get_option('fluentwa_settings', array());
        $debug_enabled = isset($settings['debug_logging']) && $settings['debug_logging'];
        
        if ($debug_enabled) {
            $this->write_log('DEBUG', $message, $data);
        }
    }
    
    /**
     * Metode generik untuk menulis log
     */
    private function write_log($level, $message, $data = array()) {
        // Cek apakah logging diaktifkan
        $settings = get_option('fluentwa_settings', array());
        $logging_enabled = isset($settings['enable_logging']) ? $settings['enable_logging'] : false;
        
        if (!$logging_enabled && $level !== 'ERROR') {
            // Selalu log pesan error, bahkan jika logging dinonaktifkan
            return;
        }
        
        $time = date_i18n('Y-m-d H:i:s');
        $entry = "[$level] [$time] $message";
        
        // Tambahkan data tambahan jika ada
        if (!empty($data)) {
            $entry .= ' ' . wp_json_encode($data);
        }
        
        // Baca log yang ada
        $logs = $this->get_logs(false);
        
        // Tambahkan entri baru
        array_unshift($logs, $entry);
        
        // Batasi jumlah entri
        if (count($logs) > $this->max_logs) {
            $logs = array_slice($logs, 0, $this->max_logs);
        }
        
        // Tulis kembali ke file
        file_put_contents($this->log_file, implode("\n", $logs));
    }
    
    /**
     * Ambil semua log
     */
    public function get_logs($formatted = true) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $content = file_get_contents($this->log_file);
        if (empty($content)) {
            return array();
        }
        
        $logs = explode("\n", $content);
        
        if ($formatted) {
            // Format log entries for display
            foreach ($logs as &$log) {
                if (strpos($log, '[ERROR]') !== false) {
                    $log = '<span class="log-error">' . $log . '</span>';
                } elseif (strpos($log, '[WARNING]') !== false) {
                    $log = '<span class="log-warning">' . $log . '</span>';
                } elseif (strpos($log, '[DEBUG]') !== false) {
                    $log = '<span class="log-debug">' . $log . '</span>';
                } else {
                    $log = '<span class="log-info">' . $log . '</span>';
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Bersihkan semua log
     */
    public function clear_logs() {
        if (file_exists($this->log_file)) {
            return file_put_contents($this->log_file, '');
        }
        
        return true;
    }
    
    /**
     * Log informasi umum dalam format standar
     */
    public function log_info($message, $data = array()) {
        $this->info($message, $data);
    }
    
    /**
     * Log error dalam format standar
     */
    public function log_error($message, $data = array()) {
        $this->error($message, $data);
    }
}