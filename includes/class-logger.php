<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

class FluentWA_Logger {
    private $log_enabled;
    private $log_file;
    private $timezone;
    
    public function __construct() {
        $settings = get_option('fluentwa_settings', array());
        $this->log_enabled = isset($settings['enable_logging']) ? $settings['enable_logging'] : false;
        
        // Set timezone untuk Indonesia/Jakarta (UTC+7)
        $this->timezone = new DateTimeZone('Asia/Jakarta');
        
        // Gunakan DateTime dengan zona waktu Jakarta untuk nama file
        $date = new DateTime('now', $this->timezone);
        $this->log_file = FLUENTWA_PLUGIN_DIR . 'logs/whatsapp-' . $date->format('Y-m-d') . '.log';
    }
    
    /**
     * Catat log informasi
     */
    public function info($message, $data = array()) {
        $this->log('INFO', $message, $data);
    }
    
    /**
     * Catat log error
     */
    public function error($message, $data = array()) {
        $this->log('ERROR', $message, $data);
    }
    
    /**
     * Fungsi umum untuk mencatat log
     */
    private function log($level, $message, $data = array()) {
        if (!$this->log_enabled) {
            return;
        }
        
        // Gunakan DateTime dengan zona waktu Jakarta untuk timestamp log
        $date = new DateTime('now', $this->timezone);
        
        $log_entry = sprintf(
            "[%s] [%s]: %s %s\n",
            $date->format('Y-m-d H:i:s'),
            $level,
            $message,
            !empty($data) ? json_encode($data) : ''
        );
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Ambil semua log
     */
    public function get_logs($limit = 100) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $logs = file($this->log_file);
        $logs = array_reverse($logs); // Terbaru di atas
        
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Hapus semua log
     */
    public function clear_logs() {
        if (file_exists($this->log_file)) {
            return unlink($this->log_file);
        }
        
        return true;
    }
}