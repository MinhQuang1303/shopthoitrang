<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/layouts/tieu_de.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// Xử lý thêm / sửa / xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $id = $_POST['category_id'] ?? '';

    if ($name === '') {
        flash_set('error', 'Tên danh mục không được để trống!');
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE Categories SET category_name=?, description=? WHERE category_id=?");
            $stmt->execute([$name, $desc, $id]);
            flash_set('success', 'Cập nhật danh mục thành công!');
        } else {
            $stmt = $pdo->prepare("INSERT INTO Categories (category_name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc]);
            flash_set('success', 'Thêm danh mục thành công!');
        }
    }
    header('Location: quan_ly_danh_muc.php');
    exit;
}

if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $pdo->prepare("DELETE FROM Categories WHERE category_id=?")->execute([$id]);
    flash_set('success', 'Đã xóa danh mục!');
    header('Location: quan_ly_danh_muc.php');
    exit;
}

// Lấy danh sách danh mục
$cats = $pdo->query("SELECT * FROM Categories ORDER BY category_id DESC")->fetchAll();
$edit = null;
if (isset($_GET['sua'])) {
    $id = (int)$_GET['sua'];
    $stmt = $pdo->prepare("SELECT * FROM Categories WHERE category_id=?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - Admin</title>

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
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
            <i class="fas fa-folder-open text-primary me-2"></i>
            Quản lý danh mục
        </h1>
        <a href="quan_ly_danh_muc.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Làm mới
        </a>
    </div>

    <!-- Flash Messages -->
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

    <div class="row g-4">
        <!-- Form Thêm/Sửa -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas <?= $edit ? 'fa-edit text-warning' : 'fa-plus-circle text-success' ?> me-2"></i>
                    <?= $edit ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' ?>
                </h5>
                <form method="post">
                    <input type="hidden" name="category_id" value="<?= e($edit['category_id'] ?? '') ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
                        <input name="category_name" type="text" class="form-control" required 
                               value="<?= e($edit['category_name'] ?? '') ?>" placeholder="Nhập tên danh mục">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" class="form-control" rows="4" 
                                  placeholder="Mô tả ngắn về danh mục..."><?= e($edit['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas <?= $edit ? 'fa-save' : 'fa-plus' ?> me-1"></i>
                            <?= $edit ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                        <?php if ($edit): ?>
                            <a href="quan_ly_danh_muc.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách danh mục -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($cats)): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h5>Chưa có danh mục nào</h5>
                            <p class="text-muted">Hãy thêm danh mục đầu tiên!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên danh mục</th>
                                        <th>Mô tả</th>
                                        <th>Ngày tạo</th>
                                        <th class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cats as $c): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= e($c['category_id']) ?></span></td>
                                        <td class="fw-semibold"><?= e($c['category_name']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $c['description'] ? e(substr($c['description'], 0, 50)) . (strlen($c['description']) > 50 ? '...' : '') : '<em>Không có mô tả</em>' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $c['created_at'] ? date('d/m/Y H:i', strtotime($c['created_at'])) : '—' ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="?sua=<?= $c['category_id'] ?>" class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                                          <a href="?xoa=<?= $c['category_id'] ?>" class="btn btn-sm btn-danger" 
                                                              onclick="return confirm(<?= json_encode('Xóa danh mục "' . ($c['category_name'] ?? '') . '"?') ?>)" title="Xóa">
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