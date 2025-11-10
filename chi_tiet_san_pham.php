<?php
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__ . '/includes/ham_chung.php';
require_once __DIR__ . '/includes/class_gio_hang.php';

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) session_start();

// Định nghĩa ảnh mặc định (Placeholder)
const DEFAULT_IMAGE = 'placeholder.jpg'; // Đảm bảo bạn có file này trong thư mục /assets/images/san_pham/

// ======================
// 🛒 XỬ LÝ AJAX THÊM GIỎ HÀNG
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    header('Content-Type: application/json; charset=utf-8');

    // 1. Kiểm tra CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF token không hợp lệ!']);
        exit;
    }

    $variant_id = (int)($_POST['variant_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    // 2. Kiểm tra tồn tại biến thể
    $sql = "SELECT pv.*, p.product_name
            FROM Product_Variants pv
            JOIN Products p ON pv.product_id = p.product_id
            WHERE pv.variant_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$variant_id]);
    $variant = $stmt->fetch();

    if (!$variant) {
        echo json_encode(['status' => 'error', 'message' => 'Biến thể không tồn tại!']);
        exit;
    }
    
    // 3. Thêm vào giỏ hàng
    $gio = new Cart($pdo);
    $gio->add($variant_id, $qty);

    // 4. Cập nhật và trả về kết quả
    $_SESSION['cart_count'] = $gio->countItems();
    echo json_encode([
        'status' => 'success',
        'message' => 'Đã thêm sản phẩm vào giỏ hàng!',
        'cart_count' => $_SESSION['cart_count']
    ]);
    exit;
}

// ======================
// 📦 LẤY CHI TIẾT SẢN PHẨM & DỮ LIỆU LIÊN QUAN
// ======================
$product_id = (int)($_GET['product_id'] ?? 0);

// Lấy thông tin sản phẩm chính
$stmt = $pdo->prepare("SELECT * FROM Products WHERE product_id = ?");
$stmt->execute([$product_id]);
$p = $stmt->fetch();

if (!$p) {
    require_once __DIR__ . '/views/tieu_de.php';
    echo '<div class="container"><div class="alert alert-danger text-center mt-5">Sản phẩm không tồn tại.</div></div>';
    require_once __DIR__ . '/views/chan_trang.php';
    exit;
}

// Lấy Ảnh, Biến thể, Đánh giá, Liên quan
$images = $pdo->prepare("SELECT image_url FROM Product_Images WHERE product_id = ? ORDER BY image_id ASC");
$images->execute([$product_id]);
$images = $images->fetchAll(PDO::FETCH_COLUMN);

// Nếu ảnh chính bị thiếu, dùng ảnh mặc định
if (empty($p['thumbnail_url'])) {
    $p['thumbnail_url'] = DEFAULT_IMAGE;
}

// Kiểm tra và thêm ảnh thumbnail vào đầu mảng ảnh phụ nếu nó chưa có
if (!in_array($p['thumbnail_url'], $images)) {
    array_unshift($images, $p['thumbnail_url']);
}

$variants = $pdo->prepare("SELECT * FROM Product_Variants WHERE product_id = ?");
$variants->execute([$product_id]);
$variants = $variants->fetchAll();

$reviews = $pdo->prepare("SELECT * FROM Reviews WHERE product_id = ? ORDER BY created_at DESC");
$reviews->execute([$product_id]);
$reviews = $reviews->fetchAll();

$related_products = [];
if (!empty($p['category_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Products WHERE category_id = ? AND product_id != ? LIMIT 6");
    $stmt->execute([$p['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
}

// Kiểm tra sản phẩm đã trong danh sách yêu thích chưa
$isLoved = false;
if (isLogged()) {
    $check = $pdo->prepare("SELECT 1 FROM Wishlist WHERE user_id = ? AND product_id = ?");
    $check->execute([$_SESSION['user']['user_id'], $product_id]);
    $isLoved = $check->fetchColumn() ? true : false;
}

// ======================
// HEADER (TIEU DE)
// ======================
// Ẩn banner ở trang chi tiết sản phẩm để không trùng/không cần thiết
$hide_banner = true;
require_once __DIR__ . '/views/tieu_de.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="container py-5">
    <div class="row g-4 align-items-start">
        <div class="col-md-6">
            <div class="text-center mb-3 main-image-wrapper">
                <?php $mainImage = $images[0] ?? DEFAULT_IMAGE; // Đã đảm bảo ảnh chính là phần tử đầu tiên ?>
                <img id="main-product-image"
                     src="<?= base_url('assets/images/san_pham/' . e($mainImage)) ?>"
                     class="img-fluid rounded-4 shadow-lg border"
                     alt="<?= e($p['product_name']) ?>"
                     style="max-height: 450px; object-fit: contain; width: 100%;">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                <?php foreach ($images as $img): ?>
                  <img src="<?= base_url('assets/images/san_pham/' . e($img)) ?>"
                       class="thumb border rounded shadow-sm"
                       style="width:80px; height:80px; object-fit: cover; cursor:pointer;"
                       onclick="changeMainImage(this)">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <h2 class="fw-bold mb-3 text-primary"><?= e($p['product_name']) ?></h2>
            <div class="mb-3">
                <h3 class="text-danger fw-bolder mb-0 d-inline-block me-3">
                    <?= currency($p['base_price'] * (1 - $p['discount_percent'] / 100)) ?>
                </h3>
                <?php if ($p['discount_percent'] > 0): ?>
                    <span class="text-muted text-decoration-line-through me-2 fs-5">
                        <?= currency($p['base_price']) ?>
                    </span>
                    <span class="badge bg-success fs-6">-<?= e($p['discount_percent']) ?>%</span>
                <?php endif; ?>
            </div>
            
            <p class="text-secondary small mt-3"><?= nl2br(e($p['description'] ?? '')) ?></p>

            <form method="post" id="form-add-cart" class="p-4 border rounded-4 bg-light shadow-sm mt-4">
                <h5 class="mb-3 fw-bold text-dark">Đặt hàng ngay</h5>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">🎨 Chọn màu</label>
                    <select name="color" id="select-color" class="form-select form-select-lg" required>
                        <option value="" disabled selected>Chọn màu sắc</option>
                        <?php
                        $colors = [];
                        foreach ($variants as $v) {
                            if (!in_array($v['color'], $colors)) {
                                $colors[] = $v['color'];
                                echo '<option value="'.e($v['color']).'">'.e($v['color']).'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">📏 Chọn size</label>
                    <select name="variant_id" id="select-size" class="form-select form-select-lg" required disabled>
                        <option value="">Chọn màu trước</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">🔢 Số lượng</label>
                    <input type="number" name="qty" value="1" min="1" class="form-control w-50 form-control-lg">
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">🛒 Thêm vào giỏ hàng</button>
                    <button type="button" id="btn-love" class="btn border-0 fs-2 p-0" title="Thêm vào yêu thích">
                        <i class="fa-solid fa-heart" style="color: <?= $isLoved ? 'red' : '#999' ?>;"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Mô tả chi tiết</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Đánh giá (<?= count($reviews) ?>)</button>
                </li>
            </ul>
            <div class="tab-content border border-top-0 p-4 rounded-bottom" id="myTabContent">
                <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                    <p><?= nl2br(e($p['description'] ?? 'Không có mô tả chi tiết.')) ?></p>
                </div>
                <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                    <?php if ($reviews): foreach ($reviews as $r): ?>
                        <div class="border p-3 rounded mb-2 bg-white shadow-sm">
                            <strong><?= e($r['user_name'] ?? 'Khách hàng') ?></strong>
                            <span class="text-muted small"> - <?= date('d/m/Y H:i', strtotime($r['created_at'] ?? '')) ?></span>
                            <?php $review_text = trim((string)($r['content'] ?? $r['title'] ?? '')); ?>
                            <?php if ($review_text !== ''): ?>
                                <p class="mt-2 mb-0"><?= nl2br(e($review_text)) ?></p>
                            <?php else: ?>
                                <p class="mt-2 mb-0"><em class="text-muted">Người dùng chưa gửi nội dung đánh giá.</em></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; else: ?>
                        <p>Chưa có đánh giá nào cho sản phẩm này.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<div class="row mt-5">
        <div class="col-12">
            <h4 class="fw-bold mb-4 border-bottom pb-2 text-primary">⚡ Sản phẩm liên quan</h4>
            
            <?php if ($related_products): ?>
            <div class="related-products-carousel mb-3">
                <?php foreach ($related_products as $rp): 
                    $is_discount = $rp['discount_percent'] > 0;
                    $final_price = $rp['base_price'] * (1 - $rp['discount_percent'] / 100);
                    $rp_image = $rp['thumbnail_url'] ?? DEFAULT_IMAGE;
                ?>
                    <div class="product-item-wrapper">
                        <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($rp['product_id'])) ?>" 
                           class="card product-card h-100 shadow-sm text-decoration-none text-dark">
                            <div class="image-container">
                                <img src="<?= base_url('assets/images/san_pham/' . e($rp_image)) ?>" 
                                     class="card-img-top" 
                                     alt="<?= e($rp['product_name']) ?>">
                                <?php if ($is_discount): ?>
                                  <span class="badge bg-danger discount-tag">-<?= e($rp['discount_percent']) ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-2 text-center">
                                <h6 class="card-title mb-1 text-truncate" title="<?= e($rp['product_name']) ?>"><?= e($rp['product_name']) ?></h6>
                                <p class="text-danger fw-bold mb-0 fs-6"><?= currency($final_price) ?></p>
                                <?php if ($is_discount): ?>
                                  <small class="text-muted text-decoration-line-through"><?= currency($rp['base_price']) ?></small>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4">
                <?php 
                // Giả định trang danh mục có URL là 'danh_muc.php?category_id=X'
                $category_link = base_url('san_pham.php?category_id=' . e($p['category_id']));
                ?>
                <a href="<?= $category_link ?>" class="btn btn-outline-primary btn-lg">
                    Xem thêm sản phẩm cùng loại (<?= e(count($related_products)) ?>+) <i class="fa-solid fa-arrow-right-long ms-2"></i>
                </a>
            </div>

            <?php else: ?>
                <p class="text-muted">Không có sản phẩm liên quan trong danh mục này.</p>
            <?php endif; ?>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function changeMainImage(el) {
    // Đặt ảnh chính bằng ảnh thumbnail được click
    document.getElementById('main-product-image').src = el.src;
    // Cập nhật border cho thumbnail đang chọn
    document.querySelectorAll('.thumb').forEach(thumb => {
        thumb.style.border = '1px solid #dee2e6';
    });
    el.style.border = '2px solid #0d6efd'; // Thêm border màu primary của Bootstrap
}

document.addEventListener('DOMContentLoaded', () => {
    // Kích hoạt ảnh đầu tiên làm thumb đang chọn khi load
    const firstThumb = document.querySelector('.thumb');
    if (firstThumb) {
        firstThumb.style.border = '2px solid #0d6efd';
    }

    const form = document.getElementById('form-add-cart');
    const cartCount = document.getElementById('cart-count'); 
    const selectColor = document.getElementById('select-color');
    const selectSize = document.getElementById('select-size');
    const variants = <?= json_encode($variants) ?>;

    // ... (Phần logic JS cho selectColor, selectSize và AJAX Thêm giỏ hàng tương tự phiên bản trước)
    selectColor.addEventListener('change', () => {
        const selectedColor = selectColor.value;
        selectSize.innerHTML = '<option value="" disabled selected>Chọn size</option>';
        const filtered = variants.filter(v => v.color === selectedColor);
        
        if (filtered.length > 0) {
            filtered.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.variant_id;
                opt.textContent = `${v.size} (Còn ${v.stock})`;
                if (v.stock <= 0) {
                    opt.disabled = true;
                    opt.textContent += ' - Hết hàng';
                }
                selectSize.appendChild(opt);
            });
            selectSize.disabled = false;
        } else {
            selectSize.innerHTML = '<option value="">Không có size</option>';
            selectSize.disabled = true;
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!selectSize.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Vui lòng chọn Màu sắc và Kích thước!',
                timer: 2000,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
            return;
        }

        const formData = new FormData(form);
        const res = await fetch('', { method: 'POST', body: formData });
        const data = await res.json();
        
        Swal.fire({
            icon: data.status === 'success' ? 'success' : 'error',
            title: data.message,
            timer: 1500,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
        
        if (data.status === 'success' && cartCount) {
            cartCount.textContent = data.cart_count;
        }
    });

    // ... (Phần logic JS cho nút Yêu thích tương tự phiên bản trước)
    const loveBtn = document.getElementById('btn-love');
    const icon = loveBtn.querySelector('i');
    const productId = '<?= $p['product_id'] ?>';

    loveBtn.addEventListener('click', async () => {
        try {
            const res = await fetch('<?= base_url("api/them_vao_yeu_thich.php") ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ product_id: productId })
            });
            const data = await res.json();
            
            if (data.status === 'added') {
                icon.style.color = 'red';
                Swal.fire({icon: 'success', title: 'Đã thêm vào yêu thích!', toast: true, timer: 1200, showConfirmButton: false, position: 'top-end'});
            } else if (data.status === 'removed') {
                icon.style.color = '#999';
                Swal.fire({icon: 'info', title: 'Đã xóa khỏi yêu thích', toast: true, timer: 1200, showConfirmButton: false, position: 'top-end'});
            } else if (data.status === 'error') {
                Swal.fire({icon: 'warning', title: data.message, showConfirmButton: true})
                    .then(() => window.location.href = '<?= base_url("auth/dang_nhap.php") ?>');
            }
        } catch (err) {
            console.error(err);
            Swal.fire({icon: 'error', title: 'Có lỗi xảy ra!', showConfirmButton: true});
        }
    });
});
</script>

<style>
/* CSS cho phần Chi tiết sản phẩm */
#form-add-cart select { cursor: pointer; }
#form-add-cart select:disabled { background-color: #f8f9fa; }
#form-add-cart .btn-primary:hover { transform: translateY(-2px); transition: 0.2s; box-shadow: 0 0.25rem 0.5rem rgba(0, 123, 255, 0.4) !important; }
.thumb { border: 1px solid #dee2e6; transition: border 0.2s, transform 0.2s; }
.thumb:hover { transform: scale(1.05); border: 2px solid #198754; } /* Hiệu ứng hover cho ảnh nhỏ */
#btn-love { cursor: pointer; transition: transform 0.2s; }
#btn-love:hover { transform: scale(1.3); }

/* Cải thiện hiển thị ảnh chính */
.main-image-wrapper {
    background-color: #f8f9fa; /* Nền nhẹ để ảnh chứa trong đó nổi bật hơn */
    border-radius: 0.5rem;
    padding: 10px;
}
#main-product-image {
    max-height: 450px !important; /* Tăng nhẹ chiều cao */
    object-fit: contain !important; /* Đảm bảo ảnh hiển thị toàn bộ, không bị cắt */
}


/* ... CSS hiện có cho form, thumb, love button ... */

/* Cải thiện hiển thị ảnh chính */
.main-image-wrapper {
    background-color: #f8f9fa; 
    border-radius: 0.5rem;
    padding: 10px;
}
#main-product-image {
    max-height: 450px !important; 
    object-fit: contain !important; 
}

/* CSS MỚI cho Sản phẩm liên quan - Cuộn Ngang */
.related-products-carousel {
    display: flex;
    overflow-x: auto; /* Kích hoạt cuộn ngang */
    padding-bottom: 15px; /* Đủ chỗ cho thanh cuộn (scrollbar) */
    gap: 15px; /* Khoảng cách giữa các sản phẩm */
    -webkit-overflow-scrolling: touch; /* Cuộn mượt trên iOS */
}

/* Ẩn thanh cuộn trên một số trình duyệt (tùy chọn) */
.related-products-carousel::-webkit-scrollbar {
    height: 8px;
}
.related-products-carousel::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}
.related-products-carousel::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.product-item-wrapper {
    flex: 0 0 auto; /* Ngăn các item co lại */
    width: 200px; /* **Định nghĩa chiều rộng cố định cho mỗi sản phẩm (Làm cho nó to hơn)** */
}

.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
    border-radius: 0.5rem;
    border: 1px solid #eee;
}
.product-card:hover {
    transform: translateY(-5px); 
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.product-card .image-container {
    position: relative;
    overflow: hidden;
}
.product-card .card-img-top {
    height: 180px; /* Tăng chiều cao ảnh lên 180px */
    object-fit: cover; 
    transition: transform 0.3s ease;
}
.product-card:hover .card-img-top {
    transform: scale(1.05); 
}
.product-card .discount-tag {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 0.3em 0.6em;
    font-size: 0.8rem;
    font-weight: bold;
    z-index: 10;
}
.text-truncate {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
<?php require_once __DIR__ . '/views/chan_trang.php'; ?>