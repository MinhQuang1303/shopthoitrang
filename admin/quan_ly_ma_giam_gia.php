<?php
// PHP Logic (Đã điều chỉnh để xử lý Sửa/Xóa qua GET/Modal)
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php'; // Đảm bảo hàm chung được load

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// ==============================
// 🔧 XỬ LÝ THÊM / SỬA / XÓA
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $code = trim($_POST['voucher_code'] ?? '');
    // Chuyển đổi thành số float, kiểm tra rỗng để tránh lỗi SQL
    $discount = (float)($_POST['discount_percent'] ?? 0); 
    $valid_to = $_POST['valid_to'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0; // Thêm trạng thái hoạt động

    // Thêm mới
    if ($action === 'add') {
        if (empty($code) || $discount <= 0 || empty($valid_to)) {
             flash_set('error', 'Vui lòng điền đầy đủ Mã, Phần trăm giảm giá, và Ngày hết hạn.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO Vouchers (voucher_code, discount_percent, valid_to, is_active)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$code, $discount, $valid_to]);
            flash_set('success', 'Thêm mã giảm giá thành công!');
        }
    } 
    // Cập nhật
    elseif ($action === 'edit_save') {
        $id = (int)$_POST['voucher_id'];
        if (empty($code) || $discount <= 0 || empty($valid_to)) {
             flash_set('error', 'Vui lòng điền đầy đủ thông tin cập nhật.');
        } else {
            $stmt = $pdo->prepare("
                UPDATE Vouchers 
                SET voucher_code=?, discount_percent=?, valid_to=?, is_active=?
                WHERE voucher_id=?
            ");
            $stmt->execute([$code, $discount, $valid_to, $is_active, $id]);
            flash_set('success', 'Cập nhật mã giảm giá thành công!');
        }
    } 
    // Xóa (dùng GET hoặc POST đơn giản)
    elseif ($action === 'delete_confirm') {
        $id = (int)$_POST['voucher_id'];
        $pdo->prepare("DELETE FROM Vouchers WHERE voucher_id=?")->execute([$id]);
        flash_set('success', 'Xóa mã giảm giá thành công!');
    }

    header('Location: quan_ly_ma_giam_gia.php');
    exit;
}

// Lấy danh sách voucher
$vouchers = $pdo->query("SELECT * FROM Vouchers ORDER BY voucher_id DESC")->fetchAll();

// Lấy voucher cần chỉnh sửa (Sẽ dùng Modal thay vì form ngay trên trang)
$edit_voucher = null;
if (isset($_GET['sua'])) {
    $id = (int)$_GET['sua'];
    $stmt = $pdo->prepare("SELECT * FROM Vouchers WHERE voucher_id=?");
    $stmt->execute([$id]);
    $edit_voucher = $stmt->fetch();
}

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý mã giảm giá - Admin</title>

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

        .btn-success {
             border-radius: 12px;
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

        /* Modal styling */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] .modal-content {
             background-color: #1e293b;
             color: #e2e8f0;
        }
        [data-theme="dark"] .modal-header, 
        [data-theme="dark"] .modal-footer {
             border-color: #334155;
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
            <i class="fas fa-ticket-alt text-primary me-2"></i>
            Quản lý mã giảm giá
        </h1>
        <a href="quan_ly_ma_giam_gia.php" class="btn btn-outline-secondary btn-sm">
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

    <div class="row g-4 mb-4">
        <div class="col-12">
            <form method="post" class="card p-4">
                <h5 class="mb-3 fw-bold text-success">
                    <i class="fas fa-plus-circle me-2"></i> Thêm mã giảm giá mới
                </h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mã code</label>
                        <input type="text" name="voucher_code" class="form-control" placeholder="Mã (VD: TET20)" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Giảm (%)</label>
                        <input type="number" name="discount_percent" min="1" max="100" class="form-control" placeholder="Phần trăm (1-100)" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Ngày hết hạn</label>
                        <input type="date" name="valid_to" class="form-control" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <input type="hidden" name="action" value="add">
                        <button class="btn btn-success w-100">
                            <i class="fas fa-plus me-1"></i> Thêm mã
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($vouchers)): ?>
                 <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <h5>Chưa có mã giảm giá nào</h5>
                    <p class="text-muted">Hãy thêm mã giảm giá đầu tiên!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã</th>
                                <th>Giảm (%)</th>
                                <th>Ngày hết hạn</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vouchers as $v): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= e($v['voucher_id']) ?></span></td>
                                <td class="fw-semibold"><?= e($v['voucher_code']) ?></td>
                                <td><span class="badge bg-warning text-dark"><?= e($v['discount_percent']) ?>%</span></td>
                                <td>
                                    <?php 
                                        $valid_to_date = new DateTime($v['valid_to']);
                                        $now = new DateTime();
                                        $is_expired = $valid_to_date < $now;
                                        $badge_class = $is_expired ? 'bg-danger' : 'bg-info';
                                        $date_format = date('d/m/Y', strtotime($v['valid_to']));
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= $date_format ?></span>
                                </td>
                                <td>
                                    <?php 
                                        if($is_expired) {
                                            echo '<span class="badge bg-danger">Hết hạn</span>';
                                        } else {
                                            echo $v['is_active'] ? '<span class="badge bg-success">Hoạt động</span>' : '<span class="badge bg-secondary">Đã tắt</span>';
                                        }
                                    ?>
                                </td>
                                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning me-2 btn-edit-voucher" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editVoucherModal"
                                            data-id="<?= e($v['voucher_id']) ?>"
                                            data-code="<?= e($v['voucher_code']) ?>"
                                            data-discount="<?= e($v['discount_percent']) ?>"
                                            data-valid-to="<?= e($v['valid_to']) ?>"
                                            data-is-active="<?= e($v['is_active']) ?>">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận xóa mã giảm giá <?= e($v['voucher_code']) ?>?')">
                                        <input type="hidden" name="voucher_id" value="<?= $v['voucher_id'] ?>">
                                        <input type="hidden" name="action" value="delete_confirm">
                                        <button class="btn btn-sm btn-danger" type="submit">
                                            <i class="fas fa-trash-alt"></i> Xóa
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

<div class="modal fade" id="editVoucherModal" tabindex="-1" aria-labelledby="editVoucherModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="editVoucherModalLabel"><i class="fas fa-edit me-1 text-warning"></i> Chỉnh sửa Mã giảm giá</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="edit_save">
            <input type="hidden" name="voucher_id" id="edit-voucher-id">
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Mã code</label>
                <input type="text" name="voucher_code" id="edit-voucher-code" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Giảm (%)</label>
                <input type="number" name="discount_percent" id="edit-discount-percent" min="1" max="100" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Ngày hết hạn</label>
                <input type="date" name="valid_to" id="edit-valid-to" class="form-control" required>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="edit-is-active" name="is_active" value="1">
              <label class="form-check-label fw-semibold" for="edit-is-active">Trạng thái hoạt động</label>
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const editVoucherModal = document.getElementById('editVoucherModal');
    if (editVoucherModal) {
        editVoucherModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            // Extract info from data-bs-* attributes
            const id = button.getAttribute('data-id');
            const code = button.getAttribute('data-code');
            const discount = button.getAttribute('data-discount');
            const validTo = button.getAttribute('data-valid-to');
            const isActive = button.getAttribute('data-is-active');

            // Update the modal's content.
            const modalTitle = editVoucherModal.querySelector('.modal-title');
            const modalBodyInputId = editVoucherModal.querySelector('#edit-voucher-id');
            const modalBodyInputCode = editVoucherModal.querySelector('#edit-voucher-code');
            const modalBodyInputDiscount = editVoucherModal.querySelector('#edit-discount-percent');
            const modalBodyInputValidTo = editVoucherModal.querySelector('#edit-valid-to');
            const modalBodyInputIsActive = editVoucherModal.querySelector('#edit-is-active');

            modalTitle.textContent = `Chỉnh sửa Mã giảm giá: ${code}`;
            modalBodyInputId.value = id;
            modalBodyInputCode.value = code;
            modalBodyInputDiscount.value = discount;
            modalBodyInputValidTo.value = validTo;
            modalBodyInputIsActive.checked = (isActive === '1');
        });
    }
</script>

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