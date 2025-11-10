<?php
// ==============================
// File: auth/dang_ky_otp.php
// ==============================

require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/class_otp.php';

// Đảm bảo session chỉ khởi tạo 1 lần
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa có thông tin đăng ký tạm thời → quay về đăng ký
if (!isset($_SESSION['pending_register'])) {
    header('Location: dang_ky.php');
    exit;
}

$msg = '';
$info = $_SESSION['pending_register'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = trim($_POST['otp']);
    $otp = new OTP($pdo);

    // Kiểm tra định dạng OTP (phải là 6 số)
    if (!preg_match('/^\d{6}$/', $otp_code)) {
        $msg = 'Mã OTP phải gồm 6 chữ số.';
    } else {
        // Gọi hàm verify trong class_otp
        if ($otp->verify($info['email'], 'register', $otp_code)) {

            // ✅ 1. Lưu tài khoản mới
            $stmt = $pdo->prepare("INSERT INTO Users (email, full_name, password) VALUES (?, ?, ?)");
            $stmt->execute([$info['email'], $info['full_name'], $info['password']]);

            // ✅ 2. Xóa OTP + xóa session tạm
            $otp->delete($info['email'], 'register');
            unset($_SESSION['pending_register']);

            // ✅ 3. Gửi thông báo flash
            $_SESSION['flash'] = '🎉 Đăng ký thành công! Hãy đăng nhập.';
            header('Location: dang_nhap.php');
            exit;
        } else {
            $msg = '❌ Mã OTP không hợp lệ hoặc đã hết hạn.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Xác nhận OTP | Shop Thời Trang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #141E30, #243B55);
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .otp-box {
            background: #fff;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 350px;
            text-align: center;
        }
        h3 {
            margin-bottom: 20px;
            color: #141E30;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            text-align: center;
            border-radius: 5px;
            border: 1px solid #aaa;
            margin-bottom: 15px;
        }
        button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background: #52B69A;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #409d85;
        }
        a {
            text-decoration: none;
            color: #FF6B6B;
        }
    </style>
</head>
<body>
    <div class="otp-box">
        <h3>Xác nhận mã OTP</h3>
        <p>Chúng tôi đã gửi mã 6 số đến email: <b><?= e($info['email']) ?></b></p>

        <?php if ($msg): ?>
            <p style="color:red; font-weight:bold;"><?= e($msg) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="otp" maxlength="6" placeholder="Nhập mã OTP..." required>
            <button type="submit">Xác nhận</button>
        </form>

        <p><a href="dang_ky.php">← Quay lại đăng ký</a></p>
    </div>
</body>
</html>
