<?php
// PHP Logic (Giữ nguyên)
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php'; // Đảm bảo hàm chung (như e() và isAdmin()) được load

if (!isAdmin()) {
    header('Location: ../indexadmin.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin đơn hàng và khách hàng
$stmt = $pdo->prepare("
    SELECT 
        o.*, 
        u.full_name, 
        u.email, 
        u.phone, 
        u.address
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    // Nếu không tìm thấy đơn hàng, chuyển hướng hoặc hiển thị lỗi với giao diện đồng bộ
    flash_set('error', 'Đơn hàng không tồn tại.');
    header('Location: quan_ly_don_hang.php');
    exit;
}

// Lấy chi tiết sản phẩm trong đơn hàng
$items = $pdo->prepare("
    SELECT 
        od.*, 
        p.product_name, 
        pv.color, 
        pv.size
    FROM Order_Details od
    JOIN Product_Variants pv ON od.variant_id = pv.variant_id
    JOIN Products p ON pv.product_id = p.product_id
    WHERE od.order_id = ?
");
$items->execute([$order_id]);
$details = $items->fetchAll();

// Hàm chuyển trạng thái sang badge màu (Sử dụng lại từ quan_ly_don_hang.php)
function getStatusBadge($status) {
    $colors = [
        'pending'   => 'warning', 
        'confirmed' => 'info',     
        'shipping'  => 'primary',  
        'delivered' => 'success',  
        'cancelled' => 'danger'    
    ];
    $labels = [
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping'  => 'Đang giao',
        'delivered' => 'Đã giao',
        'cancelled' => 'Đã hủy'
    ];
    $color = $colors[$status] ?? 'secondary';
    $label = $labels[$status] ?? 'Không rõ';
    return '<span class="badge bg-' . $color . '">' . $label . '</span>';
}

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= e($order_id) ?> - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #94a3b8;
            --border: #e2e8f0;
        }

        [data-theme="dark"] {
            --primary: #5b7aff;
            --light: #1e293b;
            --dark: #f1f5f9;
            --gray: #64748b;
            --border: #334155;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .navbar-admin {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        [data-theme="dark"] .navbar-admin {
            background: #1e293b;
            border-bottom: 1px solid #334155;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            background: white;
        }

        [data-theme="dark"] .card {
            background: #1e293b;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .card-header {
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        [data-theme="dark"] .card-header {
            background: #334155;
            border-bottom: 1px solid #334155;
            color: #e2e8f0;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s;
        }

        .btn-secondary {
            background: var(--gray);
            border: none;
        }

        .btn-secondary:hover {
            background: #64748b;
        }

        .table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 0;
        }

        [data-theme="dark"] .table {
            background: #1e293b;
        }

        .table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border: none;
            padding: 16px;
        }

        [data-theme="dark"] .table th {
            background: #334155;
            color: #94a3b8;
        }

        .table td {
            padding: 16px;
            vertical-align: middle;
            border-color: var(--border);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        [data-theme="dark"] .table-hover tbody tr:hover {
            background-color: rgba(91, 122, 255, 0.1);
        }

        .table tfoot th {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .badge {
            font-weight: 600;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .page-title {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.8rem;
            margin: 0;
        }

        [data-theme="dark"] .page-title {
            color: #e2e8f0;
        }

        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.3rem;
            color: var(--gray);
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s;
        }
        .theme-toggle:hover {
            background: rgba(0,0,0,0.05);
            color: var(--primary);
        }
        [data-theme="dark"] .theme-toggle:hover {
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-admin navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="admin.php">
            <i class="fas fa-cogs me-2"></i> Admin Panel
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <button class="theme-toggle" id="themeToggle" title="Đổi giao diện">
                <i class="fas fa-moon"></i>
            </button>
            <span class="text-muted small">Chào, <strong><?= $_SESSION['admin_name'] ?? 'Admin' ?></strong></span>
        </div>
    </div>
</nav>

<div class="container-fluid py-4 px-4 px-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">
            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
            Chi tiết đơn hàng #<?= e($order['order_id']) ?>
        </h1>
        <a href="quan_ly_don_hang.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-user-circle me-1"></i> Thông tin khách hàng
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong><i class="fas fa-user me-2 text-primary"></i> Tên khách hàng:</strong> <span class="fw-semibold"><?= e($order['full_name']) ?></span></p>
                    <p class="mb-2"><strong><i class="fas fa-envelope me-2 text-primary"></i> Email:</strong> <?= e($order['email']) ?></p>
                    <p class="mb-2"><strong><i class="fas fa-phone me-2 text-primary"></i> Số điện thoại:</strong> <?= e($order['phone']) ?></p>
                    <p class="mb-2"><strong><i class="fas fa-map-marker-alt me-2 text-primary"></i> Địa chỉ giao hàng:</strong> <?= e($order['address']) ?></p>
                    <hr>
                    <p class="mb-2"><strong><i class="fas fa-calendar-alt me-2 text-primary"></i> Ngày tạo:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                    <p class="mb-0">
                        <strong><i class="fas fa-info-circle me-2 text-primary"></i> Trạng thái:</strong> 
                        <?= getStatusBadge($order['status']) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list-alt me-1"></i> Sản phẩm trong đơn hàng
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Phân loại</th>
                                    <th class="text-end">Giá</th>
                                    <th class="text-center">SL</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details as $d): ?>
                                <tr>
                                    <td><?= e($d['product_name']) ?></td>
                                    <td><?= e($d['color']) ?> / <?= e($d['size']) ?></td>
                                    <td class="text-end"><?= number_format($d['price'], 0, ',', '.') ?>₫</td>
                                    <td class="text-center fw-semibold"><?= e($d['quantity']) ?></td>
                                    <td class="text-end fw-semibold text-danger"><?= number_format($d['price'] * $d['quantity'], 0, ',', '.') ?>₫</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Tổng cộng đơn hàng</th>
                                    <th class="text-end text-danger"><?= number_format($order['total_amount'], 0, ',', '.') ?>₫</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="quan_ly_don_hang.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại quản lý đơn hàng
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    // Load theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateIcon(savedTheme);

    themeToggle.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const newTheme = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon(newTheme);
    });

    function updateIcon(theme) {
        const icon = themeToggle.querySelector('i');
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
</script>
</body>
</html>