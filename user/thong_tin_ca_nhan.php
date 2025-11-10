<?php
require_once __DIR__.'/../includes/ham_chung.php';
if(!isLogged()) {
    header('Location: '.base_url('auth/dang_nhap.php'));
    exit;
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    if(empty($full_name)) {
        $error = 'Vui lòng nhập họ tên';
    } else {
        $stmt = $db->prepare('UPDATE Users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?');
        if($stmt->execute([$full_name, $phone, $address, $_SESSION['user']['user_id']])) {
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['address'] = $address;
            $success = 'Đã cập nhật thông tin thành công';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}

require_once __DIR__ . '/../user/tieu_de_k_banner.php';
?>

<div class="row">
    <div class="col-md-3">
        <?php require_once __DIR__.'/../views/thanh_ben_nguoi_dung.php'; ?>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Thông tin cá nhân</h3>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?= e($_SESSION['user']['email']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Họ tên</label>
                        <input type="text" name="full_name" class="form-control" value="<?= e($_SESSION['user']['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($_SESSION['user']['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="3"><?= e($_SESSION['user']['address'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__.'/../views/chan_trang.php'; ?>
