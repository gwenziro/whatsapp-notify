<?php
// Jika uninstall tidak dipanggil dari WordPress, keluar
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Hapus semua opsi plugin
delete_option('wanotify_settings');

// Hapus pengaturan form
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wanotify_form_settings_%'");

// Hapus log jika disimpan di database
delete_option('wanotify_logs');

// Hapus kemungkinan transient yang dibuat plugin
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_wanotify_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_wanotify_%'");
