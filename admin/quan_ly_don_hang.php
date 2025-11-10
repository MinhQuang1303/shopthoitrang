<?php
// PHP Logic (Giữ nguyên)
require_once __DIR__ . '/../includes/ket_noi_db.php';
// Đảm bảo file ham_chung.php chứa hàm flash_set, e, isAdmin
require_once __DIR__ . '/../includes/ham_chung.php'; 

// ✅ Kiểm tra quyền admin
if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// ==============================
// 🔧 XỬ LÝ CẬP NHẬT TRẠNG THÁI
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';

    // Chỉ chấp nhận giá trị hợp lệ
    $allowed = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed)) {
        flash_set('error', 'Trạng thái không hợp lệ!');
        header('Location: quan_ly_don_hang.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE Orders SET status=? WHERE order_id=?");
    $stmt->execute([$status, $order_id]);

    flash_set('success', '✅ Cập nhật trạng thái đơn hàng thành công!');
    header('Location: quan_ly_don_hang.php');
    exit;
}

// ==============================
// 📦 LẤY DANH SÁCH ĐƠN HÀNG
// ==============================
$orders = $pdo->query("
    SELECT 
        o.order_id, 
        o.created_at, 
        o.status, 
        o.total_amount, 
        u.full_name, 
        u.email 
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    ORDER BY o.order_id DESC
")->fetchAll();

// Hàm chuyển trạng thái sang badge màu
function getStatusBadge($status) {
    $colors = [
        'pending'   => 'warning',  // Chờ xác nhận
        'confirmed' => 'info',     // Đã xác nhận
        'shipping'  => 'primary',  // Đang giao
        'delivered' => 'success',  // Đã giao
        'cancelled' => 'danger'    // Đã hủy
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
    <title>Quản lý đơn hàng - Admin</title>

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
            --info: #06b6d4; /* Thêm màu info cho trạng thái */
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

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s;
        }
        
        /* Chỉnh sửa riêng cho form-select và button nhỏ */
        .form-select-sm {
             border-radius: 8px !important;
             padding: 6px 10px !important;
             font-size: 0.85rem;
             height: auto; 
             border: 1.5px solid var(--border);
        }

        .btn-sm {
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
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

        .table tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        [data-theme="dark"] .table tr:hover {
            background-color: rgba(91, 122, 255, 0.1);
        }

        .badge {
            font-weight: 600;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 14px 20px;
            font-weight: 500;
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
            <i class="fas fa-shopping-bag text-primary me-2"></i>
            Quản lý đơn hàng
        </h1>
        <a href="quan_ly_don_hang.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Làm mới
        </a>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h5>Chưa có đơn hàng nào</h5>
                    <p class="text-muted">Hãy chờ khách hàng đặt hàng đầu tiên!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="text-center">
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Email</th>
                                <th>Tổng tiền</th>
                                <th>Ngày tạo</th>
                                <th style="min-width: 180px;">Trạng thái</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="text-center fw-bold"><span class="badge bg-primary"><?= e($o['order_id']) ?></span></td>
                                <td class="fw-semibold"><?= e($o['full_name']) ?></td>
                                <td><small class="text-muted"><?= e($o['email']) ?></small></td>
                                <td class="text-end text-danger fw-semibold"><?= number_format($o['total_amount'], 0, ',', '.') ?>₫</td>
                                <td class="text-center text-muted"><small><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></small></td>
                                
                                <td>
                                    <form method="post" class="d-flex gap-2 align-items-center">
                                        <input type="hidden" name="order_id" value="<?= e($o['order_id']) ?>">
                                        <select name="status" class="form-select form-select-sm flex-grow-1">
                                            <?php 
                                            $statusOptions = [
                                                'pending'   => 'Chờ xác nhận',
                                                'confirmed' => 'Đã xác nhận',
                                                'shipping'  => 'Đang giao',
                                                'delivered' => 'Đã giao',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            foreach ($statusOptions as $key => $label): ?>
                                                <option value="<?= $key ?>" <?= $o['status'] == $key ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary flex-shrink-0" title="Lưu trạng thái">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                </td>

                                <td class="text-center">
                                    <a href="chi_tiet_don_hang.php?id=<?= e($o['order_id']) ?>" class="btn btn-sm btn-info text-white" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
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