<?php
require_once __DIR__ . '/../includes/ham_chung.php';

if (!isAdmin()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php';

$page_title = "Quản lý đánh giá";
$current_page = "reviews";

// ===== CSRF Token =====
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ===== XỬ LÝ POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        flash_set('error', 'CSRF token không hợp lệ!');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $review_id = (int)($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($review_id > 0) {
        try {
            switch ($action) {
                case 'approve':
                    $stmt = $pdo->prepare("UPDATE Reviews SET is_approved = 1 WHERE review_id = ?");
                    $stmt->execute([$review_id]);
                    flash_set('success', '✅ Đã duyệt đánh giá!');
                    break;

                case 'hide':
                    $stmt = $pdo->prepare("UPDATE Reviews SET is_approved = 0 WHERE review_id = ?");
                    $stmt->execute([$review_id]);
                    flash_set('success', '👁️ Đã ẩn đánh giá!');
                    break;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM Reviews WHERE review_id = ?");
                    $stmt->execute([$review_id]);
                    flash_set('success', '🗑️ Đã xóa đánh giá!');
                    break;
            }
        } catch (PDOException $e) {
            error_log("Error managing review: " . $e->getMessage());
            flash_set('error', 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ====== BỘ LỌC ======
$where = [];
$params = [];

if (isset($_GET['approved']) && $_GET['approved'] !== '') {
    $where[] = "r.is_approved = ?";
    $params[] = $_GET['approved'] === '1' ? 1 : 0;
}

if (!empty($_GET['rating'])) {
    $where[] = "r.rating = ?";
    $params[] = (int)$_GET['rating'];
}

if (!empty($_GET['search'])) {
    $search = '%' . trim($_GET['search']) . '%';
    $where[] = "(p.product_name LIKE ? OR u.full_name LIKE ? OR r.comment LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ====== LẤY DANH SÁCH ======
$sql = "
    SELECT 
        r.review_id, r.product_id, r.user_id, r.order_id,
        r.rating, r.comment, r.is_approved, r.created_at,
        u.full_name AS user_name, u.email AS user_email,
        p.product_name, o.order_code
    FROM Reviews r
    JOIN Users u ON r.user_id = u.user_id
    JOIN Products p ON r.product_id = p.product_id
    JOIN Orders o ON r.order_id = o.order_id
    $where_sql
    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== ĐẾM ======
$counts = [
    'approved' => $pdo->query("SELECT COUNT(*) FROM Reviews WHERE is_approved = 1")->fetchColumn(),
    'pending'  => $pdo->query("SELECT COUNT(*) FROM Reviews WHERE is_approved = 0")->fetchColumn(),
    'total'    => $pdo->query("SELECT COUNT(*) FROM Reviews")->fetchColumn()
];

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<div class="container-fluid py-4">
    <h3 class="fw-bold mb-3">⭐ Quản lý đánh giá sản phẩm</h3>

    <!-- THỐNG KÊ -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h4 class="mb-0"><?= $counts['pending'] ?></h4>
                    <small class="text-muted">Chưa duyệt</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h4 class="mb-0"><?= $counts['approved'] ?></h4>
                    <small class="text-muted">Đã duyệt</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h4 class="mb-0"><?= $counts['total'] ?></h4>
                    <small class="text-muted">Tổng</small>
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG -->
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr class="text-center">
                        <th>#</th>
                        <th>Người dùng</th>
                        <th>Sản phẩm</th>
                        <th>Nội dung</th>
                        <th>Sao</th>
                        <th>Trạng thái</th>
                        <th>Ngày</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Chưa có đánh giá nào.</td></tr>
                    <?php else: foreach ($reviews as $r): ?>
                        <tr>
                            <td class="text-center"><?= e($r['review_id']) ?></td>
                            <td>
                                <strong><?= e($r['user_name'] ?: 'Khách') ?></strong><br>
                                <small class="text-muted"><?= e($r['user_email']) ?></small>
                            </td>
                            <td>
                                <?= e($r['product_name']) ?><br>
                                <small class="text-muted">ĐH: #<?= e($r['order_code']) ?></small>
                            </td>
                            <td><?= nl2br(e($r['comment'])) ?></td>
                            <td class="text-warning text-center"><?= str_repeat('⭐', $r['rating']) ?></td>
                            <td class="text-center">
                                <?php if ($r['is_approved']): ?>
                                    <span class="badge bg-success">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                            <td class="text-center">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">

                                    <?php if ($r['is_approved'] == 0): ?>
                                        <button name="action" value="approve" class="btn btn-success btn-sm" title="Duyệt">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php else: ?>
                                        <button name="action" value="hide" class="btn btn-warning btn-sm" title="Ẩn">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    <?php endif; ?>

                                    <button name="action" value="delete" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Xóa đánh giá này?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
