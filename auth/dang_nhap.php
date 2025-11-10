<?php
// auth/dang_nhap.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header('Location: ../index.php');
        exit;
    } else {
        $msg = 'Email hoặc mật khẩu không đúng.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đăng nhập | Shop Thời Trang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font + Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { font-family: 'Montserrat', sans-serif; }
        body {
            background: linear-gradient(135deg, #141E30, #243B55);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 420px;
            text-align: center;
            animation: fadeIn 0.7s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card h3 {
            font-weight: 800;
            color: #243B55;
            margin-bottom: 25px;
        }
        .form-control {
            border-radius: 50px;
            padding: 10px 20px;
        }
        .btn-login {
            background-color: #FF6B6B;
            color: #fff;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 700;
            transition: 0.3s;
            width: 100%;
        }
        .btn-login:hover {
            background-color: #e75454;
            transform: translateY(-2px);
        }
        .text-muted a {
            color: #FF6B6B;
            text-decoration: none;
            font-weight: 600;
        }
        .alert-danger {
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h3>Đăng Nhập</h3>

    <?php if ($msg): ?>
        <div class="alert alert-danger"><?= e($msg) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="📧 Email" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="🔒 Mật khẩu" required>
        </div>
        <button type="submit" class="btn btn-login">Đăng nhập</button>
    </form>
   <p class="mt-3">
        <a href="quen_mat_khau.php" style="color:#FF6B6B; text-decoration:none; font-weight:600;">
            Quên mật khẩu?
        </a>
    </p>
    <p class="mt-3 text-muted">
        Chưa có tài khoản? <a href="dang_ky.php">Đăng ký ngay</a>
    </p>
    <p class="mt-2"><a href="../index.php" style="color:#555; text-decoration:none;">← Quay lại Trang chủ</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
