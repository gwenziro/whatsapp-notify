<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Definisi konstanta untuk plugin
 */
class FluentWA_Constants {
    // Versi plugin
    const VERSION = '1.0.0';
    
    // Nama opsi di database
    const SETTINGS_OPTION_KEY = 'fluentwa_settings';
    const FORM_SETTINGS_PREFIX = 'fluentwa_form_settings_';
    
    // Endpoint API WhatsApp
    const ENDPOINT_PERSONAL = 'kirim-pesan-personal';
    const ENDPOINT_GROUP = 'kirim-pesan-grup';
    const ENDPOINT_GROUP_LIST = 'daftar-grup';
    
    // Mode penerima
    const RECIPIENT_MODE_DEFAULT = 'default';
    const RECIPIENT_MODE_MANUAL = 'manual';
    const RECIPIENT_MODE_DYNAMIC = 'dynamic';
    
    // Template pesan default
    const DEFAULT_TEMPLATE = "🔔 *Ada pengisian formulir baru!*\n\nFormulir: {form_name}\nWaktu: {submission_date}\n\n{form_data}";
}
