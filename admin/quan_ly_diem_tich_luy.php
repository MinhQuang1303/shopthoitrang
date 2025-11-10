<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/layouts/tieu_de.php';



// Xóa điểm
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    try {
        $pdo->prepare("DELETE FROM Points WHERE point_id = ?")->execute([$id]);
        flash_set('success', "Đã xóa bản ghi #$id thành công!");
    } catch (Exception $e) {
        flash_set('error', "Lỗi khi xóa!");
    }
    header("Location: quan_ly_diem_tich_luy.php");
    exit;
}

// Thêm hoặc sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $points = (int)($_POST['points'] ?? 0);
    $type = $_POST['type'] ?? 'earn';
    $description = trim($_POST['description'] ?? '');

    if ($user_id <= 0 || $points == 0) {
        flash_set('error', 'Vui lòng chọn người dùng và nhập điểm hợp lệ!');
    } else {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE Points SET user_id=?, order_id=?, points=?, type=?, description=? WHERE point_id=?");
                $stmt->execute([$user_id, $order_id, $points, $type, $description, $id]);
                flash_set('success', "Cập nhật bản ghi #$id thành công!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO Points (user_id, order_id, points, type, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $order_id, $points, $type, $description]);
                flash_set('success', "Thêm bản ghi mới thành công!");
            }
        } catch (Exception $e) {
            flash_set('error', 'Lỗi: Không thể lưu dữ liệu!');
        }
    }
    header("Location: quan_ly_diem_tich_luy.php");
    exit;
}

// Lấy danh sách người dùng
$users = $pdo->query("SELECT user_id, full_name FROM Users ORDER BY full_name ASC")->fetchAll();

// Tìm kiếm
$q = trim($_GET['q'] ?? '');
$where = $q ? "WHERE u.full_name LIKE ?" : '';
$params = $q ? ["%$q%"] : [];

// Lấy dữ liệu điểm
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name 
    FROM Points p 
    JOIN Users u ON p.user_id = u.user_id 
    $where 
    ORDER BY p.point_id DESC
");
$stmt->execute($params);
$data = $stmt->fetchAll();

// Lấy dữ liệu chỉnh sửa (nếu có)
$edit = null;
if (isset($_GET['sua'])) {
    $id = (int)$_GET['sua'];
    $stmt = $pdo->prepare("SELECT * FROM Points WHERE point_id = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý điểm tích lũy - Admin</title>

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

        .btn-warning { background: var(--warning); border: none; }
        .btn-warning:hover { background: #d97706; }

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

        .point-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .point-earn { background: #d1fae5; color: #065f46; }
        .point-redeem { background: #fee2e2; color: #991b1b; }

        [data-theme="dark"] .point-earn { background: #064e3b; color: #6ee7b7; }
        [data-theme="dark"] .point-redeem { background: #7f1d1d; color: #fca5a5; }

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
            <i class="fas fa-coins text-warning me-2"></i>
            Quản lý điểm tích lũy
        </h1>
        <a href="quan_ly_diem_tich_luy.php" class="btn btn-outline-secondary btn-sm">
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
        <!-- Form Thêm / Sửa -->
        <div class="col-lg-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas <?= $edit ? 'fa-edit text-warning' : 'fa-plus-circle text-success' ?> me-2"></i>
                    <?= $edit ? 'Chỉnh sửa điểm' : 'Thêm điểm mới' ?>
                </h5>
                <form method="post">
                    <input type="hidden" name="id" value="<?= $edit['point_id'] ?? '' ?>">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Người dùng <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Chọn người dùng --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['user_id'] ?>" <?= ($edit['user_id'] ?? '') == $u['user_id'] ? 'selected' : '' ?>>
                                        <?= e($u['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Điểm <span class="text-danger">*</span></label>
                            <input type="number" name="points" class="form-control" required min="1" value="<?= $edit['points'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Loại</label>
                            <select name="type" class="form-select">
                                <option value="earn" <?= ($edit['type'] ?? 'earn') === 'earn' ? 'selected' : '' ?>>Tích điểm</option>
                                <option value="redeem" <?= ($edit['type'] ?? '') === 'redeem' ? 'selected' : '' ?>>Đổi điểm</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Mô tả</label>
                            <input type="text" name="description" class="form-control" value="<?= e($edit['description'] ?? '') ?>" placeholder="Mua hàng, hoàn tiền...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas <?= $edit ? 'fa-save' : 'fa-plus' ?> me-1"></i>
                                <?= $edit ? 'Cập nhật' : 'Thêm mới' ?>
                            </button>
                            <?php if ($edit): ?>
                                <a href="quan_ly_diem_tich_luy.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tìm kiếm + Danh sách -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <!-- Tìm kiếm -->
                    <div class="p-3 border-bottom" style="background: #f8fafc;">
                        <form method="get" class="d-flex gap-2">
                            <input type="text" name="q" class="form-control" placeholder="Tìm theo tên người dùng..." value="<?= e($q) ?>">
                            <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                            <?php if ($q): ?>
                                <a href="quan_ly_diem_tich_luy.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($data)): ?>
                        <div class="empty-state">
                            <i class="fas fa-coins"></i>
                            <h5>Chưa có điểm tích lũy</h5>
                            <p class="text-muted">Hãy thêm điểm đầu tiên!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Người dùng</th>
                                        <th>Điểm</th>
                                        <th>Loại</th>
                                        <th>Mô tả</th>
                                        <th>Ngày</th>
                                        <th class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $r): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= e($r['point_id']) ?></span></td>
                                        <td class="fw-semibold"><?= e($r['full_name']) ?></td>
                                        <td>
                                            <span class="point-badge <?= $r['type'] === 'earn' ? 'point-earn' : 'point-redeem' ?>">
                                                <?= $r['type'] === 'earn' ? '+' : '-' ?><?= abs($r['points']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $r['type'] === 'earn' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $r['type'] === 'earn' ? 'Tích điểm' : 'Đổi điểm' ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?= e($r['description']) ?: '—' ?></small></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '—' ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="?sua=<?= $r['point_id'] ?>" class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?xoa=<?= $r['point_id'] ?>" class="btn btn-sm btn-danger delete-btn" title="Xóa">
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
                title: 'Xóa điểm tích lũy?',
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