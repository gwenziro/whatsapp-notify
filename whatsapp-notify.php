<?php

/**
 * Plugin Name: WhatsApp Notify
 * Plugin URI: https://github.com/gwenziro/whatsapp-notify
 * Description: Kirim notifikasi WhatsApp otomatis ketika formulir Fluent Forms disubmit.
 * Version: 1.0.1
 * Author: Exernia
 * Author URI: https://jajanweb.com
 * Text Domain: whatsapp-notify
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Tested up to: 6.4
 * 
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.
 * 
 * WhatsApp Notify memerlukan plugin Fluent Forms untuk bekerja dengan baik.
 * Fluent Forms: https://wordpress.org/plugins/fluentform/
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WANOTIFY_VERSION', '1.0.1');
define('WANOTIFY_PLUGIN_FILE', __FILE__);
define('WANOTIFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WANOTIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WANOTIFY_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload classes
require_once WANOTIFY_PLUGIN_DIR . 'includes/Autoloader.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['WANotify\Core\Bootstrap', 'activate']);
register_deactivation_hook(__FILE__, ['WANotify\Core\Bootstrap', 'deactivate']);

// Load plugin
add_action('plugins_loaded', ['WANotify\Core\Bootstrap', 'init']);
