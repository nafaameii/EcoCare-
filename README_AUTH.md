# EcoCare+ - Authentication System (Production Ready)

## File yang Diperbaiki
1. `config.php` - Ditambahkan keamanan session, CSRF protection, sanitasi
2. `register.php` - Hapus fitur auto fill, tambah konfirmasi password, validasi, sanitasi, CSRF
3. `login.php` - Hapus demo account, tambah CSRF, keamanan session
4. `submit_report.php` - Tambah CSRF protection, sanitasi input
5. `ecocare_db.sql` - Diupdate admin account, hapus dummy data

## Fitur Keamanan
- Session Cookie Secure (HttpOnly, SameSite Strict)
- CSRF Token Protection
- Sanitasi Semua Input
- Validasi Form Lengkap
- Password Hashing (PASSWORD_DEFAULT)
- Session Regenerasi saat Login/Register

## Cara Penggunaan
1. Import `ecocare_db.sql` ke MySQL
2. Pastikan konfigurasi database di `config.php` sesuai
3. Buka `index.php`
4. Register sebagai masyarakat atau Login sebagai admin

## Akun Default Admin
- Email: `admin@ecocare.id`
- Password: `password` (Catatan: Untuk keamanan, Ganti password admin segera di file `hash_admin_password.php` lalu import kembali ke database!)

## Catatan Penting
- Untuk production, ubah `session.cookie_secure` di `config.php` menjadi `1` (butuh HTTPS)
- Folder `uploads` harus memiliki izin write (chmod 775)
