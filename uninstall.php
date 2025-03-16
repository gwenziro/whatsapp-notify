<?php
// Jika uninstall tidak dipanggil dari WordPress, keluar
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Hapus semua opsi plugin
delete_option('fluentwa_settings');

// Hapus pengaturan form
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'fluentwa_form_settings_%'");

// Hapus log jika disimpan di database
delete_option('fluentwa_logs');

// Hapus kemungkinan transient yang dibuat plugin
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_fluentwa_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_fluentwa_%'");