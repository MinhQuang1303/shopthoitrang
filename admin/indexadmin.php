<?php
// PHP Logic (Giữ nguyên)
require_once __DIR__ . '/layouts/tieu_de.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// 📊 Thống kê tổng quan
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status = 'delivered'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// 📈 Doanh thu 7 ngày gần nhất
$stmt = $pdo->query("
    SELECT DATE(created_at) AS ngay, COALESCE(SUM(total_amount),0) AS doanhthu
    FROM orders
    WHERE status = 'delivered' AND created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
    ORDER BY ngay ASC
");
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$chart_labels = array_column($chart_data, 'ngay');
$chart_values = array_column($chart_data, 'doanhthu');

// Đơn hàng hôm nay
$today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$today_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'delivered'")->fetchColumn();

// === TOP 5 SẢN PHẨM BÁN CHẠY (Giữ logic tự động phát hiện cột) ===
$top_products = [];
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM order_details");
    $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $product_id_col = null;
    $variant_id_col = null;

    foreach ($columns as $col) {
        if (stripos($col, 'product') !== false || stripos($col, 'san_pham') !== false) {
            $product_id_col = $col;
        }
        if (stripos($col, 'bien_the') !== false || stripos($col, 'variant') !== false) {
            $variant_id_col = $col;
        }
    }

    if ($product_id_col) {
        $sql = "
            SELECT p.product_name, SUM(od.quantity) AS total_sold
            FROM order_details od
            JOIN products p ON od.$product_id_col = p.product_id
            JOIN orders o ON od.order_id = o.order_id
            WHERE o.status = 'delivered'
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT 5
        ";
        $top_products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($variant_id_col && in_array('product_variants', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
        $sql = "
            SELECT p.product_name, SUM(od.quantity) AS total_sold
            FROM order_details od
            JOIN product_variants pv ON od.$variant_id_col = pv.variant_id
            JOIN products p ON pv.product_id = p.product_id
            JOIN orders o ON od.order_id = o.order_id
            WHERE o.status = 'delivered'
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT 5
        ";
        $top_products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Lỗi lấy top sản phẩm: " . $e->getMessage());
}

// === HOẠT ĐỘNG GẦN ĐÂY ===
$activities = [];
try {
    $sql = "
        SELECT 'order' AS type, order_id AS id, CONCAT('Đơn hàng mới #', order_id) AS message, created_at
        FROM orders
        WHERE DATE(created_at) = CURDATE()
        UNION ALL
        SELECT 'user' AS type, user_id AS id, CONCAT('Người dùng mới: ', full_name) AS message, created_at
        FROM users
        WHERE DATE(created_at) = CURDATE()
        ORDER BY created_at DESC
        LIMIT 6
    ";
    $activities = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Lỗi lấy hoạt động: " . $e->getMessage());
}

// Tạo mảng dữ liệu biểu đồ an toàn
$chartDataJson = json_encode([
    'labels' => $chart_labels,
    'values' => $chart_values
], JSON_UNESCAPED_UNICODE);
?>

<style>
    /* Kế thừa từ tieu_de.php */

    /* === FIX: Đảm bảo nền tối hoạt động chính xác === */
    [data-theme="dark"] .stat-card,
    [data-theme="dark"] .chart-card {
        background: rgba(45, 52, 54, 0.8) !important;
        color: var(--dark) !important; /* Đảm bảo text sáng */
    }
    
    /* === 1. CARD SIÊU NỔI BẬT (STATS CARD) === */
    .stat-card {
        border-radius: 20px !important;
        position: relative;
        overflow: hidden;
        color: var(--dark);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid var(--border); /* Thêm lại border */
        background: white;
    }
    
    .stat-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important; 
        z-index: 10;
    }

    .stat-card h3 {
        font-weight: 900;
        font-size: 2.5rem;
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1); 
    }
    
    .stat-card h6 {
        font-weight: 600;
        opacity: 0.9;
    }

    .stat-card i {
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        font-size: 2.5rem;
        animation: pulseIcon 3s infinite;
    }
    @keyframes pulseIcon {
        0%, 100% { transform: scale(1); opacity: 0.9; }
        50% { transform: scale(1.1); opacity: 1; }
    }

    /* Card Biểu đồ/Danh sách */
    .card {
        border-radius: 20px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        transition: all 0.4s ease;
    }
    .card:hover {
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12) !important;
    }

    /* Hoạt động gần đây Timeline */
    .activity-list-item {
        border-bottom: 1px solid var(--border);
        padding: 12px 0;
        transition: all 0.3s;
        cursor: pointer;
    }
    .activity-list-item:hover {
        background: rgba(108, 92, 231, 0.05); 
        padding-left: 15px;
        border-radius: 5px;
    }
</style>

<div class="container-fluid py-4 px-4 px-lg-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">
            <i class="fas fa-tachometer-alt me-2"></i>
            Bảng điều khiển
        </h1>
        <a href="indexadmin.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Làm mới
        </a>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        
        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card-main" style="background: linear-gradient(135deg, var(--primary), #8b5cf6);">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-2" style="color: white;"></i>
                    <h6 class="text-white-50">Tổng người dùng</h6>
                    <h3 class="text-white"><?= number_format($total_users) ?></h3>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card-main" style="background: linear-gradient(135deg, var(--danger), #ff7675);">
                <div class="card-body text-center">
                    <i class="fas fa-lock fa-2x mb-2" style="color: white;"></i> <h6 class="text-white-50">Tổng đơn hàng</h6>
                    <h3 class="text-white"><?= number_format($total_orders) ?></h3>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card-main" style="background: linear-gradient(135deg, var(--success), #00b894);">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-2" style="color: white;"></i>
                    <h6 class="text-white-50">Tổng doanh thu</h6>
                    <h3 class="text-white"><?= number_format($total_revenue, 0, ',', '.') ?> ₫</h3>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card-main" style="background: linear-gradient(135deg, var(--warning), #fab1a0);">
                <div class="card-body text-center">
                    <i class="fas fa-cubes fa-2x mb-2" style="color: white;"></i>
                    <h6 class="text-white-50">Tổng sản phẩm</h6>
                    <h3 class="text-white"><?= number_format($total_products) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-line text-success me-2"></i>
                        Doanh thu 7 ngày gần nhất
                    </h5>
                    <small class="text-muted">Cập nhật: <?= date('H:i, d/m/Y') ?></small>
                </div>
                <div class="card-body p-4">
                    <canvas id="revenueChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            
            <div class="card mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-calendar-day text-primary me-2"></i>
                        Hôm nay
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom border-light">
                        <span class="text-muted fw-semibold">Đơn hàng mới</span>
                        <strong class="text-primary fs-5"><?= $today_orders ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2">
                        <span class="text-muted fw-semibold">Doanh thu đạt</span>
                        <strong class="text-success fs-5"><?= number_format($today_revenue, 0, ',', '.') ?> ₫</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Top 5 sản phẩm bán chạy
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($top_products)): ?>
                        <p class="text-center text-muted py-4">Chưa có dữ liệu</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_products as $i => $p): ?>
                                <a href="quan_ly_san_pham.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-primary rounded-pill fs-6 p-2 shadow-sm" style="min-width: 35px;"><?= $i + 1 ?></span>
                                        <div>
                                            <div class="fw-semibold text-truncate" style="max-width: 180px;"><?= e($p['product_name']) ?></div>
                                            <small class="text-muted"><?= number_format($p['total_sold']) ?> đã bán</small>
                                        </div>
                                    </div>
                                    <i class="fas fa-angle-right text-primary"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-history text-info me-2"></i>
                Hoạt động gần đây
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="timeline">
                <?php if (empty($activities)): ?>
                    <p class="text-center text-muted py-3">Chưa có hoạt động nào hôm nay</p>
                <?php else: ?>
                    <?php foreach ($activities as $act): ?>
                        <div class="d-flex align-items-center gap-3 activity-list-item">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" 
                                     style="background-color: var(--<?= $act['type'] === 'order' ? 'primary' : 'success' ?>);">
                                    <i class="fas fa-<?= $act['type'] === 'order' ? 'shopping-bag' : 'user-plus' ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-0 fw-semibold"><?= e($act['message']) ?></p>
                                <small class="text-muted"><?= date('H:i', strtotime($act['created_at'])) ?></small>
                            </div>
                            <i class="fas fa-clock text-gray"></i>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Hàm khởi tạo biểu đồ
    function createChart(gridColor, textColor) {
        const ctx = document.getElementById('revenueChart');
        if (window.myChart) {
            window.myChart.destroy();
        }

        const labels = <?= json_encode($chart_labels) ?>;
        const values = <?= json_encode($chart_values) ?>;

        window.myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: values,
                    borderColor: 'rgb(34, 197, 94)', // Hoặc var(--success)
                    backgroundColor: 'rgba(34, 197, 94, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(34, 197, 94)',
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        // Tùy chỉnh màu tooltip theo theme
                        backgroundColor: textColor === '#f1f5f9' ? 'rgba(45, 52, 54, 0.9)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: textColor === '#f1f5f9' ? '#f1f5f9' : '#2d3436',
                        bodyColor: textColor === '#f1f5f9' ? '#f1f5f9' : '#2d3436',
                        borderColor: textColor,
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: ctx => `Doanh thu: ${ctx.parsed.y.toLocaleString('vi-VN')} ₫`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: value => value.toLocaleString('vi-VN') + ' ₫'
                        },
                        grid: { color: gridColor, drawBorder: false }
                    },
                    x: { 
                        ticks: { color: textColor },
                        grid: { display: false, color: gridColor }
                    }
                }
            }
        });
    }

    // Lấy màu sắc động dựa trên Theme
    function getChartColors(theme) {
        const rootStyles = getComputedStyle(document.documentElement);
        if (theme === 'dark') {
            return {
                gridColor: 'rgba(255, 255, 255, 0.1)',
                textColor: rootStyles.getPropertyValue('--dark') || '#f1f5f9' 
            };
        } else {
            return {
                gridColor: 'rgba(0, 0, 0, 0.1)',
                textColor: rootStyles.getPropertyValue('--dark') || '#2d3436'
            };
        }
    }

    // Xử lý Dark Mode và Biểu đồ
    const html = document.documentElement;
    const initialTheme = html.getAttribute('data-theme') || 'light';
    const initialColors = getChartColors(initialTheme);
    createChart(initialColors.gridColor, initialColors.textColor);

    // Bắt sự kiện đổi theme từ tieu_de.php
    document.getElementById('themeToggle').addEventListener('click', () => {
        const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        const newColors = getChartColors(newTheme); 
        createChart(newColors.gridColor, newColors.textColor);
    });

    // Tự reload mỗi 5 phút
    setTimeout(() => location.reload(), 5 * 60 * 1000);
</script>