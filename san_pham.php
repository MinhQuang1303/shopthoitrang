<?php
// san_pham.php
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de.php';

// --- 1. Xử lý logic truy vấn dữ liệu ---
// Lấy danh mục (nếu có) và đảm bảo là số nguyên hợp lệ
$cat = isset($_GET['category_id']) ? filter_var($_GET['category_id'], FILTER_VALIDATE_INT) : null;
$params = [];
$sql = "SELECT * FROM Products";
if ($cat !== false && $cat > 0) { // Kiểm tra tính hợp lệ
  $sql .= " WHERE category_id = ?";
  $params[] = $cat;

  // Lấy tên danh mục để hiển thị tiêu đề hấp dẫn hơn
  $stmtCat = $pdo->prepare("SELECT category_name FROM Categories WHERE category_id = ? LIMIT 1");
  $stmtCat->execute([$cat]);
  $categoryName = $stmtCat->fetchColumn();
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xác định tiêu đề trang
$pageTitle = ($cat && isset($categoryName)) 
    ? 'Sản phẩm ' . e($categoryName) 
    : 'Khám phá tất cả sản phẩm mới nhất';
?>

<div class="container py-5">
  
  <h1 class="text-center fw-bolder mb-2 text-primary-custom"><?= $pageTitle ?></h1>
  <?php if ($cat && isset($categoryName)): ?>
      <p class="text-center text-muted mb-5 lead">
          Những lựa chọn tuyệt vời trong danh mục **<?= e($categoryName) ?>** đang chờ bạn.
      </p>
  <?php else: ?>
      <p class="text-center text-muted mb-5 lead">
          Cập nhật những sản phẩm mới nhất và được yêu thích nhất của chúng tôi.
      </p>
  <?php endif; ?>

  <?php if (empty($products)): ?>
    <div class="alert alert-info text-center rounded-4 shadow-sm py-4">
      <i class="fas fa-box-open fa-2x mb-3"></i>
      <h4 class="alert-heading fw-bold">Rất tiếc!</h4>
      Hiện tại chưa có sản phẩm nào trong danh mục này. Vui lòng quay lại sau hoặc thử danh mục khác.
    </div>
  <?php else: ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ($products as $p): ?>
        <?php
        // --- 2. Xử lý logic hiển thị ảnh (Tối ưu hóa: Tránh truy vấn DB trong loop nếu có thể) ---
        // Ưu tiên thumbnail_url, nếu không có mới tìm ảnh đầu tiên.
        // Tuy nhiên, ở đây giữ nguyên logic cũ để đảm bảo tính năng nếu thumbnail_url trống.
        $mainImage = $p['thumbnail_url'] ?? null;
        if (!$mainImage) {
            $stmtImg = $pdo->prepare("SELECT image_url FROM Product_Images WHERE product_id = ? ORDER BY image_id ASC LIMIT 1");
            $stmtImg->execute([$p['product_id']]);
            $mainImage = $stmtImg->fetchColumn();
        }
        $finalImage = $mainImage ? ltrim($mainImage, '/') : 'no-image.jpg';
        $imagePath = base_url('assets/images/san_pham/' . $finalImage);
        
        $discountedPrice = $p['base_price'] * (1 - $p['discount_percent'] / 100);
        $originalPrice = $p['base_price'];
        ?>
        
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex"> <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($p['product_id'])) ?>" class="text-decoration-none w-100">
            <div class="product-card-pro h-100">
              
              <div class="product-card-image-wrapper">
                <img src="<?= $imagePath ?>" class="card-img-top product-card-img" 
                     alt="<?= e($p['product_name']) ?>" loading="lazy">
                
                <?php if ($p['discount_percent'] > 0): ?>
                  <span class="product-discount-badge">-<?= e($p['discount_percent']) ?>%</span>
                <?php endif; ?>
                
                <div class="product-overlay">
                    <span class="btn btn-modern-view"><i class="fas fa-eye me-1"></i> Xem Nhanh</span>
                </div>
              </div>

              <div class="product-card-body text-center">
                <h5 class="card-title text-truncate" title="<?= e($p['product_name']) ?>">
                    <?= e($p['product_name']) ?>
                </h5>
                
                <p class="card-text product-short-desc text-muted">
                    <?= e(mb_substr($p['description'] ?? '', 0, 40)) ?>
                    <?= mb_strlen($p['description'] ?? '') > 40 ? '...' : '' ?>
                </p>
                
                <div class="price-info mb-3">
                  <span class="text-danger fw-bolder price-discounted">
                    <?= currency($discountedPrice) ?>
                  </span>
                  <?php if ($p['discount_percent'] > 0): ?>
                    <span class="text-muted text-decoration-line-through ms-2 price-original">
                      <?= currency($originalPrice) ?>
                    </span>
                  <?php endif; ?>
                </div>
                
                <span class="btn btn-modern-cta w-75 mt-2">
                    <i class="fas fa-shopping-cart me-2"></i> Chi Tiết
                </span>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
