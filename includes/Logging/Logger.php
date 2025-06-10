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
     * Cache pesan log untuk mencegah log duplikat dalam satu sesi PHP
     *
     * @var array
     */
    private static $log_cache = [];

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
     * @param string $category Kategori log (general, initialization, dll)
     */
    public function info($message, $data = [], $category = 'general')
    {
        $this->write_log('INFO', $message, $data, $category);
    }

    /**
     * Log error
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     * @param string $category Kategori log (general, error, dll)
     */
    public function error($message, $data = [], $category = 'error')
    {
        $this->write_log('ERROR', $message, $data, $category);
    }

    /**
     * Log warning
     *
     * @param string $message Pesan log
     * @param array  $data    Data tambahan untuk log
     * @param string $category Kategori log (general, warning, dll)
     */
    public function warning($message, $data = [], $category = 'warning')
    {
        $this->write_log('WARNING', $message, $data, $category);
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
     * @param string $category Kategori log (general, initialization, dll)
     */
    private function write_log($level, $message, $data = [], $category = 'general')
    {
        // Cek apakah logging diaktifkan - default ke true
        $settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
        $logging_enabled = isset($settings['enable_logging']) ? $settings['enable_logging'] : true;

        // Buat kunci cache untuk mencegah log duplikat pada sesi yang sama
        // Gunakan category untuk memisahkan pengecekan duplikasi berdasarkan kategori
        $cache_key = $category . '_' . md5($level . $message . json_encode($data));
        
        // Cek untuk log initialization:
        // - Jika dalam sesi PHP sama dengan category 'initialization'
        // - Dan telah dicatat sebelumnya
        // - Maka skip (hindari duplikasi)
        if ($category === 'initialization' && isset(self::$log_cache[$cache_key])) {
            return;
        }
        
        // Mendeteksi apakah ini AJAX request
        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
        
        // Jika ini adalah log inisialisasi dan sedang dalam AJAX request, skip untuk mengurangi spam
        if ($category === 'initialization' && $is_ajax) {
            // Tetap catat dalam cache untuk mencegah duplikasi di masa mendatang
            self::$log_cache[$cache_key] = true;
            return;
        }

        // Keyword penting untuk selalu dilog
        $important_keywords = ['notification sent', 'berhasil', 'sukses', 'failed', 'gagal', 'error'];
        $is_important = false;
        
        foreach ($important_keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $is_important = true;
                break;
            }
        }

        // Skip log yang tidak penting jika logging dinonaktifkan
        if (!$logging_enabled && $level !== 'ERROR' && $level !== 'WARNING' && !$is_important) {
            return;
        }

        $time = date_i18n('Y-m-d H:i:s');
        $entry = "[$level] [$time] $message";

        // Tambahkan data tambahan jika ada
        if (!empty($data)) {
            // Filter data sensitif sebelum dilog
            $filtered_data = $this->filter_sensitive_data($data);
            $entry .= ' ' . wp_json_encode($filtered_data);
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
        
        // Catat dalam cache untuk mencegah duplikasi
        self::$log_cache[$cache_key] = true;
    }

    /**
     * Filter data sensitif sebelum dilog
     *
     * @param array $data Data yang akan difilter
     * @return array Data yang sudah difilter
     */
    private function filter_sensitive_data($data)
    {
        $filtered = $data;
        
        // Filter token dari log
        if (isset($filtered['token']) && !empty($filtered['token'])) {
            $filtered['token'] = '***' . substr($filtered['token'], -4);
        }
        
        if (isset($filtered['access_token']) && !empty($filtered['access_token'])) {
            $filtered['access_token'] = '***' . substr($filtered['access_token'], -4);
        }
        
        // Filter info kredensial lainnya
        $sensitive_keys = ['password', 'key', 'secret', 'api_key'];
        foreach ($sensitive_keys as $key) {
            if (isset($filtered[$key]) && !empty($filtered[$key])) {
                $filtered[$key] = '***HIDDEN***';
            }
        }
        
        return $filtered;
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

        // PERUBAHAN: Format log untuk tampilan yang lebih baik jika diminta
        if ($formatted) {
            $logs = array_map(function($log) {
                return $this->format_log_for_display($log);
            }, $logs);
        }

        return $logs;
    }

    /**
     * Format log untuk tampilan
     *
     * @param string $log_entry Log entry string
     * @return string Formatted log entry
     */
    private function format_log_for_display($log_entry)
    {
        // Pisahkan level, timestamp, dan pesan
        if (preg_match('/^\[([^\]]+)\] \[([^\]]+)\] (.+?)(\s\{.*\})?$/', $log_entry, $matches)) {
            $level = $matches[1];
            $time = $matches[2];
            $message = $matches[3];
            $data = isset($matches[4]) ? $matches[4] : '';
            
            // Format data JSON untuk tampilan yang lebih baik
            if (!empty($data)) {
                $data = json_decode(trim($data), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Format ulang JSON untuk lebih mudah dibaca
                    $data = json_encode($data, JSON_PRETTY_PRINT);
                    $data = str_replace('{', '{ ', $data);
                    $data = str_replace('}', ' }', $data);
                }
            }
            
            // Format level dengan warna berbeda untuk tampilan
            $level_class = strtolower($level);
            
            return $log_entry; // Tetap kembalikan format asli (akan diformat di template)
        }
        
        return $log_entry;
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
