<?php
require_once __DIR__ . '/layouts/tieu_de.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';

// === XỬ LÝ THÊM / SỬA ===
if (isset($_POST['action'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $id = $_POST['event_id'] ?? '';

    if (empty($title) || empty($content) || empty($event_date)) {
        flash_set('error', 'Vui lòng điền đầy đủ thông tin bắt buộc!');
    } else {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO Events (title, content, image_url, event_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $image_url, $event_date]);
                flash_set('success', 'Thêm sự kiện thành công!');
            } elseif ($_POST['action'] === 'edit' && $id) {
                $stmt = $pdo->prepare("UPDATE Events SET title=?, content=?, image_url=?, event_date=? WHERE event_id=?");
                $stmt->execute([$title, $content, $image_url, $event_date, $id]);
                flash_set('success', 'Cập nhật sự kiện thành công!');
            }
        } catch (Exception $e) {
            flash_set('error', 'Lỗi: Không thể lưu dữ liệu!');
        }
    }
    header("Location: quan_ly_su_kien.php");
    exit;
}

// === XỬ LÝ XÓA ===
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM Events WHERE event_id=?")->execute([$id]);
        flash_set('success', 'Đã xóa sự kiện!');
    } catch (Exception $e) {
        flash_set('error', 'Lỗi khi xóa!');
    }
    header("Location: quan_ly_su_kien.php");
    exit;
}

// === LẤY DỮ LIỆU ĐỂ SỬA ===
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id=?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// === TÌM KIẾM ===
$q = trim($_GET['q'] ?? '');
$where = $q ? "WHERE title LIKE ?" : '';
$params = $q ? ["%$q%"] : [];

// === LẤY DANH SÁCH SỰ KIỆN ===
$sql = "SELECT * FROM Events $where ORDER BY event_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sự kiện - Admin</title>

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

        .event-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid var(--border);
        }

        .preview-img {
            width: 150px;
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
            <i class="fas fa-calendar-alt text-primary me-2"></i>
            Quản lý sự kiện / bài viết
        </h1>
        <a href="quan_ly_su_kien.php" class="btn btn-outline-secondary btn-sm">
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
        <div class="col-lg-5">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas <?= $edit ? 'fa-edit text-warning' : 'fa-plus-circle text-success' ?> me-2"></i>
                    <?= $edit ? 'Chỉnh sửa sự kiện' : 'Thêm sự kiện mới' ?>
                </h5>
                <form method="post">
                    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
                    <input type="hidden" name="event_id" value="<?= $edit['event_id'] ?? '' ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required 
                               value="<?= e($edit['title'] ?? '') ?>" placeholder="Nhập tiêu đề sự kiện">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nội dung <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="5" required 
                                  placeholder="Mô tả chi tiết sự kiện..."><?= e($edit['content'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hình ảnh (URL)</label>
                        <input type="text" name="image_url" class="form-control" 
                               value="<?= e($edit['image_url'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                        <?php if (!empty($edit['image_url'])): ?>
                            <img src="<?= e($edit['image_url']) ?>" class="preview-img" alt="Preview">
                            <small class="text-muted d-block mt-1">Ảnh hiện tại</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngày sự kiện <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" class="form-control" required 
                               value="<?= $edit['event_date'] ?? date('Y-m-d') ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="fas <?= $edit ? 'fa-save' : 'fa-plus' ?> me-1"></i>
                            <?= $edit ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                        <?php if ($edit): ?>
                            <a href="quan_ly_su_kien.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách sự kiện -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-0">
                    <!-- Tìm kiếm -->
                    <div class="p-3 border-bottom" style="background: #f8fafc;">
                        <form method="get" class="d-flex gap-2">
                            <input type="text" name="q" class="form-control" placeholder="Tìm theo tiêu đề..." value="<?= e($q) ?>">
                            <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                            <?php if ($q): ?>
                                <a href="quan_ly_su_kien.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($events)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            <h5>Chưa có sự kiện nào</h5>
                            <p class="text-muted">Hãy thêm sự kiện đầu tiên!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tiêu đề</th>
                                        <th>Ngày</th>
                                        <th>Hình ảnh</th>
                                        <th class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $e): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= e($e['event_id']) ?></span></td>
                                        <td class="fw-semibold"><?= e($e['title']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($e['event_date'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($e['image_url'])): ?>
                                                <img src="<?= e($e['image_url']) ?>" class="event-img" alt="<?= e($e['title']) ?>">
                                            <?php else: ?>
                                                <div class="bg-light border-dashed rounded d-flex align-items-center justify-content-center event-img">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="?edit=<?= $e['event_id'] ?>" class="btn btn-sm btn-warning" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?= $e['event_id'] ?>" 
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
                title: 'Xóa sự kiện?',
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

    // Focus vào form khi sửa
    <?php if ($edit): ?>
    document.querySelector('input[name="title"]').focus();
    <?php endif; ?>
</script>
</body>
</html>