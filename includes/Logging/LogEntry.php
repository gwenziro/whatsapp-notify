<?php

/**
 * Kelas LogEntry
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Logging;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class LogEntry
 * 
 * Representasi data dari sebuah entri log
 */
class LogEntry
{
    /**
     * Level dari log (INFO, ERROR, WARNING, DEBUG)
     *
     * @var string
     */
    private $level;

    /**
     * Timestamp saat log dibuat
     *
     * @var int
     */
    private $timestamp;

    /**
     * Pesan log
     *
     * @var string
     */
    private $message;

    /**
     * Data tambahan
     *
     * @var array
     */
    private $data;

    /**
     * Constructor
     *
     * @param string $level     Level log (INFO, ERROR, WARNING, DEBUG)
     * @param string $message   Pesan log
     * @param array  $data      Data tambahan
     * @param int    $timestamp Timestamp (opsional)
     */
    public function __construct($level, $message, $data = [], $timestamp = null)
    {
        $this->level = strtoupper($level);
        $this->message = $message;
        $this->data = $data;
        $this->timestamp = $timestamp ?: time();
    }

    /**
     * Mendapatkan level log
     *
     * @return string
     */
    public function get_level()
    {
        return $this->level;
    }

    /**
     * Mendapatkan timestamp
     *
     * @return int
     */
    public function get_timestamp()
    {
        return $this->timestamp;
    }

    /**
     * Mendapatkan pesan log
     *
     * @return string
     */
    public function get_message()
    {
        return $this->message;
    }

    /**
     * Mendapatkan data tambahan
     *
     * @return array
     */
    public function get_data()
    {
        return $this->data;
    }

    /**
     * Konversi ke format string untuk disimpan
     *
     * @return string
     */
    public function to_string()
    {
        $time = date_i18n('Y-m-d H:i:s', $this->timestamp);
        $entry = "[{$this->level}] [{$time}] {$this->message}";

        if (!empty($this->data)) {
            $entry .= ' ' . wp_json_encode($this->data);
        }

        return $entry;
    }

    /**
     * Parse log entry dari string
     *
     * @param string $log_string String log
     * @return LogEntry|null
     */
    public static function from_string($log_string)
    {
        // Format: [LEVEL] [YYYY-MM-DD HH:MM:SS] Message {Data JSON}
        if (preg_match('/^\[([^\]]+)\] \[([^\]]+)\] (.+?)(\s\{.*\})?$/', $log_string, $matches)) {
            $level = $matches[1];
            $time = strtotime($matches[2]);
            $message = $matches[3];
            $data = [];

            // Parse data JSON jika ada
            if (isset($matches[4]) && !empty($matches[4])) {
                $json_data = trim($matches[4]);
                $data = json_decode($json_data, true);
            }

            return new self($level, $message, $data, $time);
        }

        return null;
    }
}
