<?php
// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wanotify-settings-section">
    <h2><?php _e('Bantuan & Dokumentasi', 'whatsapp-notify'); ?></h2>

    <div class="wanotify-help-card">
        <h3><?php _e('Memulai', 'whatsapp-notify'); ?></h3>
        <p><?php _e('Untuk memulai menggunakan plugin ini, Anda perlu:', 'whatsapp-notify'); ?></p>
        <ol>
            <li><?php _e('Menyiapkan Bot WhatsApp menggunakan whatsapp-web.js', 'whatsapp-notify'); ?></li>
            <li><?php _e('Mengonfigurasi URL API bot di halaman pengaturan umum', 'whatsapp-notify'); ?></li>
            <li><?php _e('Menambahkan token autentikasi yang sama dengan yang digunakan di server bot', 'whatsapp-notify'); ?></li>
            <li><?php _e('Mengaktifkan notifikasi untuk formulir yang diinginkan', 'whatsapp-notify'); ?></li>
        </ol>
    </div>

    <div class="wanotify-help-card">
        <h3><?php _e('FAQ', 'whatsapp-notify'); ?></h3>

        <div class="wanotify-faq-item">
            <h4><?php _e('Bagaimana cara menyiapkan bot WhatsApp?', 'whatsapp-notify'); ?></h4>
            <div class="wanotify-faq-answer">
                <p><?php _e('Anda perlu menyiapkan server Node.js dengan library whatsapp-web.js. Lihat dokumentasi di GitHub untuk detail lebih lanjut.', 'whatsapp-notify'); ?></p>
            </div>
        </div>

        <div class="wanotify-faq-item">
            <h4><?php _e('Apakah plugin ini aman dan legal?', 'whatsapp-notify'); ?></h4>
            <div class="wanotify-faq-answer">
                <p><?php _e('Plugin ini menggunakan library tidak resmi untuk WhatsApp. Untuk penggunaan resmi, sebaiknya gunakan WhatsApp Business API.', 'whatsapp-notify'); ?></p>
            </div>
        </div>

        <div class="wanotify-faq-item">
            <h4><?php _e('Apa saja variabel yang bisa digunakan di template?', 'whatsapp-notify'); ?></h4>
            <div class="wanotify-faq-answer">
                <p><?php _e('Anda dapat menggunakan {form_name}, {form_id}, {submission_date}, {form_data}, dan nama field formulir seperti {nama}, {email}, dll.', 'whatsapp-notify'); ?></p>
            </div>
        </div>
    </div>
</div>
