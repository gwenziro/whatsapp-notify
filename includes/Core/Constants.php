<?php

/**
 * Definisi konstanta untuk plugin
 *
 * @package WhatsApp_Notify
 * @subpackage Core
 * @since 1.0.0
 */

namespace WANotify\Core;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Constants
 * 
 * Definisi konstanta yang digunakan dalam plugin
 */
class Constants
{
    // Versi plugin
    const VERSION = '1.0.0';

    // Nama opsi di database
    const SETTINGS_OPTION_KEY = 'wanotify_settings';
    const FORM_SETTINGS_PREFIX = 'wanotify_form_settings_';
    const LOG_OPTION_KEY = 'wanotify_logs';
    
    // URL API WhatsApp - nilai tetap
    const BASE_API_URL = 'https://bot.ihyaulquran.id';

    // Endpoint API WhatsApp
    const ENDPOINT_PERSONAL = 'api/send/personal';
    const ENDPOINT_GROUP = 'api/send/group';
    const ENDPOINT_GROUP_LIST = 'api/groups';

    // Mode penerima
    const RECIPIENT_MODE_DEFAULT = 'default';
    const RECIPIENT_MODE_MANUAL = 'manual';
    const RECIPIENT_MODE_DYNAMIC = 'dynamic';

    // Template pesan default
    const DEFAULT_TEMPLATE = "🔔 *Ada pengisian formulir baru!*\n\nFormulir: {form_name}\nWaktu: {submission_date}\n\n{form_data}";
    
    // Level log
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_DEBUG = 'DEBUG';
    
    // Kapasitas log maksimum
    const MAX_LOG_ENTRIES = 500;
    
    // Opsi timeout default (dalam detik)
    const DEFAULT_API_TIMEOUT = 30;
    
    // Opsi cache (dalam detik)
    const CACHE_EXPIRATION = 3600; // 1 jam
}
