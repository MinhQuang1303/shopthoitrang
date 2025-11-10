<?php
// Bắt đầu session và các hàm chung
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/ham_chung.php';

if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php')); // Sửa lại đường dẫn đăng nhập
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php'; // Giả định $db được định nghĩa ở đây

$errors = [];
$success = '';

// Khởi tạo CSRF Token nếu chưa có
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Lỗi bảo mật, vui lòng thử lại.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // 2. Kiểm tra dữ liệu đầu vào
        if (!$current_password || !$new_password || !$confirm_password) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Mật khẩu mới không khớp.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } else {
            // 3. Kiểm tra mật khẩu cũ
            $stmt = $db->prepare('SELECT password_hash FROM Users WHERE user_id = ?');
            $stmt->execute([$_SESSION['user']['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                $errors[] = 'Mật khẩu hiện tại không đúng.';
            } else {
                // 4. Cập nhật mật khẩu mới
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE Users SET password_hash = ? WHERE user_id = ?');
                $stmt->execute([$new_hash, $_SESSION['user']['user_id']]);

                $success = 'Đổi mật khẩu thành công! Bạn sẽ được chuyển hướng.';
                flash_set('success', $success);
                // Tạo CSRF mới sau khi thành công để ngăn chặn tấn công Double Submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
                header('Location: ' . base_url('trang_ca_nhan.php'));
                exit;
            }
        }
    }
}

// Bắt đầu tích hợp TIEU_DE.PHP (giả định đã không có banner)
$pdo = $db;
try {
    $stmt_cat = $pdo->query("SELECT category_id, category_name FROM Categories ORDER BY category_name ASC");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0; 
}

require_once __DIR__ . '/../user/tieu_de_k_banner.php';
// Nội dung của tieu_de.php đã được tích hợp ở trên (hoặc file đó được cấu hình chỉ có Header)
?>

<style>
    /* CSS cho Tieu De (Navbar, Logo, Buttons) */
    * { font-family: 'Montserrat', sans-serif; }
    body { background-color: #f8f9fa; }

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
    .cart-badge {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(50%, -50%);
        font-size: 0.8rem;
    }
    @media (max-width: 576px) {
        .navbar-brand video { display: none; }
        .navbar-brand { font-size: 1.3rem; }
    }
</style>
<div class="container mt-5" style="max-width: 400px;">
    <h2>Đổi mật khẩu</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err) echo e($err) . '<br>'; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
        <div class="mb-3">
            <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
            <input type="password" name="current_password" id="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="new_password" class="form-label">Mật khẩu mới</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>

        <button class="btn btn-primary" type="submit">Cập nhật</button>
    </form>
</div>

<footer class="bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container text-center text-md-start">
        <div class="row text-center text-md-start">
            
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 font-weight-bold" style="color: #FFD700;">
                    Shop Thời Trang Hiện Đại
                </h5>
                <p>
                    Cung cấp các sản phẩm thời trang phong cách, cá tính và đẳng cấp. Luôn cập nhật những xu hướng mới nhất.
                </p>
            </div>

            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 font-weight-bold" style="color: #FF6B6B;">Sản phẩm</h5>
                <p><a href="<?= base_url('san_pham.php?category_id=1') ?>" class="text-white" style="text-decoration: none;">Áo nam</a></p>
                <p><a href="<?= base_url('san_pham.php?category_id=2') ?>" class="text-white" style="text-decoration: none;">Váy nữ</a></p>
                <p><a href="<?= base_url('san_pham.php?category_id=3') ?>" class="text-white" style="text-decoration: none;">Phụ kiện</a></p>
                <p><a href="<?= base_url('san_pham.php') ?>" class="text-white" style="text-decoration: none;">Tất cả</a></p>
            </div>

            <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 font-weight-bold" style="color: #52B69A;">Liên kết</h5>
                <?php if (isLogged()): ?>
                    <p><a class="text-white" style="text-decoration: none;" href="<?= base_url('trang_ca_nhan.php') ?>">Tài khoản của tôi</a></p>
                    <p><a class="text-white" style="text-decoration: none;" href="<?= base_url('lich_su_mua_hang.php') ?>">Lịch sử đơn hàng</a></p>
                <?php else: ?>
                    <p><a class="text-white" style="text-decoration: none;" href="<?= base_url('auth/dang_nhap.php') ?>">Đăng nhập</a></p>
                <?php endif; ?>
                <p><a href="<?= base_url('chinh_sach.php') ?>" class="text-white" style="text-decoration: none;">Chính sách</a></p>
                <p><a href="<?= base_url('lien_he.php') ?>" class="text-white" style="text-decoration: none;">Liên hệ</a></p>
            </div>

            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 font-weight-bold" style="color: #FFD700;">Liên hệ</h5>
                <p><i class="bi bi-geo-alt-fill me-3"></i> Tầng 1, Tòa nhà Thời Trang, TP.HCM</p>
                <p><i class="bi bi-envelope-fill me-3"></i> support@shopthoitrang.vn</p>
                <p><i class="bi bi-telephone-fill me-3"></i> +84 901 234 567</p>
                <p><i class="bi bi-printer-fill me-3"></i> +84 901 234 568</p>
            </div>
        </div>

        <hr class="mb-4">

        <div class="row align-items-center">
            <div class="col-md-7 col-lg-8">
                <p class="text-center text-md-start">
                    © 2024 Bản quyền thuộc về: 
                    <a href="<?= base_url('index.php') ?>" style="text-decoration: none;">
                        <strong class="text-warning">ShopThoiTrang.vn</strong>
                    </a>
                </p>
            </div>
            
            <div class="col-md-5 col-lg-4">
                <div class="text-center text-md-end">
                    <ul class="list-unstyled list-inline">
                        <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i class="bi bi-facebook"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i class="bi bi-twitter"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i class="bi bi-google"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i class="bi bi-linkedin"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>