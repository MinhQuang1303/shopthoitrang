<?php
// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';

// Lấy danh mục sản phẩm từ DB
$categories = $pdo->query("SELECT * FROM Categories ORDER BY category_name ASC")->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Shop Thời Trang Hiện Đại</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- FONT + CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { font-family: 'Montserrat', sans-serif; }
        body { background-color: #f8f9fa; }

        /* Navbar hiện đại */
        .navbar-modern {
            background: linear-gradient(90deg, #141E30, #243B55);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            padding: 1rem 2rem;
        }
        .navbar-brand { 
            color: #FFD700 !important; 
            font-weight: 800; 
            font-size: 1.7rem; 
            display: flex; 
            align-items: center;
        }
        .navbar-brand video {
            height: 40px; 
            width: 40px; 
            border-radius: 5px; 
            object-fit: cover; 
            margin-right: 10px;
        }

        .navbar-nav .nav-link {
            color: #fff !important;
            font-weight: 600;
            margin: 0 10px;
            transition: 0.3s;
        }
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active { color: #FF6B6B !important; }

        /* Button CTA */
        .btn-cta {
            border-radius: 50px;
            padding: 6px 18px;
            font-weight: 700;
            transition: 0.3s;
            margin-left: 10px;
            text-decoration: none;
            color: white !important;
        }
        .btn-login { background-color: #FF6B6B; }
        .btn-login:hover { background-color: #e75454; transform: translateY(-2px); }
        .btn-cart { background-color: #52B69A; position: relative; }
        .btn-cart:hover { background-color: #409d85; transform: translateY(-2px); }

        .nav-link-user {
            color: #FFD700 !important;
            font-style: italic;
            font-weight: 700;
        }

        /* Banner */
        .banner-img { height: 420px; object-fit: cover; filter: brightness(75%); }
        .carousel-caption h1 { font-size: 3rem; font-weight: 800; text-shadow: 0 3px 12px rgba(0,0,0,0.8); }
        .carousel-caption p { font-size: 1.2rem; text-shadow: 0 2px 8px rgba(0,0,0,0.6); }

        /* Badge giỏ hàng */
        .cart-badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(50%, -50%);
            font-size: 0.8rem;
        }

        /* Responsive video logo */
        @media (max-width: 576px) {
            .navbar-brand video { display: none; }
            .navbar-brand { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<!-- ======= NAVBAR ======= -->
<nav class="navbar navbar-expand-lg navbar-modern">
    <div class="container-fluid">
        <!-- Logo + Video -->
        <a class="navbar-brand" href="<?= base_url('index.php') ?>">
            <video autoplay muted loop>
                <source src="<?= base_url('assets/images/san_pham/video.mp4') ?>" type="video/mp4">
                Trình duyệt không hỗ trợ video.
            </video>
            ShopThoiTrang
        </a>

        <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="<?= base_url('index.php') ?>">Trang chủ</a></li>

                <!-- Danh mục tự động -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Danh mục</a>
                    <ul class="dropdown-menu">
                        <?php foreach($categories as $c): ?>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('san_pham.php?category_id='.$c['category_id']) ?>">
                                    <?= e($c['category_name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <li class="nav-item"><a class="nav-link" href="<?= base_url('san_pham.php') ?>">Sản phẩm</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('su_kien.php') ?>">Sự kiện</a></li>
            </ul>

            <ul class="navbar-nav">
                <?php if (isLogged()): ?>
                    <li class="nav-item">
                        <a class="nav-link nav-link-user" href="<?= base_url('user/trang_ca_nhan.php') ?>">
                            Xin chào, <?= e($_SESSION['user']['full_name']) ?>
                        </a>
                    </li>

                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/indexadmin.php') ?>">Quản trị</a></li>
                    <?php endif; ?>

                    <li class="nav-item position-relative">
                        <a class="btn btn-cta btn-cart" href="<?= base_url('gio_hang.php') ?>">
                            🛒 Giỏ hàng
                            <?php if (!empty($_SESSION['cart_count'])): ?>
                                <span class="badge bg-danger rounded-pill cart-badge"><?= $_SESSION['cart_count'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item"><a class="btn btn-cta btn-login" href="<?= base_url('auth/dang_xuat.php') ?>">Đăng xuất</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-cta btn-login" href="<?= base_url('auth/dang_nhap.php') ?>">Đăng nhập</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>


<!-- LUXURY 3D BANNER 2025 - TÁC PHẨM NGHỆ THUẬT -->
<div class="luxury-banner-container">
    <div class="luxury-banner-scene" id="luxuryScene">
        
        <!-- 8 Layer 3D -->
        <div class="banner-layer depth-1"></div>
        <div class="banner-layer depth-2"></div>
        <div class="banner-layer depth-3"></div>
        <div class="banner-layer depth-4"></div>
        
        <!-- Particles kim cương + ánh sáng -->
        <div class="luxury-particles"></div>
        <div class="light-rays"></div>
        
        <!-- Nội dung chính -->
        <div class="luxury-content">
            <div class="brand-logo">
                <video autoplay muted loop playsinline class="logo-video">
                    <source src="<?= base_url('assets/images/san_pham/video.mp4') ?>" type="video/mp4">
                </video>
            </div>
            
            <h1 class="luxury-title-3d">
                <span class="line-1">ÉLITE</span>
                <span class="line-2">COUTURE</span>
                <span class="line-3">2025</span>
            </h1>
            
            <p class="luxury-tagline">
                <span>Where Luxury Meets Eternity</span>
            </p>
            
            <div class="luxury-cta-group">
                <a href="<?= base_url('san_pham.php') ?>" class="btn-luxury-primary">
                    <span>KHÁM PHÁ BỘ SƯU TẬP</span>
                    <div class="btn-glow"></div>
                </a>
                <a href="<?= base_url('su_kien.php') ?>" class="btn-luxury-secondary">
                    <span>XEM SHOW MỚI</span>
                </a>
            </div>
        </div>
        
        <!-- Navigation 3D -->
        <div class="banner-navigation">
            <button class="nav-circle prev" onclick="changeBanner(-1)">
                <i class="fas fa-angle-left"></i>
            </button>
            <button class="nav-circle next" onclick="changeBanner(1)">
                <i class="fas fa-angle-right"></i>
            </button>
        </div>
        
        <!-- Dots luxury -->
        <div class="banner-indicators">
            <div class="indicator active" data-slide="0"></div>
            <div class="indicator" data-slide="1"></div>
            <div class="indicator" data-slide="2"></div>
            <div class="indicator" data-slide="3"></div>
            <div class="indicator" data-slide="4"></div>
            <div class="indicator" data-slide="5"></div>
            <div class="indicator" data-slide="6"></div>
            <div class="indicator" data-slide="7"></div>
        </div>
    </div>
</div>

<style>
/* LUXURY BANNER 3D - ĐỈNH CAO 2025 */
.luxury-banner-container {
    height: 100vh;
    min-height: 650px;
    position: relative;
    overflow: hidden;
    background: #000;
    perspective: 2000px;
    font-family: 'Playfair Display', serif;
}

.luxury-banner-scene {
    width: 100%;
    height: 100%;
    position: relative;
    transform-style: preserve-3d;
    transition: transform 1s cubic-bezier(0.23, 1, 0.32, 1);
}

/* 8 Backgrounds 8K - tự động đổi */
.banner-layer {
    position: absolute;
    inset: 0;
    background: center/cover no-repeat;
    filter: brightness(0.7) contrast(1.3);
    transition: all 1.5s ease;
}

.depth-1 { background-image: url('https://images.unsplash.com/photo-1515886657613-9f3519b396c7?w=3200&q=95'); transform: translateZ(-400px) scale(1.4); }
.depth-2 { background-image: url('https://images.unsplash.com/photo-1523381210434-271e8be1f52e?w=3200&q=95'); transform: translateZ(-300px) scale(1.3); }
.depth-3 { background-image: url('https://images.unsplash.com/photo-1509631179647-0177331693ae?w=3200&q=95'); transform: translateZ(-200px) scale(1.2); }
.depth-4 { background-image: url('https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?w=3200'); transform: translateZ(-100px) scale(1.1); }

/* Particles kim cương rơi */
.luxury-particles {
    position: absolute;
    inset: 0;
    background: 
        radial-gradient(circle at 20% 30%, #FFD70033 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, #FFA50033 0%, transparent 50%),
        radial-gradient(circle at 50% 10%, #FF6B6B33 0%, transparent 50%);
    animation: float 25s infinite linear;
    pointer-events: none;
}

.light-rays {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 40%, rgba(255,215,0,0.1) 50%, transparent 60%);
    animation: rays 15s infinite linear;
    pointer-events: none;
}

@keyframes float { 0% { transform: translateY(0) rotate(0deg); } 100% { transform: translateY(-100px) rotate(10deg); } }
@keyframes rays { 0% { background-position: 0 0; } 100% { background-position: 1000px 1000px; } }

/* Logo video */
.brand-logo {
    position: absolute;
    top: 15%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
}

.logo-video {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 6px solid #FFD700;
    box-shadow: 0 0 80px #FFD700, inset 0 0 40px #FFA500;
    object-fit: cover;
}

/* Tiêu đề 3D */
.luxury-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) translateZ(200px);
    text-align: center;
    z-index: 30;
    width: 90%;
    max-width: 1400px;
}

.luxury-title-3d {
    margin: 0;
    line-height: 0.85;
    text-transform: uppercase;
}

.line-1 {
    font-size: 11vw;
    font-weight: 900;
    background: linear-gradient(135deg, #FFD700, #FFFFFF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 20px 40px rgba(0,0,0,0.8);
    letter-spacing: -5px;
    display: block;
    animation: glowText 4s infinite alternate;
}

.line-2 {
    font-size: 13vw;
    font-weight: 900;
    -webkit-text-stroke: 4px #FFD700;
    -webkit-text-fill-color: transparent;
    text-shadow: 
        0 0 40px #FFD700,
        0 0 80px #FFA500,
        0 0 120px #FF6B6B;
    letter-spacing: -8px;
    display: block;
    animation: strokePulse 3s infinite;
}

.line-3 {
    font-size: 8vw;
    color: #FFD700;
    font-weight: 700;
    letter-spacing: 10px;
    text-shadow: 0 0 60px #FFD700;
    animation: yearFloat 4s infinite;
}

/* Tagline */
.luxury-tagline {
    font-size: 2.2vw;
    color: #fff;
    font-weight: 600;
    letter-spacing: 8px;
    margin: 40px 0;
    font-style: italic;
    opacity: 0.9;
}

/* Nút CTA */
.luxury-cta-group {
    display: flex;
    gap: 30px;
    justify-content: center;
    margin-top: 60px;
    flex-wrap: wrap;
}

.btn-luxury-primary {
    position: relative;
    padding: 28px 90px;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000;
    font-weight: 900;
    font-size: 1.8vw;
    text-transform: uppercase;
    letter-spacing: 6px;
    border-radius: 60px;
    text-decoration: none;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(255,215,0,0.6);
    transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
}

.btn-luxury-primary:hover {
    transform: translateY(-20px) scale(1.08);
    box-shadow: 0 50px 120px rgba(255,215,0,0.8);
}

.btn-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.8), transparent 70%);
    animation: pulseGlow 3s infinite;
    pointer-events: none;
}

.btn-luxury-secondary {
    padding: 25px 70px;
    background: transparent;
    color: #FFD700;
    border: 3px solid #FFD700;
    font-weight: 700;
    font-size: 1.6vw;
    letter-spacing: 5px;
    border-radius: 60px;
    text-decoration: none;
    transition: all 0.5s ease;
}

.btn-luxury-secondary:hover {
    background: #FFD700;
    color: #000;
    transform: translateY(-10px);
}

/* Navigation */
.banner-navigation {
    position: absolute;
    bottom: 8%;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 30px;
    z-index: 40;
}

.nav-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,215,0,0.15);
    border: 3px solid #FFD700;
    color: #FFD700;
    font-size: 2rem;
    backdrop-filter: blur(10px);
    transition: all 0.4s ease;
    box-shadow: 0 15px 40px rgba(0,0,0,0.4);
}

.nav-circle:hover {
    background: #FFD700;
    color: #000;
    transform: scale(1.2);
}

/* Indicators */
.banner-indicators {
    position: absolute;
    bottom: 5%;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 15px;
    z-index: 40;
}

.indicator {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    border: 2px solid #FFD700;
    cursor: pointer;
    transition: all 0.5s ease;
}

.indicator.active {
    background: #FFD700;
    transform: scale(1.5);
    box-shadow: 0 0 30px #FFD700;
}

/* Animations */
@keyframes glowText { 
    0% { text-shadow: 0 20px 40px rgba(0,0,0,0.8); }
    100% { text-shadow: 0 20px 40px rgba(0,0,0,0.8), 0 0 80px #FFD700; }
}

@keyframes strokePulse { 
    0%, 100% { text-shadow: 0 0 40px #FFD700; }
    50% { text-shadow: 0 0 120px #FFA500; }
}

@keyframes yearFloat { 
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

@keyframes pulseGlow { 
    0%, 100% { opacity: 0; transform: scale(0.8); }
    50% { opacity: 1; transform: scale(1.2); }
}

/* Responsive */
@media (max-width: 992px) {
    .line-1 { font-size: 14vw; }
    .line-2 { font-size: 16vw; }
    .line-3 { font-size: 10vw; }
    .luxury-tagline { font-size: 3.5vw; }
    .btn-luxury-primary { padding: 20px 60px; font-size: 3vw; }
    .btn-luxury-secondary { padding: 18px 50px; font-size: 2.8vw; }
}

@media (max-width: 576px) {
    .logo-video { width: 80px; height: 80px; }
    .luxury-cta-group { flex-direction: column; align-items: center; }
    .btn-luxury-primary, .btn-luxury-secondary { width: 80%; }
    .nav-circle { width: 60px; height: 60px; font-size: 1.5rem; }
}
</style>

<script>
// 8 ảnh + video background
const backgrounds = [
    'https://images.unsplash.com/photo-1515886657613-9f3519b396c7?w=3200&q=95',
    'https://images.unsplash.com/photo-1523381210434-271e8be1f52e?w=3200&q=95',
    'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=3200&q=95',
    'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?w=3200',
    'https://images.unsplash.com/photo-1445205170230-053b83016050?w=3200&q=95',
    'https://images.pexels.com/photos/1571459/pexels-photo-1571459.jpeg?w=3200',
    'https://images.unsplash.com/photo-1490481651871-ab68de7d43df?w=3200&q=95',
    'https://images.pexels.com/photos/1571468/pexels-photo-1571468.jpeg?w=3200'
];

let currentIndex = 0;
const layers = document.querySelectorAll('.banner-layer');

function changeBanner(direction) {
    currentIndex = (currentIndex + direction + backgrounds.length) % backgrounds.length;
    
    layers.forEach((layer, i) => {
        setTimeout(() => {
            layer.style.backgroundImage = `url(${backgrounds[(currentIndex + i) % backgrounds.length]})`;
        }, i * 200);
    });
    
    updateIndicators();
}

function updateIndicators() {
    document.querySelectorAll('.indicator').forEach((dot, i) => {
        dot.classList.toggle('active', i === currentIndex);
    });
}

// Auto change every 10s
setInterval(() => changeBanner(1), 10000);

// Mouse 3D effect
document.getElementById('luxuryScene').addEventListener('mousemove', (e) => {
    const x = (e.clientX / window.innerWidth - 0.5) * 30;
    const y = (e.clientY / window.innerHeight - 0.5) * 30;
    document.getElementById('luxuryScene').style.transform = `rotateY(${x}deg) rotateX(${-y}deg)`;
});

document.getElementById('luxuryScene').addEventListener('mouseleave', () => {
    document.getElementById('luxuryScene').style.transform = 'rotateY(0) rotateX(0)';
});

// Click indicator
document.querySelectorAll('.indicator').forEach((dot, i) => {
    dot.addEventListener('click', () => {
        currentIndex = i;
        layers.forEach((layer, j) => {
            layer.style.backgroundImage = `url(${backgrounds[(i + j) % backgrounds.length]})`;
        });
        updateIndicators();
    });
});
</script>


<!-- Load Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
