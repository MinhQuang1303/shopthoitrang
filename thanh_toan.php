<?php ob_start();
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/class_gio_hang.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Kiểm tra đăng nhập
if (!isLogged()) {
    header('Location: '.base_url('auth/dang_nhap.php'));
    exit;
}

$cart = new Cart($pdo);
$items = $cart->items();
if (empty($items)) {
    header('Location: '.base_url('gio_hang.php'));
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Toàn bộ logic xử lý POST đã được chuyển sang api/momo_xu_ly.php
// Trang này bây giờ chỉ để hiển thị form
?>

<div class="container py-5">
  <h2 class="fw-bold mb-4 text-center">🧾 Thanh toán đơn hàng</h2>

  <!-- Form sẽ luôn gửi đến api/momo_xu_ly.php -->
  <form id="paymentForm" method="post" action="<?= base_url('api/momo_xu_ly.php') ?>">
    <div class="row">
      <div class="col-md-7">
        <div class="card p-4 mb-4 shadow-sm">
          <h5 class="fw-bold mb-3">Thông tin giao hàng</h5>
          <div class="mb-3">
            <label class="form-label">Họ và tên</label>
            <input type="text" name="name" class="form-control" value="<?= e($_SESSION['user']['full_name'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Địa chỉ</label>
            <textarea name="address" class="form-control" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Ghi chú</label>
            <textarea name="note" class="form-control" placeholder="Ghi chú thêm (nếu có)"></textarea>
          </div>
        </div>
      </div>

      <div class="col-md-5">
        <div class="card p-4 shadow-sm">
          <h5 class="fw-bold mb-3">Phương thức thanh toán</h5>

          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="payment_method" id="pm_cod" value="cod" checked>
            <label class="form-check-label" for="pm_cod">💵 Thanh toán khi nhận hàng (COD)</label>
          </div>

          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="payment_method" id="pm_momo_qr" value="momo_qr">
            <label class="form-check-label" for="pm_momo_qr">📱 Thanh toán bằng MoMo QR Code</label>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="payment_method" id="pm_momo_atm" value="momo_atm">
            <label class="form-check-label" for="pm_momo_atm">💳 Thanh toán bằng MoMo ATM / Banking</label>
          </div>

          <?php
          $tong = $cart->totalAfterDiscount();
          ?>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
            <span>Tổng tiền:</span><span><?= currency($tong) ?></span>
          </div>

          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="amount" value="<?= $tong ?>">

          <button type="submit" class="btn btn-success w-100 fw-bold py-2" id="submitButton">
            Xác nhận đặt hàng
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
// Không cần JavaScript để thay đổi 'action' nữa.
// Chúng ta có thể thêm một hiệu ứng loading nhỏ cho nút bấm
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const button = document.getElementById('submitButton');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
});
</script>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
<?php ob_end_flush(); ?>