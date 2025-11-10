<?php
// san_pham.php
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de.php';

// Lấy danh mục (nếu có)
$cat = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$params = [];
$sql = "SELECT * FROM Products";
if ($cat) {
  $sql .= " WHERE category_id = ?";
  $params[] = $cat;
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
  <h2 class="text-center fw-bold mb-5">Danh sách sản phẩm</h2>

  <?php if (empty($products)): ?>
    <div class="alert alert-warning text-center rounded-4 shadow-sm">
      Hiện chưa có sản phẩm nào trong danh mục này.
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($products as $p): ?>
        <?php
        // Lấy ảnh chính
        $mainImage = $p['thumbnail_url'] ?? null;
        if (!$mainImage) {
            $stmtImg = $pdo->prepare("SELECT image_url FROM Product_Images WHERE product_id = ? ORDER BY image_id ASC LIMIT 1");
            $stmtImg->execute([$p['product_id']]);
            $mainImage = $stmtImg->fetchColumn();
        }
        if (!$mainImage) $mainImage = 'no-image.jpg';
        $imagePath = base_url('assets/images/san_pham/' . ltrim($mainImage, '/'));
        ?>
        <div class="col-6 col-md-3">
          <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($p['product_id'])) ?>" class="text-decoration-none">
            <div class="product-card-modern">
              <img src="<?= $imagePath ?>" class="card-img-top" alt="<?= e($p['product_name']) ?>">
              <div class="product-card-body text-center">
                <h5 class="card-title"><?= e($p['product_name']) ?></h5>
                <p class="card-text text-muted">
                  <?= e(mb_substr($p['description'], 0, 50)) ?>
                  <?= mb_strlen($p['description'] ?? '') > 50 ? '...' : '' ?>
                </p>
                <div class="mb-2">
                  <span class="text-danger fw-bold">
                    <?= currency($p['base_price'] * (1 - $p['discount_percent'] / 100)) ?>
                  </span>
                  <?php if ($p['discount_percent'] > 0): ?>
                    <span class="text-muted text-decoration-line-through ms-2">
                      <?= currency($p['base_price']) ?>
                    </span>
                    <span class="badge bg-success ms-1">-<?= e($p['discount_percent']) ?>%</span>
                  <?php endif; ?>
                </div>
                <span class="btn btn-modern">Xem Chi Tiết</span>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<style>
.product-card-modern {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    background-color: #fff;
    min-height: 300px;
}
.product-card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(255,107,107,0.3), 0 0 15px rgba(255,107,107,0.1);
}
.product-card-modern img {
    height: 180px;
    object-fit: cover;
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
    transition: transform 0.5s ease;
}
.product-card-modern:hover img { transform: scale(1.05); }
.product-card-body { padding: 15px; flex-grow: 1; }
.product-card-body h5 { color: #333; font-weight: 700; margin-bottom: 5px; font-size: 1rem; }
.product-card-body p { color: #666; font-size: 0.8rem; margin-bottom: 10px; }
.btn-modern {
    background-color: #FF6B6B;
    border: none;
    padding: 7px 20px;
    border-radius: 50px;
    font-weight: 600;
    transition: background-color 0.3s ease;
    color: #fff;
    font-size: 0.85rem;
}
.btn-modern:hover { background-color: #EE5253; }
</style>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
