<?php

/**
 * Form Handler for Fluent Forms
 *
 * @package WhatsApp_Notify
 * @subpackage Form
 * @since 1.0.0
 */

namespace WANotify\Form;

use WANotify\Core\Constants;
use WANotify\Logging\Logger;
use WANotify\Notification\NotificationManager;
use WANotify\Utils\Formatter;
use WANotify\Utils\Security;
use WANotify\Form\FormSettingsManager;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FormHandler
 * 
 * Menangani interaksi dengan submissions Fluent Forms
 */
class FormHandler
{
    /**
     * Notification Manager
     *
     * @var NotificationManager
     */
    private $notification_manager;

    /**
     * Logger
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param NotificationManager $notification_manager Notification Manager
     * @param Logger $logger Logger
     */
    public function __construct(NotificationManager $notification_manager, Logger $logger)
    {
        $this->notification_manager = $notification_manager;
        $this->logger = $logger;

        // Hook ke event submission form
        add_action('fluentform/submission_inserted', [$this, 'process_form_submission'], 10, 3);
    }

    /**
     * Proses form submission dan kirim notifikasi WhatsApp
     *
     * @param int $entry_id ID dari entry
     * @param array $form_data Data form
     * @param object $form Form object
     * @return void
     */
    public function process_form_submission($entry_id, $form_data, $form)
    {
        try {
            $form_id = $form->id;
            $form_settings = $this->get_form_settings($form_id);
    
            // Cek apakah notifikasi diaktifkan untuk form ini
            if (empty($form_settings) || !$form_settings['enabled']) {
                $this->logger->info("Notifikasi tidak diaktifkan untuk formulir #{$form_id}, submission ID #{$entry_id}");
                return;
            }
    
            // PERUBAHAN: Tambahkan log untuk memulai proses pengiriman
            $this->logger->info("Memproses submission formulir untuk notifikasi WhatsApp", [
                'form_id' => $form_id,
                'entry_id' => $entry_id,
                'form_title' => $form->title
            ]);
    
            // Tentukan penerima menggunakan fungsi helper
            $recipient = $this->get_recipient($form_settings, $form_data);
    
            if (empty($recipient)) {
                $this->logger->error("Nomor tujuan tidak ditemukan untuk formulir #{$form_id}", [
                    'entry_id' => $entry_id,
                    'recipient_mode' => $form_settings['recipient_mode']
                ]);
                return;
            }
    
            // PERUBAHAN: Log nomor tujuan yang ditemukan
            $this->logger->info("Nomor tujuan ditemukan: {$recipient}", [
                'form_id' => $form_id,
                'recipient_mode' => $form_settings['recipient_mode']
            ]);
    
            // Bangun pesan dari template
            $message = $this->build_message($form_settings, $form_data, $form);
    
            // Kirim notifikasi melalui NotificationManager
            $result = $this->notification_manager->send('whatsapp', $recipient, $message, [
                'form_id' => $form_id,
                'entry_id' => $entry_id,
                'form_data' => $form_data
            ]);
    
            // PERUBAHAN: Log hasil pengiriman dengan lebih detail
            if ($result['success']) {
                $this->logger->info("Notifikasi WhatsApp berhasil dikirim untuk formulir #{$form_id}", [
                    'entry_id' => $entry_id,
                    'recipient' => $recipient,
                    'form_title' => $form->title
                ]);
            } else {
                $this->logger->error("Gagal mengirim notifikasi WhatsApp untuk formulir #{$form_id}", [
                    'entry_id' => $entry_id,
                    'recipient' => $recipient,
                    'error' => $result['message']
                ]);
            }
    
            // Simpan informasi pengiriman ke meta entry
            $this->save_notification_log($entry_id, $result);
        } catch (\Exception $e) {
            // Tangkap semua error untuk mencegah interupsi pengiriman form
            $this->logger->error('Error saat mengirim notifikasi WhatsApp', [
                'error' => $e->getMessage(),
                'form_id' => $form_id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Ambil pengaturan form dari database
     *
     * @param int $form_id ID formulir
     * @return array|null Pengaturan form atau null jika tidak ada
     */
    private function get_form_settings($form_id)
    {
        return FormSettingsManager::get_form_settings($form_id);
    }

    /**
     * Tentukan nomor penerima berdasarkan pengaturan
     *
     * @param array $form_settings Pengaturan formulir
     * @param array $entry_data Data entri formulir
     * @return string Nomor penerima yang akan digunakan
     */
    private function get_recipient($form_settings, $entry_data)
    {
        $global_settings = get_option(Constants::SETTINGS_OPTION_KEY, []);
        $default_recipient = $global_settings['default_recipient'] ?? '';

        $mode = $form_settings['recipient_mode'] ?? Constants::RECIPIENT_MODE_DEFAULT;

        switch ($mode) {
            case Constants::RECIPIENT_MODE_MANUAL:
                // Gunakan nomor manual dari pengaturan formulir
                return $form_settings['recipient'] ?? $default_recipient;

            case Constants::RECIPIENT_MODE_DYNAMIC:
                // Ambil dari field formulir
                $field_name = $form_settings['recipient_field'] ?? '';

                if (!empty($field_name) && isset($entry_data[$field_name])) {
                    return Formatter::phone_number($entry_data[$field_name]);
                }

                // Fallback ke default jika field kosong
                return $default_recipient;

            case Constants::RECIPIENT_MODE_DEFAULT:
            default:
                // Gunakan nomor default
                return $default_recipient;
        }
    }

    /**
     * Bangun pesan notifikasi dari template
     *
     * @param array $form_settings Pengaturan form
     * @param array $form_data Data formulir
     * @param object $form Form object
     * @return string Pesan lengkap
     */
    private function build_message($form_settings, $form_data, $form)
    {
        // Ambil template pesan - pastikan karakter khusus dipertahankan
        $template = !empty($form_settings['message_template']) ?
            stripslashes($form_settings['message_template']) :
            $this->get_default_template();

        // Ganti placeholder default
        $replacements = [
            '{form_name}' => $form->title,
            '{form_id}' => $form->id,
            '{submission_date}' => Formatter::datetime(time()),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_bloginfo('url')
        ];

        // Siapkan data formulir
        if (strpos($template, '{form_data}') !== false) {
            // Tentukan field mana yang disertakan
            $included_fields = !empty($form_settings['included_fields']) ?
                $form_settings['included_fields'] :
                array_keys($form_data);

            // Format data formulir
            $form_data_text = '';
            foreach ($form_data as $field => $value) {
                // Lewati jika field tidak termasuk dalam daftar dan bukan "semua field" (*) 
                if (!in_array($field, $included_fields) && !in_array('*', $included_fields)) {
                    continue;
                }

                // Format nilai jika array
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $form_data_text .= "*{$field}*: {$value}\n";
            }

            $replacements['{form_data}'] = $form_data_text;
        }

        // Ganti placeholder khusus field
        preg_match_all('/\{([^}]+)\}/', $template, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $placeholder) {
                // Skip if already replaced
                if (isset($replacements["{{$placeholder}}"])) {
                    continue;
                }

                // Check if field exists in form data
                if (isset($form_data[$placeholder])) {
                    $field_value = $form_data[$placeholder];

                    if (is_array($field_value)) {
                        $field_value = implode(', ', $field_value);
                    }

                    $replacements["{{$placeholder}}"] = $field_value;
                }
            }
        }

        // Lakukan penggantian
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Dapatkan template default
     *
     * @return string Template default
     */
    private function get_default_template()
    {
        $global_settings = get_option(Constants::SETTINGS_OPTION_KEY, []);

        return !empty($global_settings['default_template']) ?
            stripslashes($global_settings['default_template']) :
            Constants::DEFAULT_TEMPLATE;
    }

    /**
     * Simpan log pengiriman notifikasi
     *
     * @param int $entry_id Entry ID
     * @param array $result Hasil dari pengiriman notifikasi
     * @return void
     */
    private function save_notification_log($entry_id, $result)
    {
        // Ambil meta yang sudah ada
        $notification_log = get_post_meta($entry_id, '_wanotify_notification_log', true);

        if (empty($notification_log)) {
            $notification_log = [];
        }

        // Tambahkan log baru
        $notification_log[] = [
            'timestamp' => current_time('timestamp'),
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => isset($result['data']) ? $result['data'] : null
        ];

        // Simpan kembali ke database
        update_post_meta($entry_id, '_wanotify_notification_log', $notification_log);
    }
}
