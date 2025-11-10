<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/layouts/tieu_de.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// Danh mục
$cats = $pdo->query("SELECT category_id, category_name FROM Categories ORDER BY category_name")->fetchAll();

// Xử lý thêm / sửa / xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['base_price'] ?? 0);
    $discount = (float)($_POST['discount_percent'] ?? 0);
    $cat_id = (int)($_POST['category_id'] ?? 0);
    $id = $_POST['product_id'] ?? '';

    // Xử lý ảnh
    $img = '';
    $uploadDir = __DIR__ . '/../assets/images/san_pham/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($_FILES['image_url']['name'])) {
        $fileName = basename($_FILES['image_url']['name']);
        $fileTmp  = $_FILES['image_url']['tmp_name'];
        $targetFile = $uploadDir . $fileName;
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($fileTmp, $targetFile)) {
                $img = $fileName;
            } else {
                flash_set('error', 'Lỗi tải ảnh lên. Kiểm tra quyền thư mục.');
                $img = $edit['image_url'] ?? null;
            }
        } else {
            flash_set('error', 'Chỉ chấp nhận: jpg, jpeg, png, gif, webp.');
            $img = $edit['image_url'] ?? null;
        }
    } else {
        $img = $edit['image_url'] ?? null;
    }

    if ($id) {
        $sql = "UPDATE Products SET product_name=?, description=?, base_price=?, discount_percent=?, category_id=?";
        $params = [$name, $desc, $price, $discount, $cat_id];
        if ($img) { $sql .= ", image_url=?"; $params[] = $img; }
        $sql .= " WHERE product_id=?";
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        flash_set('success', 'Cập nhật sản phẩm thành công!');
    } else {
        if (!$img) {
            flash_set('error', 'Vui lòng chọn ảnh sản phẩm!');
        } else {
            $stmt = $pdo->prepare("INSERT INTO Products (product_name, description, base_price, discount_percent, category_id, image_url) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $desc, $price, $discount, $cat_id, $img]);
            flash_set('success', 'Thêm sản phẩm thành công!');
        }
    }

    header('Location: quan_ly_san_pham.php');
    exit;
}

if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $stmt = $pdo->prepare("SELECT image_url FROM Products WHERE product_id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if ($product && $product['image_url']) {
        $filePath = __DIR__ . '/../assets/images/san_pham/' . $product['image_url'];
        if (file_exists($filePath)) @unlink($filePath);
    }
    $pdo->prepare("DELETE FROM Products WHERE product_id=?")->execute([$id]);
    flash_set('success', 'Đã xóa sản phẩm!');
    header('Location: quan_ly_san_pham.php');
    exit;
}

$products = $pdo->query("SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON p.category_id=c.category_id ORDER BY p.product_id DESC")->fetchAll();

$edit = null;
if (isset($_GET['sua'])) {
    $id = (int)$_GET['sua'];
    $stmt = $pdo->prepare("SELECT * FROM Products WHERE product_id=?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - Admin</title>

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
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

        .btn-primary { background: var(--primary); border: none; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

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

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid var(--border);
        }

        .preview-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            margin-top: 8px;
            border: 2px solid var(--border);
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

        .price {
            font-weight: 600;
            color: var(--success);
        }

        .discount {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        [data-theme="dark"] .discount {
            background: #451a03;
            color: #fcd34d;
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
            <i class="fas fa-box-open text-primary me-2"></i>
            Quản lý sản phẩm
        </h1>
        <a href="quan_ly_san_pham.php" class="btn btn-outline-secondary btn-sm">
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
        <!-- Form Thêm/Sửa -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas <?= $edit ? 'fa-edit text-warning' : 'fa-plus-circle text-success' ?> me-2"></i>
                    <?= $edit ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?>
                </h5>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= e($edit['product_id'] ?? '') ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input name="product_name" type="text" class="form-control" required 
                               value="<?= e($edit['product_name'] ?? '') ?>" placeholder="Nhập tên sản phẩm">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= $c['category_id'] ?>" <?= ($edit['category_id'] ?? '') == $c['category_id'] ? 'selected' : '' ?>>
                                    <?= e($c['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" class="form-control" rows="4" 
                                  placeholder="Mô tả chi tiết sản phẩm..."><?= e($edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Giá gốc (₫)</label>
                            <input name="base_price" type="number" min="0" step="1000" class="form-control" 
                                   value="<?= e($edit['base_price'] ?? '') ?>" placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Giảm giá (%)</label>
                            <input name="discount_percent" type="number" min="0" max="100" class="form-control" 
                                   value="<?= e($edit['discount_percent'] ?? '') ?>" placeholder="0">
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Ảnh sản phẩm <?= !$edit ? '<span class="text-danger">*</span>' : '' ?></label>
                        <input name="image_url" type="file" class="form-control" accept="image/*" <?= !$edit ? 'required' : '' ?>>
                        <?php if (!empty($edit['image_url'])): ?>
                            <img src="../assets/images/san_pham/<?= e($edit['image_url']) ?>" class="preview-img" alt="Preview">
                            <small class="text-muted d-block mt-1">Ảnh hiện tại</small>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas <?= $edit ? 'fa-save' : 'fa-plus' ?> me-1"></i>
                            <?= $edit ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                        <?php if ($edit): ?>
                            <a href="quan_ly_san_pham.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h5>Chưa có sản phẩm nào</h5>
                            <p class="text-muted">Hãy thêm sản phẩm đầu tiên!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Danh mục</th>
                                        <th>Giá</th>
                                        <th>Giảm</th>
                                        <th>Ngày tạo</th>
                                        <th class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= e($p['product_id']) ?></span></td>
                                        <td>
                                            <?php if (!empty($p['image_url'])): ?>
                                                <img src="../assets/images/san_pham/<?= e($p['image_url']) ?>" class="product-img" alt="<?= e($p['product_name']) ?>">
                                            <?php else: ?>
                                                <div class="bg-light border-dashed rounded d-flex align-items-center justify-content-center product-img">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-semibold"><?= e($p['product_name']) ?></td>
                                        <td><span class="badge bg-light text-dark"><?= e($p['category_name'] ?? 'Chưa có') ?></span></td>
                                        <td class="price"><?= currency($p['base_price']) ?></td>
                                        <td>
                                            <?php if ($p['discount_percent'] > 0): ?>
                                                <span class="discount">-<?= e($p['discount_percent']) ?>%</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $p['created_at'] ? date('d/m/Y', strtotime($p['created_at'])) : '—' ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="?sua=<?= $p['product_id'] ?>" class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?xoa=<?= $p['product_id'] ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm(<?= json_encode('Xóa sản phẩm "' . ($p['product_name'] ?? '') . '"?') ?>)" title="Xóa">
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

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Dark Mode Toggle -->
<script>
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

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