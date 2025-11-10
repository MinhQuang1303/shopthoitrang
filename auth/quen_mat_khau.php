<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/class_otp.php';
session_start();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT full_name FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $msg = 'Email không tồn tại.';
    } else {
        $otp = new OTP($pdo);
        $sent = $otp->create($email, 'forgot_password');
        $_SESSION['reset_email'] = $email;
        $msg = $sent ? 'Mã OTP đã gửi vào email của bạn.' : 'Không gửi được mail.';
        header('Location: dat_lai_mat_khau.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><title>Quên mật khẩu</title></head>
<body>
<h3>Quên mật khẩu</h3>
<?php if ($msg): ?><p><?= e($msg) ?></p><?php endif; ?>
<form method="post">
    <label>Email:</label>
    <input type="email" name="email" required>
    <button type="submit">Gửi mã OTP</button>
</form>
</body>
</html>
