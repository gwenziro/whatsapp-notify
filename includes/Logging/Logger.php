<?php

/**
 * Kelas Logger
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Logging;

use WANotify\Core\Constants;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Logger
 * 
 * Menangani pencatatan log aktivitas plugin
 */
class Logger
{
    /**
     * Path ke file log
     *
     * @var string
     */
    private $log_file;

    /**
     * Maksimum jumlah entri log
     *
     * @var int
     */
    private $max_logs = 500;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->log_file = WANOTIFY_PLUGIN_DIR . 'logs/whatsapp-notify.log';

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
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     */
    public function info($message, $data = [])
    {
        $this->write_log('INFO', $message, $data);
    }

    /**
     * Log error
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     */
    public function error($message, $data = [])
    {
        $this->write_log('ERROR', $message, $data);
    }

    /**
     * Log warning
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     */
    public function warning($message, $data = [])
    {
        $this->write_log('WARNING', $message, $data);
    }

    /**
     * Log debug info
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     */
    public function debug($message, $data = [])
    {
        // Cek apakah debug logging diaktifkan
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
        $debug_enabled = isset($settings['debug_logging']) && $settings['debug_logging'];

        if ($debug_enabled) {
            $this->write_log('DEBUG', $message, $data);
        }
    }

    /**
     * Metode generik untuk menulis log
     *
     * @param string $level   Level log (INFO, ERROR, WARNING, DEBUG)
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     */
    private function write_log($level, $message, $data = [])
    {
        // Cek apakah logging diaktifkan
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
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
     *
     * @param bool $formatted Apakah perlu memformat log untuk tampilan
     * @return array Daftar log
     */
    public function get_logs($formatted = true)
    {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $content = file_get_contents($this->log_file);
        if (empty($content)) {
            return [];
        }

        $logs = explode("\n", $content);
        $logs = array_filter($logs);

        return $logs;
    }

    /**
     * Bersihkan semua log
     *
     * @return bool Berhasil atau tidak
     */
    public function clear_logs()
    {
        if (file_exists($this->log_file)) {
            return file_put_contents($this->log_file, '') !== false;
        }

        return true;
    }

    /**
     * Log informasi umum dalam format standar (alias untuk info)
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     */
    public function log_info($message, $data = [])
    {
        $this->info($message, $data);
    }

    /**
     * Log error dalam format standar (alias untuk error)
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     */
    public function log_error($message, $data = [])
    {
        $this->error($message, $data);
    }
}
