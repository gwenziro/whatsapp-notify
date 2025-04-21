<?php

/**
 * HelpPage Controller
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Admin;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class HelpPage
 * 
 * Menangani tampilan halaman bantuan
 */
class HelpPage
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Constructor kosong
    }

    /**
     * Render tab bantuan
     */
    public function render()
    {
        // Include template
        include WANOTIFY_PLUGIN_DIR . 'templates/admin/help.php';
    }
}
