<?php
// PHP Logic (Giữ nguyên)
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/layouts/tieu_de.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

$variants = $pdo->query("
    SELECT pv.variant_id, pv.sku, pv.color, pv.size, pv.stock, p.product_name 
    FROM Product_Variants pv
    JOIN Products p ON pv.product_id = p.product_id
    ORDER BY p.product_name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $variant_id = (int)$_POST['variant_id'];
    $new_stock = (int)$_POST['new_stock'];

    $stmt = $pdo->prepare("UPDATE Product_Variants SET stock=? WHERE variant_id=?");
    $stmt->execute([$new_stock, $variant_id]);

    flash_set('success', 'Cập nhật tồn kho thành công!');
    header('Location: quan_ly_ton_kho.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tồn kho - Admin</title>

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

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            border-color: var(--border);
            color: var(--gray);
        }

        .table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 0;
            border: none !important; /* Loại bỏ border mặc định của table-bordered */
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
            font-weight: 500;
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

        .page-title {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.8rem;
            margin: 0;
        }

        [data-theme="dark"] .page-title {
            color: #e2e8f0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
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
            <i class="fas fa-cubes text-primary me-2"></i>
            Quản lý tồn kho sản phẩm
        </h1>
        <a href="quan_ly_ton_kho.php" class="btn btn-outline-secondary btn-sm">
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
            <?php if (empty($variants)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h5>Không tìm thấy biến thể sản phẩm nào</h5>
                    <p class="text-muted">Hãy thêm sản phẩm và các biến thể của chúng!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0"> 
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sản phẩm</th>
                                <th>Màu</th>
                                <th>Size</th>
                                <th>SKU</th>
                                <th>Tồn kho</th>
                                <th class="text-center">Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variants as $v): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= e($v['variant_id']) ?></span></td>
                                <td class="fw-semibold"><?= e($v['product_name']) ?></td>
                                <td><?= e($v['color']) ?></td>
                                <td><?= e($v['size']) ?></td>
                                <td><small class="text-muted"><?= e($v['sku']) ?></small></td>
                                <td><span class="badge bg-<?= $v['stock'] > 0 ? 'success' : 'danger' ?>"><?= e($v['stock']) ?></span></td>
                                <td class="text-center">
                                    <form method="post" class="d-flex gap-2 justify-content-center">
                                        <input type="hidden" name="variant_id" value="<?= e($v['variant_id']) ?>">
                                        <input type="number" name="new_stock" min="0" 
                                               class="form-control form-control-sm" style="width:100px; padding: 6px 10px;" 
                                               placeholder="Nhập SL" required>
                                        <button class="btn btn-sm btn-primary">
                                            <i class="fas fa-save"></i> Lưu
                                        </button>
                                    </form>
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