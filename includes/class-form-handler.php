<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

class FluentWA_Form_Handler {
    private $api;
    private $logger;
    
    public function __construct($api, $logger) {
        $this->api = $api;
        $this->logger = $logger;
        
        // Hook ke event submission form
        add_action('fluentform/submission_inserted', array($this, 'process_form_submission'), 10, 3);
    }
    
    /**
     * Proses form submission dan kirim notifikasi WhatsApp
     */
    public function process_form_submission($entry_id, $form_data, $form) {
        $form_id = $form->id;
        $form_settings = $this->get_form_settings($form_id);
        
        // Cek apakah notifikasi diaktifkan untuk form ini
        if (empty($form_settings) || !$form_settings['enabled']) {
            $this->logger->info("Notifikasi tidak diaktifkan untuk form #{$form_id}");
            return;
        }
        
        // Tentukan penerima menggunakan fungsi helper
        $recipient = $this->get_recipient($form_settings, $form_data);
        
        if (empty($recipient)) {
            $this->logger->error("Nomor tujuan tidak ditemukan untuk form #{$form_id}");
            return;
        }
        
        // Bangun pesan dari template
        $message = $this->build_message($form_settings, $form_data, $form);
        
        // Kirim notifikasi
        $result = $this->api->send_notification($recipient, $message, $form_id);
        
        // Simpan informasi pengiriman ke meta entry
        $this->save_notification_log($entry_id, $result);
    }
    
    /**
     * Ambil pengaturan form dari database
     */
    private function get_form_settings($form_id) {
        $form_settings = get_option("fluentwa_form_settings_{$form_id}", array());
        
        if (empty($form_settings)) {
            return null;
        }
        
        return $form_settings;
    }
    
    /**
     * Tentukan nomor penerima berdasarkan pengaturan
     *
     * @param array $form_settings Pengaturan formulir
     * @param array $entry_data Data entri formulir
     * @return string Nomor penerima yang akan digunakan
     */
    private function get_recipient($form_settings, $entry_data) {
        $global_settings = get_option('fluentwa_settings', array());
        $default_recipient = $global_settings['default_recipient'] ?? '';
        
        $mode = $form_settings['recipient_mode'] ?? 'default';
        
        switch ($mode) {
            case 'manual':
                // Gunakan nomor manual dari pengaturan formulir
                return $form_settings['recipient'] ?? $default_recipient;
                
            case 'dynamic':
                // Ambil dari field formulir
                $field_name = $form_settings['recipient_field'] ?? '';
                
                if (!empty($field_name) && isset($entry_data[$field_name])) {
                    return $entry_data[$field_name];
                }
                
                // Fallback ke default jika field kosong
                return $default_recipient;
                
            case 'default':
            default:
                // Gunakan nomor default
                return $default_recipient;
        }
    }
    
    /**
     * Bangun pesan notifikasi dari template
     */
    private function build_message($form_settings, $form_data, $form) {
        // Ambil template pesan
        $template = !empty($form_settings['message_template']) ? 
                    $form_settings['message_template'] : 
                    $this->get_default_template();
        
        // Ganti placeholder default
        $replacements = array(
            '{form_name}' => $form->title,
            '{form_id}' => $form->id,
            '{submission_date}' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_bloginfo('url')
        );
        
        // Siapkan data formulir
        if (strpos($template, '{form_data}') !== false) {
            // Tentukan field mana yang disertakan
            $included_fields = !empty($form_settings['included_fields']) ? 
                              $form_settings['included_fields'] : 
                              array_keys($form_data);
            
            // Format data formulir
            $form_data_text = '';
            foreach ($form_data as $field => $value) {
                // Lewati jika field tidak termasuk dalam daftar
                if (!in_array($field, $included_fields) && $included_fields != ['*']) {
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
     */
    private function get_default_template() {
        $global_settings = get_option('fluentwa_settings', array());
        
        return !empty($global_settings['default_template']) ? 
               $global_settings['default_template'] : 
               "🔔 *Ada pengisian formulir baru!*\n\nFormulir: {form_name}\nWaktu: {submission_date}\n\n{form_data}";
    }
    
    /**
     * Simpan log pengiriman notifikasi
     */
    private function save_notification_log($entry_id, $result) {
        // Ambil meta yang sudah ada
        $notification_log = get_post_meta($entry_id, '_fluentwa_notification_log', true);
        
        if (empty($notification_log)) {
            $notification_log = array();
        }
        
        // Tambahkan log baru
        $notification_log[] = array(
            'timestamp' => current_time('timestamp'),
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => isset($result['data']) ? $result['data'] : null
        );
        
        // Simpan kembali ke database
        update_post_meta($entry_id, '_fluentwa_notification_log', $notification_log);
    }
}