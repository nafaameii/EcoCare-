# Panduan Menggunakan Gambar Lokal di Halaman Edukasi

Untuk mengganti placeholder dengan gambar nyata, ikuti langkah berikut:

---

## 1. **Masukkan gambar kamu ke folder ini (`assets/images/edukasi`)**
   - Beri nama file sesuai daftar di bawah ini agar mudah:

## 2. **Daftar nama file yang dibutuhkan**:
   - `sampah-banner.jpg` → Hero untuk halaman Memilah Sampah
   - `sampah-content.jpg` → Gambar di dalam artikel Memilah Sampah
   - `sungai-banner.jpg` → Hero untuk halaman Menjaga Sungai
   - `sungai-content.jpg` → Gambar di dalam artikel Menjaga Sungai
   - `plastik-banner.jpg` → Hero untuk halaman Kurangi Plastik
   - `plastik-content.jpg` → Gambar di dalam artikel Kurangi Plastik

---

## 3. **Cara mengganti Hero Banner (gambar latar atas)**
Buka file halaman edukasi (misal `edukasi_sampah.php`), lalu ganti URL Unsplash dengan path lokal:

Contoh untuk Hero Banner:
```html
<!-- Sebelum: -->
<section class="relative py-16" style="height: 400px; background-image: linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('https://images.unsplash.com/...'); ...">

<!-- Setelah: -->
<section class="relative py-16" style="height: 400px; background-image: linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('assets/images/edukasi/sampah-banner.jpg'); ...">
```

---

## 4. **Cara mengganti gambar di dalam artikel (placeholder)**
Buka file halaman edukasi (misal `edukasi_sampah.php`), lalu ganti div placeholder dengan tag `<img>`:

Contoh untuk `edukasi_sampah.php`:
```html
<!-- Sebelum (placeholder): -->
<div class="w-full h-64 bg-gradient-to-br from-green-400 to-ecocare-primary rounded-xl mb-8 flex items-center justify-center overflow-hidden">
    <div class="text-white text-center">
        <i class="fas fa-trash-alt text-9xl mb-4"></i>
        <p class="text-xl font-semibold">Ilustrasi Pemilahan Sampah</p>
    </div>
</div>

<!-- Setelah (dengan gambar nyata): -->
<img src="assets/images/edukasi/sampah-content.jpg" alt="Ilustrasi Pemilahan Sampah" class="w-full h-64 object-cover rounded-xl mb-8" loading="lazy">
```


