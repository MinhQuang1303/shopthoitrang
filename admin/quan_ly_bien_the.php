<?php
// admin/quan_ly_bien_the.php
require_once __DIR__.'/../includes/ket_noi_db.php';
require_once __DIR__.'/../includes/ham_chung.php';

if (!isAdmin()) {
    header('Location: '.base_url('admin/indexadmin.php'));
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$act = $_GET['act'] ?? '';
$search = trim($_GET['search'] ?? '');

// Xử lý thêm (chỉ nếu POST hợp lệ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if ($act === 'add') {
        $product_id = (int)$_POST['product_id'];
        $color = trim($_POST['color']);
        $size = strtoupper(trim($_POST['size']));
        $stock = (int)$_POST['stock'];
        $sku = trim($_POST['sku']) ?: 'SKU-' . strtoupper(uniqid());

        if ($product_id && $color && $size) {
            try {
                $stmt = $pdo->prepare("INSERT INTO Product_Variants (product_id, color, size, stock, sku) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$product_id, $color, $size, $stock, $sku]);
                flash_set('success', 'Thêm biến thể thành công!');
            } catch (Exception $e) {
                flash_set('error', 'Lỗi: Không thể thêm biến thể.');
            }
        } else {
            flash_set('error', 'Vui lòng điền đầy đủ thông tin!');
        }
        header('Location: quan_ly_bien_the.php');
        exit;
    }
}

// Xóa
if ($act === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->prepare("DELETE FROM Product_Variants WHERE variant_id = ?")->execute([$id]);
        flash_set('success', 'Đã xóa biến thể!');
    } catch (Exception $e) {
        flash_set('error', 'Lỗi khi xóa!');
    }
    header('Location: quan_ly_bien_the.php');
    exit;
}

// Lấy sản phẩm (chỉ cần ID + tên)
$products = $pdo->query("SELECT product_id, product_name FROM Products ORDER BY product_name")->fetchAll();

// Lấy biến thể + tìm kiếm
$sql = "SELECT pv.*, p.product_name 
        FROM Product_Variants pv 
        JOIN Products p ON pv.product_id = p.product_id";
$params = [];

if ($search !== '') {
    $sql .= " WHERE p.product_name LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY pv.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$variants = $stmt->fetchAll();

require_once __DIR__.'/layouts/tieu_de.php';
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý biến thể - Admin</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s;
        }

        .btn-success { background: var(--success); border: none; }
        .btn-success:hover { background: #059669; transform: translateY(-1px); }

        .btn-danger { background: var(--danger); border: none; }
        .btn-danger:hover { background: #dc2626; }

        .table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 0;
        }

        [data-theme="dark"] .table { background: #1e293b; }

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

        .sku-badge {
            background: #e0e7ff;
            color: #4338ca;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        [data-theme="dark"] .sku-badge {
            background: #312e81;
            color: #c7d2fe;
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

<!-- Navbar -->
<nav class="navbar navbar-admin navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="indexadmin.php">
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
            <i class="fas fa-layer-group text-primary me-2"></i>
            Quản lý biến thể
        </h1>
        <a href="quan_ly_bien_the.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Làm mới
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Thêm biến thể -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-plus-circle text-success me-2"></i> Thêm biến thể mới
                </h5>
                <form method="post" action="?act=add">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>"><?= e($p['product_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Màu sắc <span class="text-danger">*</span></label>
                        <input name="color" type="text" class="form-control" placeholder="Đỏ, Xanh, Trắng..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kích cỡ <span class="text-danger">*</span></label>
                        <input name="size" type="text" class="form-control" placeholder="S, M, L, XL..." required style="text-transform: uppercase;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tồn kho</label>
                        <input name="stock" type="number" min="0" class="form-control" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">SKU</label>
                        <input name="sku" type="text" class="form-control" placeholder="Tự động nếu để trống">
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-plus me-1"></i> Thêm biến thể
                    </button>
                </form>
            </div>
        </div>

        <!-- Danh sách biến thể -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <!-- Tìm kiếm -->
                    <div class="p-3 border-bottom" style="background: #f8fafc;">
                        <form method="get" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="Tìm sản phẩm..." value="<?= e($search) ?>">
                            <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                            <?php if ($search): ?>
                                <a href="quan_ly_bien_the.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($variants)): ?>
                        <div class="empty-state">
                            <i class="fas fa-layer-group"></i>
                            <h5>Chưa có biến thể nào</h5>
                            <p class="text-muted">Hãy thêm biến thể đầu tiên!</p>
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
                                        <th>Tồn</th>
                                        <th>SKU</th>
                                        <th class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($variants as $v): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= e($v['variant_id']) ?></span></td>
                                        <td class="fw-semibold"><?= e($v['product_name']) ?></td>
                                        <td><span class="badge bg-light text-dark"><?= e($v['color']) ?></span></td>
                                        <td><strong><?= e($v['size']) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $v['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                <?= e($v['stock']) ?>
                                            </span>
                                        </td>
                                        <td><code class="sku-badge"><?= e($v['sku']) ?></code></td>
                                        <td class="text-center">
                                            <a href="?act=delete&id=<?= $v['variant_id'] ?>" 
                                               class="btn btn-sm btn-danger delete-btn" title="Xóa">
                                                <i class="fas fa-trash"></i>
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
    </div>
</div>

<?php require_once __DIR__.'/layouts/chan_trang.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Dark Mode + SweetAlert2 -->
<script>
    // Dark Mode
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

    themeToggle.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const newTheme = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeToggle.querySelector('i').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });

    // Xóa với SweetAlert2
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            Swal.fire({
                title: 'Xóa biến thể?',
                text: "Hành động này không thể hoàn tác!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>
</body>
</html>