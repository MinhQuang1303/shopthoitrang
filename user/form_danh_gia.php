<?php
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';

// Bắt buộc phải kiểm tra session trước khi load HTML
if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Lấy user_id từ session (user stored as array)
$user_id = $_SESSION['user']['user_id'] ?? null;

// Lấy product_id và order_id từ GET
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

$errors = [];
$success = '';

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token không hợp lệ.';
    }

    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $product_id_post = intval($_POST['product_id'] ?? 0);
    $order_id_post = intval($_POST['order_id'] ?? 0);

    // Validate
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Vui lòng chọn số sao từ 1 đến 5.';
    }
    if (empty($content)) {
        $errors[] = 'Nội dung đánh giá không được để trống.';
    }
    if (empty($user_id)) {
        $errors[] = 'Bạn cần đăng nhập để gửi đánh giá.';
    }

    if (empty($errors)) {
        try {
            // ket_noi_db.php sets $pdo and $db; prefer $db
            $conn = $db ?? $pdo ?? null;
            if (!$conn) throw new Exception('Không có kết nối CSDL.');

            $stmt = $conn->prepare("INSERT INTO Reviews (user_id, product_id, order_id, rating, title, content, created_at) VALUES (:user_id, :product_id, :order_id, :rating, :title, :content, NOW())");
            $stmt->execute([
                ':user_id' => $user_id,
                ':product_id' => $product_id_post,
                ':order_id' => $order_id_post,
                ':rating' => $rating,
                ':title' => $title,
                ':content' => $content
            ]);

            $success = 'Đánh giá của bạn đã được gửi thành công!';
            // reset CSRF token to avoid accidental repost
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (PDOException $e) {
            // Nếu lỗi do thiếu cột (Unknown column) thì thử chèn mà không có title/content
            error_log('[form_danh_gia] PDOException: ' . $e->getMessage());
            $msg = $e->getMessage();
            $isMissingColumn = ($e->getCode() === '42S22') || (stripos($msg, 'Unknown column') !== false);
            if ($isMissingColumn) {
                try {
                    $stmt2 = $conn->prepare("INSERT INTO Reviews (user_id, product_id, order_id, rating, created_at) VALUES (:user_id, :product_id, :order_id, :rating, NOW())");
                    $stmt2->execute([
                        ':user_id' => $user_id,
                        ':product_id' => $product_id_post,
                        ':order_id' => $order_id_post,
                        ':rating' => $rating
                    ]);
                    $success = 'Đánh giá của bạn đã được gửi thành công! (Lưu không có tiêu đề/nội dung do cấu trúc DB)';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } catch (PDOException $e2) {
                    error_log('[form_danh_gia] Fallback PDOException: ' . $e2->getMessage());
                    $errors[] = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']) ? 'Lỗi khi lưu đánh giá: ' . e($e2->getMessage()) : 'Đã có lỗi xảy ra khi gửi đánh giá.';
                }
            } else {
                $errors[] = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']) ? 'Lỗi khi lưu đánh giá: ' . e($e->getMessage()) : 'Đã có lỗi xảy ra khi gửi đánh giá.';
            }
        } catch (Exception $e) {
            error_log('[form_danh_gia] Exception: ' . $e->getMessage());
            $errors[] = 'Đã có lỗi xảy ra: ' . e($e->getMessage());
        }
    }
}

require_once __DIR__ . '/../views/tieu_de.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Gửi đánh giá</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= e($success) ?></div>
                    <?php endif; ?>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $err) echo e($err) . '<br>'; ?>
                        </div>
                    <?php endif; ?>

                    <form id="reviewForm" method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="product_id" value="<?= e($product_id) ?>">
                        <input type="hidden" name="order_id" value="<?= e($order_id) ?>">

                        <div class="mb-3">
                            <label class="form-label">Sản phẩm</label>
                            <div class="form-control-plaintext"><?= $product_id ? 'ID: ' . e($product_id) : 'Chưa chọn sản phẩm' ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Đánh giá</label>
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>">
                                    <label for="star<?= $i ?>" title="<?= $i ?> sao">⭐</label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề (tùy chọn)</label>
                            <input id="title" name="title" type="text" class="form-control" maxlength="120" placeholder="Tóm tắt đánh giá">
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Nội dung đánh giá</label>
                            <textarea id="content" name="content" class="form-control" rows="6" required maxlength="2000" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('user/trang_ca_nhan.php') ?>" class="btn btn-secondary">Quay lại</a>
                            <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    font-size: 2rem;
    margin-bottom: 1rem;
}
.star-rating input {
    display: none;
}
.star-rating label {
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
}
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffc107;
}
</style>

<script>
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    const rating = document.querySelector('input[name="rating"]:checked');
    if (!rating) {
        alert('Vui lòng chọn số sao đánh giá!');
        e.preventDefault();
    }
});
</script>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>
