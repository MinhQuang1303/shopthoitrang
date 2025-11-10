<?php
// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/ham_chung.php';

// Kiểm tra đăng nhập
if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php';
$user = $_SESSION['user'];

// Thống kê đơn hàng
$stmt = $db->prepare('SELECT 
    COUNT(*) AS total_orders,
    COUNT(CASE WHEN status = "completed" THEN 1 END) AS completed_orders,
    COUNT(CASE WHEN status IN ("pending", "confirmed", "shipping") THEN 1 END) AS active_orders
    FROM Orders
    WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Danh sách yêu thích
$stmt = $db->prepare('SELECT COUNT(*) FROM Wishlist WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$wishlist_count = $stmt->fetchColumn();

// Thông báo chưa đọc
$stmt = $db->prepare('SELECT COUNT(*) FROM Notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user['user_id']]);
$unread_notifications = $stmt->fetchColumn();

// Lấy danh mục sản phẩm (cho menu)
try {
    $pdo = $db;
    $stmt = $pdo->query("SELECT category_id, category_name FROM Categories ORDER BY category_name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Nếu chưa có session giỏ hàng
if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}
?>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Trang cá nhân - Shop Thời Trang Hiện Đại</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* --- RESET & BASE --- */
        * {
            font-family: 'Montserrat', sans-serif;
        }
        body {
            background-color: #f0f4f8;
            color: #2c3e50;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* --- NAVBAR --- */
        .navbar-modern {
            background: linear-gradient(90deg, #0f2027, #203a43, #2c5364);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
            padding: 1rem 2rem;
        }
        .navbar-brand {
            color: #FFD700 !important;
            font-weight: 900;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
        }
        .navbar-brand video {
            height: 45px;
            width: 45px;
            border-radius: 8px;
            object-fit: cover;
        }
        .navbar-nav .nav-link {
            color: #ecf0f1 !important;
            font-weight: 600;
            margin: 0 12px;
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: #ff6b6b !important;
        }
        .nav-link-user {
            color: #FFD700 !important;
            font-style: italic;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        /* --- BUTTONS --- */
        .btn-cta {
            border-radius: 50px;
            padding: 8px 22px;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-left: 12px;
            color: white !important;
            text-decoration: none;
            box-shadow: 0 3px 7px rgba(0, 0, 0, 0.15);
        }
        .btn-login {
            background-color: #ff6b6b;
            box-shadow: 0 5px 10px #ff6b6baa;
        }
        .btn-login:hover {
            background-color: #e75555;
            transform: translateY(-3px);
            box-shadow: 0 8px 15px #e7555588;
        }
        .btn-cart {
            background-color: #52b69a;
            position: relative;
            box-shadow: 0 5px 10px #52b69aaa;
        }
        .btn-cart:hover {
            background-color: #409d85;
            transform: translateY(-3px);
            box-shadow: 0 8px 15px #409d8588;
        }
        .cart-badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(50%, -50%);
            font-size: 0.85rem;
            background-color: #ff4d6d;
            color: white;
            padding: 3px 7px;
            border-radius: 50%;
            font-weight: 700;
            box-shadow: 0 0 5px #ff4d6daa;
        }

        /* --- DASHBOARD CARDS --- */
        .dashboard-card {
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: default;
            background-color: white;
        }
        .dashboard-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }
        .dashboard-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }
        .card-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .display-6 {
            font-weight: 800;
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: inherit;
        }
        .btn-sm {
            font-weight: 600;
            border-radius: 30px;
            padding: 6px 18px;
        }

        /* --- PERSONAL INFO CARD --- */
        .card-header {
            background-color: #fff;
            font-weight: 700;
            font-size: 1.3rem;
            color: #34495e;
            border-bottom: 2px solid #ddd;
        }
        .card-body p {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 10px;
        }

        /* --- BUTTON GROUP UNDER PERSONAL INFO --- */
        .btn-group-custom {
            margin-bottom: 3rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 576px) {
            .navbar-brand video {
                display: none;
            }
            .navbar-brand {
                font-size: 1.4rem;
            }
            .dashboard-icon {
                font-size: 2.2rem;
            }
            .display-6 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-modern sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= base_url('index.php') ?>">
            <video autoplay muted loop playsinline>
                <source src="assets/images/san_pham/logo_video.mp4" type="video/mp4">
            </video>
            ShopThoiTrang
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php') ?>">Trang chủ</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Danh mục</a>
                    <ul class="dropdown-menu">
                        <?php foreach ($categories as $c): ?>
                            <li><a class="dropdown-item" href="<?= base_url('san_pham.php?category_id='.$c['category_id']) ?>"><?= e($c['category_name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('san_pham.php') ?>">Sản phẩm</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('su_kien.php') ?>">Sự kiện</a></li>
            </ul>

            <ul class="navbar-nav align-items-center">
                <?php if (isLogged()): ?>
                    <li class="nav-item me-3">
                        <a class="nav-link nav-link-user" href="<?= base_url('user/trang_ca_nhan.php') ?>">
                            Xin chào, <?= e($user['full_name'] ?? 'Khách hàng') ?>
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item me-3"><a class="nav-link" href="<?= base_url('admin/indexadmin.php') ?>">Quản trị</a></li>
                    <?php endif; ?>

                    <li class="nav-item position-relative me-3">
                        <a class="btn btn-cta btn-cart" href="<?= base_url('gio_hang.php') ?>">🛒 Giỏ hàng
                            <?php if (!empty($_SESSION['cart_count'])): ?>
                                <span class="cart-badge"><?= $_SESSION['cart_count'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-cta btn-login" href="<?= base_url('auth/dang_xuat.php') ?>">Đăng xuất</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-cta btn-login" href="<?= base_url('auth/dang_nhap.php') ?>">Đăng nhập</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== MAIN CONTENT ===== -->
<div class="container mt-5 mb-5">

    <h2 class="mb-4 fw-bold text-primary">Trang cá nhân</h2>

    <!-- Thông tin cá nhân -->
    <div class="card mb-5 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Thông tin cá nhân</h4>
        </div>
        <div class="card-body">
            <p><strong>Họ tên:</strong> <?= e($user['full_name']) ?></p>
            <p><strong>Email:</strong> <?= e($user['email']) ?></p>
            <p><strong>Số điện thoại:</strong> <?= e($user['phone'] ?? '-') ?></p>
            <p><strong>Địa chỉ:</strong> <?= nl2br(e($user['address'] ?? '-')) ?></p>
        </div>
    </div>

    <!-- Các nút thao tác -->
    <div class="d-flex btn-group-custom flex-wrap">
        <a href="<?= base_url('user/thong_tin_ca_nhan.php') ?>" class="btn btn-primary shadow-sm"><i class="bi bi-person-lines-fill me-2"></i> Chỉnh sửa trang cá nhân</a>
        <a href="<?= base_url('user/doi_mat_khau.php') ?>" class="btn btn-warning shadow-sm"><i class="bi bi-key-fill me-2"></i> Đổi mật khẩu</a>
        <a href="<?= base_url('auth/dang_xuat.php') ?>" class="btn btn-danger shadow-sm"><i class="bi bi-box-arrow-right me-2"></i> Đăng xuất</a>
    </div>

    <!-- Thống kê và thông tin quan trọng -->
    <div class="row g-4 mt-4">
        <div class="col-md-3 col-sm-6">
            <div class="card dashboard-card border-primary text-primary">
                <div class="card-body text-center">
                    <div class="dashboard-icon"><i class="bi bi-bag-check-fill"></i></div>
                    <h5 class="card-title">Đơn hàng đã hoàn thành</h5>
                    <p class="display-6"><?= number_format($order_stats['completed_orders']) ?></p>
                    <a href="<?= base_url('user/lich_su_mua_hang.php') ?>" class="btn btn-primary btn-sm shadow-sm">Xem chi tiết</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card dashboard-card border-warning text-warning">
                <div class="card-body text-center">
                    <div class="dashboard-icon"><i class="bi bi-hourglass-split"></i></div>
                    <h5 class="card-title">Đơn hàng đang xử lý</h5>
                    <p class="display-6"><?= number_format($order_stats['active_orders']) ?></p>
                    <a href="<?= base_url('user/lich_su_mua_hang.php') ?>" class="btn btn-warning btn-sm shadow-sm">Xem chi tiết</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card dashboard-card border-success text-success">
                <div class="card-body text-center">
                    <div class="dashboard-icon"><i class="bi bi-heart-fill"></i></div>
                    <h5 class="card-title">Danh sách yêu thích</h5>
                    <p class="display-6"><?= number_format($wishlist_count) ?></p>
                    <a href="<?= base_url('user/danh_sach_yeu_thich.php') ?>" class="btn btn-success btn-sm shadow-sm">Xem chi tiết</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card dashboard-card border-info text-info">
                <div class="card-body text-center">
                    <div class="dashboard-icon"><i class="bi bi-bell-fill"></i></div>
                    <h5 class="card-title">Thông báo mới</h5>
                    <p class="display-6"><?= number_format($unread_notifications) ?></p>
                    <a href="<?= base_url('user/thong_bao.php') ?>" class="btn btn-info btn-sm shadow-sm">Xem chi tiết</a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
