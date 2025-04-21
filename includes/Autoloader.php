<?php

/**
 * Autoloader untuk WhatsApp Notify
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 * 
 * Menangani autoloading kelas-kelas plugin
 */
class Autoloader
{
    /**
     * Daftarkan autoloader
     */
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload callback.
     *
     * @param string $class Nama kelas yang akan di-autoload.
     */
    public static function autoload($class)
    {
        // Prefix namespace untuk plugin ini
        $prefix = 'WANotify\\';

        // Cek apakah kelas menggunakan namespace plugin ini
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return; // Bukan kelas dari plugin ini
        }

        // Hilangkan namespace prefix untuk mendapatkan nama kelas relatif
        $relative_class = substr($class, $len);

        // Ubah namespace separator (\) menjadi directory separator (/)
        // dan tambahkan .php di akhir
        $file = WANOTIFY_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';

        // Jika file ada, require
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Daftarkan autoloader
Autoloader::register();
