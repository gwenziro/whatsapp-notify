<?php

/**
 * Form Settings Manager
 *
 * @package WhatsApp_Notify
 * @subpackage Form
 * @since 1.0.0
 */

namespace WANotify\Form;

use WANotify\Core\Constants;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FormSettingsManager
 * 
 * Mengelola pengaturan formulir di database
 */
class FormSettingsManager
{
    /**
     * Ambil semua formulir tersedia
     *
     * @return array Daftar formulir
     */
    public static function get_all_forms()
    {
        if (!function_exists('wpFluent')) {
            return [];
        }

        return wpFluent()->table('fluentform_forms')
            ->select(['id', 'title'])
            ->orderBy('id', 'DESC')
            ->get();
    }

    /**
     * Ambil pengaturan formulir berdasarkan ID
     *
     * @param int $form_id ID formulir
     * @return array Pengaturan formulir
     */
    public static function get_form_settings($form_id)
    {
        return get_option(Constants::FORM_SETTINGS_PREFIX . $form_id, [
            'enabled' => false,
            'recipient_mode' => Constants::RECIPIENT_MODE_DEFAULT,
            'recipient' => '',
            'recipient_field' => '',
            'message_template' => '',
            'included_fields' => ['*']
        ]);
    }

    /**
     * Simpan pengaturan formulir
     *
     * @param int $form_id ID formulir
     * @param array $settings Pengaturan formulir
     * @return bool True jika berhasil disimpan atau tidak ada perubahan
     */
    public static function save_form_settings($form_id, $settings)
    {
        // Validasi
        if (empty($form_id) || !is_array($settings)) {
            return false;
        }

        // Pastikan tipe data konsisten
        $sanitized_settings = [];

        // Pastikan enabled selalu boolean
        $sanitized_settings['enabled'] = isset($settings['enabled']) ? 
            (bool)$settings['enabled'] : false;

        // Simpan seluruh settings dengan tipe data yang konsisten
        $sanitized_settings['recipient_mode'] = isset($settings['recipient_mode']) ? 
            sanitize_text_field($settings['recipient_mode']) : 'default';

        $sanitized_settings['recipient'] = isset($settings['recipient']) ? 
            sanitize_text_field($settings['recipient']) : '';

        $sanitized_settings['recipient_field'] = isset($settings['recipient_field']) ? 
            sanitize_text_field($settings['recipient_field']) : '';

        $sanitized_settings['message_template'] = isset($settings['message_template']) ? 
            wp_kses_post($settings['message_template']) : '';

        $sanitized_settings['included_fields'] = isset($settings['included_fields']) && is_array($settings['included_fields']) ? 
            array_map('sanitize_text_field', $settings['included_fields']) : ['*'];

        // Get existing settings to compare
        $option_name = Constants::FORM_SETTINGS_PREFIX . $form_id;
        $existing_settings = get_option($option_name, []);

        // Jika pengaturan tidak berubah, tetap kembalikan true
        if ($existing_settings == $sanitized_settings) {
            return true; // Tidak ada perubahan, tapi tetap mengembalikan sukses
        }

        // Gunakan update_option dengan autoload=yes untuk performa lebih baik
        return update_option($option_name, $sanitized_settings, 'yes');
    }

    /**
     * Toggle status notifikasi formulir
     *
     * @param int $form_id ID formulir
     * @param bool $enabled Status enabled/disabled
     * @return bool True jika berhasil
     */
    public static function toggle_form_status($form_id, $enabled)
    {
        $settings = self::get_form_settings($form_id);
        $settings['enabled'] = (bool) $enabled;

        return self::save_form_settings($form_id, $settings);
    }

    /**
     * Cek apakah notifikasi diaktifkan untuk formulir tertentu
     *
     * @param int $form_id ID formulir
     * @return bool True jika diaktifkan
     */
    public static function is_enabled($form_id)
    {
        $settings = self::get_form_settings($form_id);
        return isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
    }

    /**
     * Hapus pengaturan untuk formulir tertentu
     *
     * @param int $form_id ID formulir
     * @return bool True jika berhasil dihapus
     */
    public static function delete_form_settings($form_id)
    {
        return delete_option(Constants::FORM_SETTINGS_PREFIX . $form_id);
    }

    /**
     * Migrasi pengaturan dari format lama
     *
     * @return int Jumlah pengaturan yang dimigrasi
     */
    public static function migrate_legacy_settings()
    {
        global $wpdb;
        $count = 0;

        // Cari pengaturan dengan format lama (fluentwa_form_settings_*)
        $old_form_settings = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'fluentwa_form_settings_%'"
        );

        if (!empty($old_form_settings)) {
            foreach ($old_form_settings as $option) {
                // Ekstrak form ID dari nama opsi
                $form_id = str_replace('fluentwa_form_settings_', '', $option->option_name);

                // Buat nama opsi baru
                $new_option_name = Constants::FORM_SETTINGS_PREFIX . $form_id;

                // Salin nilai jika opsi baru belum ada
                if (!get_option($new_option_name)) {
                    $option_value = maybe_unserialize($option->option_value);
                    update_option($new_option_name, $option_value);
                    $count++;
                }
            }
        }

        return $count;
    }
}
