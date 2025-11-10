<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

// Không cần start session nữa — đã khởi tại ham_chung

// Kiểm tra đăng nhập
if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Xử lý POST xóa (an toàn hơn GET) với CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product_id'])) {
    $pid = (int)($_POST['remove_product_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash_set('error', 'Yêu cầu không hợp lệ (CSRF).');
    } else {
        $del = $pdo->prepare('DELETE FROM Wishlist WHERE user_id = ? AND product_id = ?');
        $del->execute([$user_id, $pid]);
        flash_set('success', 'Đã xóa sản phẩm khỏi yêu thích.');
    }
    header('Location: ' . base_url('user/danh_sach_yeu_thich.php'));
    exit;
}

// Lấy danh sách yêu thích
$sql = "SELECT w.product_id, p.product_name, p.thumbnail_url, p.base_price, p.discount_percent,
               pi.image_url AS main_image
        FROM Wishlist w
        JOIN Products p ON w.product_id = p.product_id
        LEFT JOIN Product_Images pi ON pi.product_id = p.product_id AND pi.is_main = 1
        WHERE w.user_id = ?
        ORDER BY w.wishlist_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll();

// Helper: trả về URL ảnh đầy đủ (có file) hoặc ảnh mặc định
function get_product_image_url(array $item): string {
    $file = $item['main_image'] ?? $item['thumbnail_url'] ?? '';
    $assetsDir = __DIR__ . '/../assets/images/san_pham/';
    $default = 'no-image.jpg';
    if (empty($file) || !file_exists($assetsDir . $file)) {
        return base_url('assets/images/san_pham/' . $default);
    }
    return base_url('assets/images/san_pham/' . ltrim($file, '/'));
}

// Hiển thị header
require_once __DIR__ . '/../user/tieu_de_k_banner.php';
?>

<div class="container py-5">
    <?php flash_show(); ?>

    <h2 class="fw-bold mb-4">Sản phẩm yêu thích</h2>

    <?php if (!empty($wishlist)): ?>
        <div class="row g-3">
            <?php foreach ($wishlist as $item): ?>
                <div class="col-md-3">
                    <div class="card h-100 shadow-sm">
                        <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($item['product_id'])) ?>" class="text-decoration-none text-dark">
                            <img src="<?= get_product_image_url($item) ?>" 
                                 class="card-img-top" 
                                 alt="<?= e($item['product_name']) ?>" 
                                 style="height:200px; object-fit:cover;">
                            <div class="card-body">
                                <h6 class="card-title"><?= e($item['product_name']) ?></h6>
                                <p class="text-danger mb-0"><?= currency($item['base_price'] * (1 - $item['discount_percent']/100)) ?></p>
                                <?php if(!empty($item['discount_percent'])): ?>
                                    <span class="text-muted text-decoration-line-through"><?= currency($item['base_price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <form method="post" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="remove_product_id" value="<?= e($item['product_id']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa sản phẩm này khỏi yêu thích?')">Xóa</button>
                            </form>
                            <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($item['product_id'])) ?>" class="btn btn-sm btn-primary">Xem</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Bạn chưa có sản phẩm yêu thích nào.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>
