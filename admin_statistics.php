<?php
require 'config.php';
require_admin();

// Handle AJAX requests for monthly data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'monthly') {
    header('Content-Type: application/json');
    
    $filter_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $filter_category = isset($_GET['category']) ? $_GET['category'] : 'all';
    
    // Build query for monthly reports with filters
    $sql = "
        SELECT DATE_FORMAT(created_at, '%m') as month_num, COUNT(*) as count 
        FROM reports 
        WHERE YEAR(created_at) = ?
    ";
    $params = [$filter_year];
    
    if ($filter_status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $filter_status;
    }
    
    if ($filter_category !== 'all') {
        $sql .= " AND category = ?";
        $params[] = $filter_category;
    }
    
    $sql .= " GROUP BY DATE_FORMAT(created_at, '%m') ORDER BY month_num";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $db_results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Define all 12 months in Indonesian
        $month_names = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
        
        // Prepare full month data
        $final_labels = [];
        $final_data = [];
        
        foreach ($month_names as $num => $name) {
            $final_labels[] = $name;
            $final_data[] = isset($db_results[$num]) ? (int)$db_results[$num] : 0;
        }
        
        echo json_encode([
            'success' => true,
            'labels' => $final_labels,
            'data' => $final_data,
            'filters' => [
                'year' => $filter_year,
                'status' => $filter_status,
                'category' => $filter_category
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Get filter inputs for initial page load
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Get available years for filter
try {
    $stmt = $pdo->query("SELECT DISTINCT YEAR(created_at) as year FROM reports ORDER BY year DESC");
    $available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($available_years)) {
        $available_years = [date('Y')];
    }
} catch(PDOException $e) {
    $available_years = [date('Y')];
}

// Get available categories for filter
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM reports ORDER BY category");
    $available_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $available_categories = [];
}

// Get statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
    $total_reports = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reports WHERE status = ?");
    $stmt->execute(['Baru']);
    $reports_baru = $stmt->fetch()['total'];
    $stmt->execute(['Diproses']);
    $reports_diproses = $stmt->fetch()['total'];
    $stmt->execute(['Selesai']);
    $reports_selesai = $stmt->fetch()['total'];
    
    // Get reports per category
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM reports GROUP BY category");
    $reports_by_category = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik - EcoCare+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .sidebar { transition: all 0.3s ease; }
        .sidebar-link { 
            transition: all 0.2s ease;
        }
        .sidebar-link:hover {
            background: #f0fdf4;
        }
        .sidebar-link.active {
            background: linear-gradient(135deg, #6FAF8F 0%, #3D8B6A 100%);
            color: white;
        }
        .sidebar-link:hover .sidebar-icon {
            transform: scale(1.1);
        }
        .sidebar-icon {
            transition: transform 0.2s ease;
        }
        .stat-card { 
            transition: all 0.3s ease; 
            cursor: pointer;
        }
        .stat-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }
        .stat-card:active {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white shadow-lg border-r border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-ecocare-dark">EcoCare+</h2>
                        <p class="text-xs text-gray-500 font-semibold">Admin Panel</p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="admin_dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-tachometer-alt w-5 text-green-600"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_reports.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-file-alt w-5 text-blue-600"></i>
                            <span>Kelola Laporan</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-users w-5 text-purple-600"></i>
                            <span>Kelola Pengguna</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_map.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-map-marked-alt w-5 text-red-600"></i>
                            <span>Peta Monitoring</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_statistics.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-chart-bar w-5 text-orange-500"></i>
                            <span>Statistik</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_education.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-book w-5 text-teal-600"></i>
                            <span>Kelola Edukasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_actions.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700">
                            <i class="sidebar-icon fas fa-hands-helping w-5 text-amber-700"></i>
                            <span>Kelola Aksi Lingkungan</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="absolute bottom-0 left-0 w-64 p-4 border-t border-gray-200 bg-white">
                <a href="admin_profile.php" class="flex items-center gap-3 mb-4 hover:bg-gray-50 rounded-lg p-2 -mx-2 -my-2 transition">
                    <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-ecocare-primary flex-shrink-0">
                        <?php if (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic']): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" class="w-full h-full object-cover" alt="Profil">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </a>
                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1">
            <header class="bg-white shadow-sm border-b border-gray-200 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-ecocare-dark">Statistik</h1>
                        <p class="text-gray-500 text-sm">Data dan statistik lengkap</p>
                    </div>
                    <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-ecocare-primary transition flex items-center gap-2">
                        <i class="fas fa-home"></i>
                        <span>Ke Beranda</span>
                    </a>
                </div>
            </header>
            
            <div class="p-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-filter="users">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Pengguna</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $total_users; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-filter="all">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Laporan</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $total_reports; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-filter="Baru">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Laporan Baru</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $reports_baru; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-filter="Diproses">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-ecocare-orange to-orange-500 rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-spinner"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Diproses</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $reports_diproses; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-filter="Selesai">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-ecocare-primary to-ecocare-green-dark rounded-xl flex items-center justify-center text-white text-2xl shadow-md">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Selesai</p>
                                <p class="text-3xl font-bold text-ecocare-dark"><?php echo $reports_selesai; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="grid lg:grid-cols-2 gap-8 mb-8">
                    <!-- Reports Status Chart -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-ecocare-dark mb-6">Status Laporan</h3>
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                    
                    <!-- Reports Category Chart -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-ecocare-dark mb-6">Laporan per Kategori</h3>
                        <canvas id="categoryChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Monthly Reports Chart -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
                        <h3 class="text-lg font-bold text-ecocare-dark">Laporan per Bulan</h3>
                        
                        <!-- Filters -->
                        <form id="filterForm" class="flex items-center gap-3 flex-wrap">
                            <select name="year" id="yearFilter" class="px-4 py-2 bg-gray-50 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                                <?php foreach($available_years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $filter_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="status" id="statusFilter" class="px-4 py-2 bg-gray-50 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                <option value="Baru" <?php echo $filter_status === 'Baru' ? 'selected' : ''; ?>>Baru</option>
                                <option value="Diproses" <?php echo $filter_status === 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="Selesai" <?php echo $filter_status === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                            
                            <?php if (!empty($available_categories)): ?>
                                <select name="category" id="categoryFilter" class="px-4 py-2 bg-gray-50 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-ecocare-primary">
                                    <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>Semua Kategori</option>
                                    <?php foreach($available_categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            
                            <button type="submit" id="applyFilterBtn" class="px-4 py-2 bg-gradient-to-r from-ecocare-primary to-ecocare-green-dark text-white rounded-xl font-semibold hover:shadow-lg transition">
                                <i class="fas fa-filter mr-2"></i> Terapkan
                            </button>
                        </form>
                    </div>
                    
                    <div id="monthlyChartContainer">
                        <div id="monthlyNoData" class="text-center py-12 text-gray-500 hidden">
                            <i class="fas fa-chart-line text-5xl mb-4 opacity-30"></i>
                            <p class="text-lg">Belum ada data laporan pada periode yang dipilih</p>
                        </div>
                        <canvas id="monthlyChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let monthlyChart;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Stat cards click
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('click', function() {
                    const filterType = this.dataset.filter;
                    
                    if (filterType === 'users') {
                        // Navigate to admin_users.php
                        window.location.href = 'admin_users.php';
                    } else if (filterType === 'all') {
                        // Reset filters and update chart
                        document.getElementById('statusFilter').value = 'all';
                        const categoryFilter = document.getElementById('categoryFilter');
                        if (categoryFilter) categoryFilter.value = 'all';
                        updateMonthlyChart();
                    } else if (['Baru', 'Diproses', 'Selesai'].includes(filterType)) {
                        // Set status filter and update chart
                        document.getElementById('statusFilter').value = filterType;
                        const categoryFilter = document.getElementById('categoryFilter');
                        if (categoryFilter) categoryFilter.value = 'all';
                        updateMonthlyChart();
                    }
                });
            });
            // Status Chart
            const statusCtx = document.getElementById('statusChart')?.getContext('2d');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Baru', 'Diproses', 'Selesai'],
                        datasets: [{
                            data: [<?php echo $reports_baru; ?>, <?php echo $reports_diproses; ?>, <?php echo $reports_selesai; ?>],
                            backgroundColor: ['#ef4444', '#FFB86C', '#6FAF8F'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Category Chart
            const categoryLabels = <?php echo json_encode(array_column($reports_by_category, 'category')); ?>;
            const categoryData = <?php echo json_encode(array_column($reports_by_category, 'count')); ?>;
            const categoryColors = ['#6FAF8F', '#7DB7E8', '#FFB86C', '#A8D5BA'];
            
            const categoryCtx = document.getElementById('categoryChart')?.getContext('2d');
            if (categoryCtx && categoryLabels.length > 0) {
                new Chart(categoryCtx, {
                    type: 'bar',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            label: 'Jumlah Laporan',
                            data: categoryData,
                            backgroundColor: categoryColors.slice(0, categoryLabels.length),
                            borderRadius: 8,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f0f0f0'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
            
            // Initialize monthly chart
            initializeMonthlyChart();
            
            // Handle filter form submission
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                updateMonthlyChart();
            });
        });
        
        async function initializeMonthlyChart() {
            await updateMonthlyChart(true);
        }
        
        async function updateMonthlyChart(initial = false) {
            const year = document.getElementById('yearFilter').value;
            const status = document.getElementById('statusFilter').value;
            const categorySelect = document.getElementById('categoryFilter');
            const category = categorySelect ? categorySelect.value : 'all';
            
            // Show loading state
            const btn = document.getElementById('applyFilterBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memuat...';
            btn.disabled = true;
            
            try {
                const response = await fetch(`admin_statistics.php?ajax=monthly&year=${encodeURIComponent(year)}&status=${encodeURIComponent(status)}&category=${encodeURIComponent(category)}`);
                const result = await response.json();
                
                if (result.success) {
                    const totalData = result.data.reduce((a, b) => a + b, 0);
                    const noDataDiv = document.getElementById('monthlyNoData');
                    const chartCanvas = document.getElementById('monthlyChart');
                    
                    if (totalData === 0) {
                        noDataDiv.classList.remove('hidden');
                        chartCanvas.classList.add('hidden');
                        if (monthlyChart) {
                            monthlyChart.destroy();
                            monthlyChart = null;
                        }
                    } else {
                        noDataDiv.classList.add('hidden');
                        chartCanvas.classList.remove('hidden');
                        renderMonthlyChart(result.labels, result.data, result.filters);
                    }
                }
            } catch (error) {
                console.error('Error loading monthly data:', error);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        function renderMonthlyChart(labels, data, filters) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            // Prepare tooltip label
            const getFilterLabel = () => {
                let parts = [];
                if (filters.status !== 'all') parts.push(`Status: ${filters.status}`);
                if (filters.category !== 'all') parts.push(`Kategori: ${filters.category}`);
                return parts.length > 0 ? parts.join(' • ') : null;
            };
            
            const chartData = {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Laporan',
                    data: data,
                    fill: false,
                    backgroundColor: '#6FAF8F',
                    borderColor: '#6FAF8F',
                    borderWidth: 4,
                    tension: 0.35,
                    pointRadius: 7,
                    pointBackgroundColor: '#3D8B6A',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 3,
                    pointHoverRadius: 9,
                    pointHoverBackgroundColor: '#6FAF8F',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 4
                }]
            };
            
            const chartOptions = {
                responsive: true,
                animation: {
                    duration: 750,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#2D3748',
                        titleFont: {
                            size: 16,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 14
                        },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(context) {
                                return `${context[0].label} ${filters.year}`;
                            },
                            label: function(context) {
                                return `Jumlah Laporan: ${context.formattedValue}`;
                            },
                            afterLabel: function(context) {
                                const label = getFilterLabel();
                                if (label) {
                                    return label;
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0',
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 1,
                            color: '#475569',
                            font: {
                                size: 13
                            }
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Laporan',
                            color: '#2D3748',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#475569',
                            font: {
                                size: 13
                            }
                        }
                    }
                }
            };
            
            if (monthlyChart) {
                monthlyChart.data = chartData;
                monthlyChart.options = chartOptions;
                monthlyChart.update('default');
            } else {
                monthlyChart = new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: chartOptions
                });
            }
        }
    </script>
</body>
</html>
