<?php
// auth/dang_ky.php

require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php'; 
require_once __DIR__ . '/../includes/class_otp.php'; // Đảm bảo class_otp.php tồn tại
// KHẮC PHỤC LỖI SESSION START (Đảm bảo chỉ gọi 1 lần)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$errors = [];

// Giá trị cũ để giữ lại trong form nếu có lỗi
$email_value = '';
$full_name_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $repassword = $_POST['repassword'];

    // Giữ lại giá trị
    $email_value = $email;
    $full_name_value = $full_name;

    // Kiểm tra dữ liệu
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (strlen($full_name) < 3) $errors[] = 'Họ và tên phải có ít nhất 3 ký tự.';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    if ($password !== $repassword) $errors[] = 'Mật khẩu nhập lại không khớp.';

    // Kiểm tra email đã tồn tại
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email này đã được đăng ký.';
    }

    if (empty($errors)) {
        // Lưu thông tin đăng ký vào session chờ xác thực OTP
        $_SESSION['pending_register'] = [
            'email' => $email,
            'full_name' => $full_name,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        // Tạo và gửi OTP (Giả định class OTP đã được cấu hình gửi email)
        $otp = new OTP($pdo);
        $result = $otp->create($email, 'register', 6, 10); // 6 số, hết hạn 10 phút

        if ($result) {
            $_SESSION['flash']['success'] = 'Mã OTP đã được gửi tới email của bạn. Vui lòng kiểm tra hộp thư.';
            header('Location: ' . base_url('dang_ky_otp.php'));
            exit;
        } else {
            $msg = 'Không gửi được email OTP. Vui lòng kiểm tra lại địa chỉ email hoặc thử lại sau.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Đăng ký tài khoản | Shop Thời Trang</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #141E30, #243B55);
            min-height: 100vh; 
            display: flex;
            align-items: center; 
            justify-content: center; 
        }
        .register-container {
            width: 100%;
            max-width: 450px; /* Hơi rộng hơn form đăng nhập */
            padding: 40px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.5s ease-out; 
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .register-title {
            font-weight: 800;
            color: #141E30;
            margin-bottom: 30px;
            text-align: center;
        }
        .btn-submit {
            background-color: #52B69A; /* Màu xanh nổi bật cho Đăng ký */
            border: none;
            font-weight: 700;
            transition: 0.3s;
        }
        .btn-submit:hover {
            background-color: #409d85;
            transform: translateY(-1px);
        }
        .login-link {
            color: #FF6B6B; /* Màu "Pong Pong" */
            font-weight: 600;
            text-decoration: none;
        }
        .login-link:hover {
            color: #e75454;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <h3 class="register-title">Đăng ký tài khoản</h3>

        <?php if ($msg): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-warning p-3 mb-3" role="alert">
                <p class="fw-bold mb-1">⚠️ Vui lòng kiểm tra lại:</p>
                <ul class="mb-0">
                    <?php foreach ($errors as $er): ?>
                        <li><?= e($er) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="dang_ky.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= e($email_value) ?>" required 
                       placeholder="example@email.com">
            </div>
            
            <div class="mb-3">
                <label for="full_name" class="form-label">Họ và tên:</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       value="<?= e($full_name_value) ?>" required 
                       placeholder="Nguyễn Văn A">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu:</label>
                <input type="password" id="password" name="password" class="form-control" required 
                       placeholder="Mật khẩu (ít nhất 6 ký tự)">
            </div>
            
            <div class="mb-4">
                <label for="repassword" class="form-label">Nhập lại mật khẩu:</label>
                <input type="password" id="repassword" name="repassword" class="form-control" required 
                       placeholder="Nhập lại mật khẩu">
            </div>
            
            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-submit btn-lg text-white">
                    Gửi mã OTP & Đăng ký
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="mb-1">Đã có tài khoản?</p>
            <a href="<?= base_url('dang_nhap.php') ?>" class="login-link">
                **Đăng nhập ngay**
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>