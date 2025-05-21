# WhatsApp Notify for Fluent Forms

Kirim notifikasi WhatsApp otomatis ketika formulir Fluent Forms disubmit. Integrasi sederhana dengan API WhatsApp untuk notifikasi real-time.

## Deskripsi

WhatsApp Notify menyediakan integrasi antara Fluent Forms dengan layanan API WhatsApp. Plugin ini memungkinkan pengirimian notifikasi WhatsApp secara otomatis ketika ada pengisian formulir baru, dengan opsi konfigurasi yang fleksibel per formulir.

### Fitur

- Kirim notifikasi WhatsApp otomatis saat formulir diisi
- Konfigurasi terpisah untuk setiap formulir
- Template pesan yang dapat disesuaikan
- Pilih field formulir yang akan disertakan dalam notifikasi
- Mode penerima yang fleksibel (nomor default, nomor kustom, atau ambil dari field formulir)
- Log aktivitas untuk memudahkan troubleshooting

## Persyaratan Sistem

- WordPress 5.6 atau lebih tinggi
- PHP 7.4 atau lebih tinggi
- Plugin Fluent Forms terinstal dan aktif
- Akses ke layanan API WhatsApp (dibutuhkan URL API dan token autentikasi)

## Instalasi

1. Unggah folder `whatsapp-notify` ke direktori `/wp-content/plugins/`
2. Aktifkan plugin melalui menu 'Plugins' di WordPress
3. Konfigurasi pengaturan plugin di menu 'WhatsApp Notify'
4. Konfigurasikan pengaturan notifikasi untuk setiap formulir

## Konfigurasi

### Pengaturan Umum

1. Buka menu 'WhatsApp Notify' di dashboard WordPress
2. Masukkan URL API WhatsApp
3. Masukkan token autentikasi
4. Atur nomor WhatsApp default untuk penerima notifikasi
5. Sesuaikan template pesan default (opsional)

### Pengaturan Formulir

1. Pilih formulir dari daftar
2. Aktifkan notifikasi untuk formulir tersebut
3. Pilih mode penerima (default, kustom, atau dinamis)
4. Sesuaikan template pesan (opsional)
5. Pilih field yang akan disertakan dalam notifikasi
6. Simpan pengaturan

## Lisensi

Plugin ini dilisensikan di bawah GPL v2 atau yang lebih baru.

## Changelog

### 1.0.0

- Rilis pertama plugin
- Integrasi dengan Fluent Forms
- Konfigurasi per formulir
- Template pesan yang dapat disesuaikan
- Mode penerima yang fleksibel
- Log aktivitas

## Kontak dan Dukungan

Untuk pertanyaan, dukungan, dan umpan balik:

- Email: <exernia@gmail.com>
- GitHub: <https://github.com/gwenziro/whatsapp-notify>
