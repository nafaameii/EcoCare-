<?php
require 'config.php';
require_login(); // Pastikan user login

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        // Sanitize & Validate Inputs
        $category = sanitize_input($_POST['category'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $location_address = sanitize_input($_POST['location_address'] ?? '');
        
        // Fallback: jika koordinat tidak ada, jangan gunakan Purwokerto, biarkan null
        $latitude = !empty($_POST['latitude']) ? sanitize_input($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? sanitize_input($_POST['longitude']) : null;
        
        // Validasi
        if (empty($category)) {
            $errors[] = "Kategori masalah harus dipilih";
        }
        if (empty($description)) {
            $errors[] = "Deskripsi tidak boleh kosong";
        }
        if (empty($location_address)) {
            $errors[] = "Alamat lokasi tidak boleh kosong";
        }

        $photo_path = null;
        
        // Tangani upload foto jika ada
        if (empty($errors) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $file_type = $_FILES['photo']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                // Buat nama file unik
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('report_', true) . '.' . $extension;
                $upload_dir = 'uploads/';
                $upload_path = $upload_dir . $file_name;
                
                // Pastikan folder uploads ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    $photo_path = $upload_path;
                } else {
                    $errors[] = "Gagal mengunggah foto";
                }
            } else {
                $errors[] = "Format foto tidak valid (hanya JPG, JPEG, PNG)";
            }
        }

        if (empty($errors)) {
            try {
                // Simpan ke database
                $stmt = $pdo->prepare("
                    INSERT INTO reports (user_id, category, description, photo_path, location_address, latitude, longitude, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Baru')
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $category,
                    $description,
                    $photo_path,
                    $location_address,
                    $latitude,
                    $longitude
                ]);
                
                $success = "Laporan berhasil dikirim!";
            } catch(PDOException $e) {
                $errors[] = "Terjadi kesalahan saat menyimpan laporan: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporkan Masalah - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'ecocare-primary': '#6FAF8F',
                        'ecocare-secondary': '#A8D5BA',
                        'ecocare-accent': '#7DB7E8',
                        'ecocare-cream': '#F4EBD0',
                        'ecocare-beige': '#E8DCCF',
                        'ecocare-orange': '#FFB86C',
                        'ecocare-dark': '#2D3748',
                        'ecocare-green-dark': '#3D8B6A'
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-ecocare-cream text-ecocare-dark min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <span class="text-2xl font-bold text-ecocare-dark">EcoCare+</span>
                </a>
                
                <div class="flex items-center gap-6">
                    <a href="map.php" class="text-ecocare-dark hover:text-ecocare-primary font-medium flex items-center gap-2 transition">
                        <i class="fas fa-map-marked-alt"></i> Peta
                    </a>
                    <span class="text-ecocare-dark font-medium">Halo, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="text-red-500 hover:text-red-600 font-medium flex items-center gap-2 transition">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12 border border-gray-100">
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-2xl flex items-center justify-center text-white text-4xl mx-auto mb-5 shadow-lg">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="text-4xl font-bold text-ecocare-dark mb-3">Laporkan Masalah Lingkungan</h1>
                <p class="text-gray-600 text-lg">Isi formulir berikut untuk melaporkan masalah di lingkungan Anda</p>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-5 rounded-2xl mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-2xl">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-xl mb-1"><?php echo htmlspecialchars($success); ?></div>
                            <p class="text-green-700 mb-2">Terima kasih telah berkontribusi untuk lingkungan kita!</p>
                            <a href="map.php" class="inline-flex items-center gap-2 text-green-700 font-bold hover:underline text-sm">
                                Lihat laporan di Peta <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 text-red-800 px-6 py-5 rounded-2xl mb-8">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg mb-3">Gagal mengirim laporan</h4>
                            <ul class="space-y-2">
                                <?php foreach ($errors as $error): ?>
                                    <li class="flex items-start gap-2 text-sm">
                                        <i class="fas fa-circle text-red-500 text-xs mt-1.5"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-7">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- Hidden inputs for latitude and longitude -->
                <input type="hidden" name="latitude" id="latitude" value="">
                <input type="hidden" name="longitude" id="longitude" value="">
                
                <!-- Kategori -->
                <div class="space-y-3">
                    <label class="block text-ecocare-dark font-semibold text-lg" for="category">
                        <i class="fas fa-tags mr-2 text-ecocare-primary"></i> Kategori Masalah
                    </label>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-gray-400">
                            <i class="fas fa-list"></i>
                        </div>
                        <select name="category" id="category" required 
                                class="w-full pl-14 pr-6 py-5 bg-white border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-ecocare-primary/20 focus:border-ecocare-primary transition text-ecocare-dark text-lg font-medium">
                            <option value="" class="text-gray-500">Pilih kategori masalah</option>
                            <option value="Sampah" <?php echo (($_POST['category'] ?? '') == 'Sampah' ? 'selected' : ''); ?> class="text-ecocare-dark">🗑️ Sampah</option>
                            <option value="Saluran Air Tersumbat" <?php echo (($_POST['category'] ?? '') == 'Saluran Air Tersumbat' ? 'selected' : ''); ?> class="text-ecocare-dark">🌊 Saluran Air Tersumbat</option>
                            <option value="Genangan Air" <?php echo (($_POST['category'] ?? '') == 'Genangan Air' ? 'selected' : ''); ?> class="text-ecocare-dark">💧 Genangan Air</option>
                            <option value="Lingkungan Kurang Terawat" <?php echo (($_POST['category'] ?? '') == 'Lingkungan Kurang Terawat' ? 'selected' : ''); ?> class="text-ecocare-dark">🏚️ Lingkungan Kurang Terawat</option>
                        </select>
                    </div>
                </div>
                
                <!-- Deskripsi -->
                <div class="space-y-3">
                    <label class="block text-ecocare-dark font-semibold text-lg" for="description">
                        <i class="fas fa-edit mr-2 text-ecocare-primary"></i> Deskripsi Detail
                    </label>
                    <div class="relative">
                        <div class="absolute left-4 top-5 text-xl text-gray-400">
                            <i class="fas fa-align-left"></i>
                        </div>
                        <textarea name="description" id="description" required rows="5" 
                                  class="w-full pl-14 pr-6 py-5 bg-white border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-ecocare-primary/20 focus:border-ecocare-primary transition text-ecocare-dark text-lg"
                                  placeholder="Jelaskan masalah secara detail, misalnya kondisi lingkungan, estimasi luas area, dll."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Foto -->
                <div class="space-y-3">
                    <label class="block text-ecocare-dark font-semibold text-lg" for="photo">
                        <i class="fas fa-camera mr-2 text-ecocare-primary"></i> Foto Lokasi
                        <span class="text-gray-400 text-sm font-normal ml-2">(Opsional)</span>
                    </label>
                    <div class="relative">
                        <input type="file" name="photo" id="photo" accept="image/*" 
                               class="w-full bg-white border-2 border-gray-200 border-dashed rounded-2xl p-6 focus:outline-none focus:ring-4 focus:ring-ecocare-primary/20 focus:border-ecocare-primary transition cursor-pointer file:mr-6 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-white file:bg-gradient-to-r file:from-ecocare-primary file:to-ecocare-green-dark file:font-bold hover:file:shadow-lg transition">
                    </div>
                    <p class="text-gray-500 text-sm">Format yang diizinkan: JPG, JPEG, PNG. Maksimal 5 MB</p>
                </div>
                
                <!-- Lokasi -->
                <div class="space-y-4">
                    <label class="block text-ecocare-dark font-semibold text-lg">
                        <i class="fas fa-map-marker-alt mr-2 text-ecocare-primary"></i> Lokasi
                    </label>
                    
                    <!-- Lokasi info -->
                    <div class="bg-gradient-to-r from-ecocare-primary/10 to-ecocare-accent/10 border border-ecocare-primary/30 px-6 py-4 rounded-2xl">
                        <p class="text-gray-700 text-sm flex items-start gap-3">
                            <i class="fas fa-info-circle text-ecocare-primary text-xl flex-shrink-0 mt-0.5"></i>
                            Lokasi akan terdeteksi otomatis menggunakan GPS perangkat Anda. Anda juga bisa mengedit alamatnya.
                        </p>
                    </div>
                    
                    <!-- Alamat -->
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-xl text-gray-400">
                            <i class="fas fa-home"></i>
                        </div>
                        <input type="text" name="location_address" id="location_address" required 
                               class="w-full pl-14 pr-6 py-5 bg-white border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-ecocare-primary/20 focus:border-ecocare-primary transition text-ecocare-dark text-lg font-medium"
                               placeholder="Jalan, RT/RW, Kelurahan, Kecamatan"
                               value="<?php echo htmlspecialchars($_POST['location_address'] ?? ''); ?>">
                    </div>
                    
                    <!-- Lokasi button -->
                    <div class="grid grid-cols-1 gap-4">
                        <button type="button" id="getLocation" 
                                class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-bold py-5 px-6 rounded-2xl hover:shadow-lg hover:shadow-ecocare-primary/40 transition flex items-center justify-center gap-3 text-lg">
                            <i class="fas fa-location-crosshairs text-xl"></i>
                            Dapatkan Lokasi Saat Ini
                        </button>
                    </div>
                    
                    <!-- Status lokasi -->
                    <div id="locationStatus" class="text-sm hidden"></div>
                </div>
                
                <!-- Submit -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white font-bold py-6 px-6 rounded-2xl hover:shadow-xl hover:shadow-ecocare-primary/40 transition text-xl shadow-lg shadow-ecocare-primary/20">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Kirim Laporan
                </button>
            </form>
        </div>
    </main>

    <script>
        // Reverse geocoding: get address from coordinates using OpenStreetMap Nominatim
        async function reverseGeocode(lat, lon) {
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&zoom=18`,
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'User-Agent': 'EcoCare+ App'
                        }
                    }
                );
                const data = await response.json();
                
                if (data && data.display_name) {
                    return data.display_name;
                } else {
                    return null;
                }
            } catch (error) {
                console.error('Reverse geocoding error:', error);
                return null;
            }
        }

        // Get current location
        document.getElementById('getLocation').addEventListener('click', async function() {
            const statusEl = document.getElementById('locationStatus');
            const locationInput = document.getElementById('location_address');
            
            if (navigator.geolocation) {
                // Show loading status
                statusEl.classList.remove('hidden', 'text-gray-500', 'text-red-500', 'text-green-600');
                statusEl.classList.add('text-ecocare-primary');
                statusEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mencoba mendapatkan lokasi Anda...';
                
                navigator.geolocation.getCurrentPosition(
                    async function(position) {
                        const lat = position.coords.latitude.toFixed(6);
                        const lon = position.coords.longitude.toFixed(6);
                        
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lon;
                        
                        // Try to get address
                        const address = await reverseGeocode(lat, lon);
                        
                        if (address) {
                            locationInput.value = address;
                            statusEl.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Lokasi berhasil ditemukan!';
                            statusEl.classList.remove('text-gray-500', 'text-red-500', 'text-ecocare-primary');
                            statusEl.classList.add('text-green-600');
                        } else {
                            statusEl.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Lokasi koordinat berhasil diambil, silakan masukkan alamat secara manual.';
                            statusEl.classList.remove('text-gray-500', 'text-red-500', 'text-green-600');
                            statusEl.classList.add('text-ecocare-primary');
                        }
                    },
                    function(error) {
                        let errorMsg = '';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg = 'Lokasi tidak dapat diakses. Silakan aktifkan izin lokasi perangkat Anda.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg = 'Informasi lokasi tidak tersedia.';
                                break;
                            case error.TIMEOUT:
                                errorMsg = 'Permintaan lokasi timeout. Silakan coba lagi.';
                                break;
                            default:
                                errorMsg = 'Terjadi kesalahan saat mendapatkan lokasi.';
                                break;
                        }
                        
                        statusEl.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${errorMsg}`;
                        statusEl.classList.remove('text-gray-500', 'text-green-600', 'text-ecocare-primary');
                        statusEl.classList.add('text-red-500');
                        
                        // Clear default coordinates
                        document.getElementById('latitude').value = '';
                        document.getElementById('longitude').value = '';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            } else {
                alert('Browser Anda tidak mendukung geolocation');
            }
        });
    </script>
</body>
</html>