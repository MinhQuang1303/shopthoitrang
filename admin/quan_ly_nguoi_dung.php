<?php
// PHP Logic (Giữ nguyên)
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php'; // Đảm bảo hàm chung (như e() và isAdmin()) được load

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// ==============================
// 🔧 XỬ LÝ HÀNH ĐỘNG
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM Users WHERE user_id=?")->execute([$user_id]);
        flash_set('success', 'Đã xóa người dùng!');
    } elseif ($action === 'toggle_role') {
        $pdo->prepare("UPDATE Users 
                         SET role = CASE WHEN role='admin' THEN 'customer' ELSE 'admin' END 
                         WHERE user_id=?")->execute([$user_id]);
        flash_set('success', 'Đã thay đổi quyền người dùng!');
    } elseif ($action === 'toggle_status') {
        $pdo->prepare("UPDATE Users 
                         SET is_verified = CASE WHEN is_verified=1 THEN 0 ELSE 1 END 
                         WHERE user_id=?")->execute([$user_id]);
        flash_set('success', 'Đã thay đổi trạng thái người dùng!');
    }

    header('Location: quan_ly_nguoi_dung.php');
    exit;
}

// ✅ Lấy danh sách người dùng
$users = $pdo->query("SELECT * FROM Users ORDER BY user_id DESC")->fetchAll();

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Admin</title>

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

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.2s;
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
            <i class="fas fa-users text-primary me-2"></i>
            Quản lý người dùng
        </h1>
        <a href="quan_ly_nguoi_dung.php" class="btn btn-outline-secondary btn-sm">
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
            <?php if (empty($users)): ?>
                 <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <h5>Không có người dùng nào được tìm thấy</h5>
                    <p class="text-muted">Danh sách người dùng đang trống.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Quyền</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th class="text-center" style="min-width: 250px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= e($u['user_id']) ?></span></td>
                                <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                                <td><small class="text-muted"><?= e($u['email']) ?></small></td>
                                <td>
                                    <?php 
                                        $role_class = $u['role'] === 'admin' ? 'bg-danger' : 'bg-info';
                                        $role_label = $u['role'] === 'admin' ? 'Admin' : 'Khách hàng';
                                    ?>
                                    <span class="badge <?= $role_class ?>"><?= $role_label ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $status_class = $u['is_verified'] ? 'bg-success' : 'bg-secondary';
                                        $status_label = $u['is_verified'] ? 'Đã xác minh' : 'Chưa xác minh';
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= $status_label ?></span>
                                </td>
                                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></small></td>
                                <td class="d-flex gap-2 justify-content-center">
                                    <form method="post">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <input type="hidden" name="action" value="toggle_role">
                                        <button class="btn btn-sm btn-warning" title="Đổi quyền Admin/Customer">
                                            <i class="fas fa-user-tag"></i> Đổi quyền
                                        </button>
                                    </form>

                                    <form method="post">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <button class="btn btn-sm btn-info" title="<?= $u['is_verified'] ? 'Khóa/Tắt Xác minh' : 'Mở/Xác minh' ?>">
                                            <i class="fas <?= $u['is_verified'] ? 'fa-lock-open' : 'fa-lock' ?>"></i> 
                                            <?= $u['is_verified'] ? 'Khóa' : 'Mở' ?>
                                        </button>
                                    </form>

                                    <form method="post" onsubmit="return confirm('Xác nhận xóa người dùng: <?= e($u['full_name']) ?>?')">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm btn-danger" title="Xóa người dùng">
                                            <i class="fas fa-trash"></i> Xóa
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