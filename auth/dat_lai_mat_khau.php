<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/class_otp.php';
require_once __DIR__ . '/../includes/ham_chung.php';
session_start();

if (empty($_SESSION['reset_email'])) {
    header('Location: quen_mat_khau.php');
    exit;
}

$msg = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = trim($_POST['otp']);
    $new_pass = trim($_POST['password']);
    $otp = new OTP($pdo);

    if ($otp->verify($email, $otp_code, 'forgot_password')) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE Users SET password=? WHERE email=?")->execute([$hash, $email]);
        unset($_SESSION['reset_email']);
        $msg = 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập lại.';
    } else {
        $msg = 'Mã OTP không hợp lệ hoặc đã hết hạn.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><title>Đặt lại mật khẩu</title></head>
<body>
<h3>Đặt lại mật khẩu</h3>
<?php if ($msg): ?><p><?= e($msg) ?></p><?php endif; ?>
<form method="post">
    <label>Nhập mã OTP:</label><br>
    <input type="text" name="otp" required><br>
    <label>Mật khẩu mới:</label><br>
    <input type="password" name="password" required><br><br>
    <button type="submit">Xác nhận</button>
</form>
</body>
</html>
