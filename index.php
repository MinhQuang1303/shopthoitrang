<?php
// index.php — Trang chủ hiển thị sản phẩm nổi bật (CHỮ ĐẸP NHƯ CAO CẤP 2025)

require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de.php';

// Lấy 6 sản phẩm nổi bật
$stmt = $pdo->query("SELECT * FROM Products WHERE is_hot = 1 ORDER BY created_at DESC LIMIT 6");
$hots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- GOOGLE FONTS CAO CẤP NHẤT 2025 -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">

<!-- ===================== SẢN PHẨM NỔI BẬT - CHỮ VIP ===================== -->
<section class="vip-hot-products py-5" style="background: linear-gradient(135deg, #fdfbfb 0%, #f8f5ff 100%); overflow: hidden;">
    <div class="container px-4">
        <!-- Tiêu đề siêu sang -->
        <div class="text-center mb-5">
            <p class="mini-title">🔥 ĐANG HOT NHẤT</p>
            <h2 class="main-title">SẢN PHẨM NỔI BẬT</h2>
            <p class="subtitle">Những tuyệt phẩm được săn đón nhiều nhất tuần này</p>
            <div class="title-line"></div>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($hots as $index => $p): 
                $mainImage = $p['thumbnail_url'] ?? null;
                if (!$mainImage) {
                    $stmtImg = $pdo->prepare("SELECT image_url FROM Product_Images WHERE product_id = ? LIMIT 1");
                    $stmtImg->execute([$p['product_id']]);
                    $mainImage = $stmtImg->fetchColumn();
                }
                $mainImage = $mainImage ?: 'no-image.jpg';
                $imagePath = base_url('assets/images/san_pham/' . ltrim($mainImage, '/'));
                
                $finalPrice = $p['base_price'] * (1 - $p['discount_percent'] / 100);
                $saved = $p['base_price'] - $finalPrice;
            ?>
                <div class="col-lg-4 col-md-6">
                    <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($p['product_id'])) ?>" class="text-decoration-none">
                        <div class="vip-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                            <!-- Badge HOT + Sale -->
                            <div class="badges">
                                <span class="badge badge-fire">HOT</span>
                                <?php if ($p['discount_percent'] > 0): ?>
                                    <span class="badge badge-sale">-<?= e($p['discount_percent']) ?>%</span>
                                <?php endif; ?>
                            </div>

                            <!-- Ảnh -->
                            <div class="image-container">
                                <img src="<?= $imagePath ?>" alt="<?= e($p['product_name']) ?>" class="product-image">
                                <div class="image-overlay"></div>
                            </div>

                            <!-- Nội dung -->
                            <div class="content">
                                <h3 class="product-name"><?= e($p['product_name']) ?></h3>
                                
                                <p class="product-desc">
                                    <?= e(mb_substr(strip_tags($p['description']), 0, 85)) ?>
                                    <?= mb_strlen(strip_tags($p['description'] ?? '')) > 85 ? '...' : '' ?>
                                </p>

                                <!-- Giá VIP -->
                                <div class="price-section">
                                    <div class="price-main"><?= currency($finalPrice) ?></div>
                                    <?php if ($p['discount_percent'] > 0): ?>
                                        <div class="price-old"><?= currency($p['base_price']) ?></div>
                                        <div class="price-save">Tiết kiệm <?= currency($saved) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Rating -->
                                <div class="rating">
                                    ★★★★☆ <span class="rating-count">(<?= rand(128, 892) ?>)</span>
                                </div>

                                <!-- Nút CTA -->
                                <button class="btn-vip">
                                    <span>Xem Chi Tiết</span>
                                    <svg class="arrow" viewBox="0 0 24 24">
                                        <path d="M8 4l8 8-8 8" stroke="currentColor" stroke-width="2.5" fill="none"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Xem thêm -->
        <div class="text-center mt-5">
            <a href="<?= base_url('san_pham.php') ?>" class="btn-more">
                Xem Tất Cả Sản Phẩm
                <span class="arrow-right">→</span>
            </a>
        </div>
    </div>
</section>

<!-- ===================== CSS CHỮ + CARD VIP ===================== -->
<style>
/* Font cao cấp */
.vip-hot-products * {
    font-family: 'Inter', sans-serif !important;
}

/* Tiêu đề */
.mini-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #FF3B30;
    letter-spacing: 4px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.main-title {
    font-family: 'Playfair Display', serif !important;
    font-size: 3.2rem;
    font-weight: 900;
    color: #1a1a1a;
    letter-spacing: -1.5px;
    margin: 0;
}
.subtitle {
    font-size: 1.15rem;
    color: #666;
    max-width: 600px;
    margin: 12px auto 0;
}
.title-line {
    width: 120px;
    height: 5px;
    background: linear-gradient(90deg, #FF6B6B, #FF8E8E);
    border-radius: 3px;
    margin: 20px auto;
    position: relative;
}
.title-line::after {
    content: '';
    position: absolute;
    width: 50px;
    height: 5px;
    background: #FF3B30;
    left: 35px;
    top: -10px;
    border-radius: 3px;
}

/* Card VIP */
.vip-card {
    background: white;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    border: 1px solid rgba(255,107,107,0.1);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.vip-card:hover {
    transform: translateY(-20px) scale(1.03);
    box-shadow: 0 30px 80px rgba(255,107,107,0.25);
    border-color: rgba(255,107,107,0.4);
}

/* Badge */
.badges {
    position: absolute;
    top: 18px;
    left: 18px;
    z-index: 10;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.badge {
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 800;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.badge-fire {
    background: linear-gradient(135deg, #FF3B30, #FF6B6B);
    box-shadow: 0 4px 15px rgba(255,59,48,0.5);
}
.badge-sale {
    background: linear-gradient(135deg, #FF9500, #FFB800);
    box-shadow: 0 4px 15px rgba(255,149,0,0.5);
}

/* Ảnh */
.image-container {
    position: relative;
    height: 300px;
    overflow: hidden;
    background: #f8f8f8;
}
.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.8s ease;
}
.vip-card:hover .product-image {
    transform: scale(1.15);
}
.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.7) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}
.vip-card:hover .image-overlay {
    opacity: 1;
}

/* Nội dung */
.content {
    padding: 28px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.product-name {
    font-size: 1.4rem;
    font-weight: 800;
    color: #1a1a1a;
    margin: 0 0 12px 0;
    line-height: 1.3;
}
.product-desc {
    font-size: 1rem;
    color: #666;
    line-height: 1.6;
    margin-bottom: 16px;
    flex-grow: 1;
}

/* Giá */
.price-section {
    margin: 16px 0;
}
.price-main {
    font-size: 1.8rem;
    font-weight: 900;
    color: #FF3B30;
    letter-spacing: -1px;
}
.price-old {
    font-size: 1.1rem;
    color: #999;
    text-decoration: line-through;
    margin-left: 10px;
}
.price-save {
    font-size: 0.95rem;
    color: #28a745;
    font-weight: 700;
    margin-top: 6px;
}

/* Rating */
.rating {
    color: #FFC107;
    font-size: 1.1rem;
    margin-bottom: 16px;
}
.rating-count {
    font-size: 0.9rem;
    color: #888;
    margin-left: 6px;
}

/* Nút */
.btn-vip {
    background: linear-gradient(135deg, #FF6B6B, #FF4757);
    color: white;
    border: none;
    padding: 16px 32px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 10px 30px rgba(255,107,107,0.4);
}
.btn-vip:hover {
    background: linear-gradient(135deg, #EE5253, #FF3742);
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(255,107,107,0.5);
}
.arrow {
    width: 20px;
    transition: transform 0.3s ease;
}
.btn-vip:hover .arrow {
    transform: translateX(6px);
}

/* Xem thêm */
.btn-more {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 40px;
    background: white;
    border: 3px solid #FF6B6B;
    border-radius: 50px;
    color: #FF6B6B;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-size: 1rem;
    transition: all 0.4s ease;
    box-shadow: 0 10px 30px rgba(255,107,107,0.2);
}
.btn-more:hover {
    background: #FF6B6B;
    color: white;
    transform: translateY(-5px);
    box-shadow: 0 20px 50px rgba(255,107,107,0.4);
}
.arrow-right {
    font-size: 1.8rem;
    transition: transform 0.3s ease;
}
.btn-more:hover .arrow-right {
    transform: translateX(10px);
}
</style>

<!-- Hiệu ứng khi scroll (tùy chọn) -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script>
    AOS.init({ duration: 800, once: true });
</script>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>