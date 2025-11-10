<?php
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__ . '/includes/class_gio_hang.php';
require_once __DIR__ . '/includes/ham_chung.php';
require_once __DIR__ . '/views/tieu_de.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$cart = new Cart($pdo);
$items = $cart->items();
$voucher = $cart->currentVoucher();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="container py-5">
  <h2 class="fw-bold text-center mb-4">🛒 Giỏ hàng của bạn</h2>
  <?php flash_show(); ?>

  <?php if (empty($items)): ?>
    <div class="alert alert-info text-center">
      Giỏ hàng trống! <a href="<?= base_url('index.php') ?>">Mua sắm ngay</a>
    </div>

  <?php else: ?>

    <!-- Cập nhật giỏ hàng -->
    <form method="post" action="<?= base_url('api/cap_nhat_gio_hang.php') ?>">
      <input type="hidden" name="action" value="update_all">
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

      <div class="table-responsive mb-4">
        <table class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>Hình</th>
              <th>Sản phẩm</th>
              <th>Biến thể</th>
              <th>Giá</th>
              <th>Số lượng</th>
              <th>Thành tiền</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><img src="<?= base_url('assets/images/san_pham/'.$it['image_url']) ?>" width="80"></td>
                <td><?= e($it['product_name']) ?></td>
                <td><?= e($it['color'].' / '.$it['size']) ?></td>
                <td><?= currency($it['price']) ?></td>
                <td><input type="number" name="qty[<?= $it['variant_id'] ?>]" value="<?= $it['qty'] ?>" min="1" class="form-control text-center"></td>
                <td><?= currency($it['subtotal']) ?></td>
                <td>
                  <a href="<?= base_url('api/cap_nhat_gio_hang.php?action=remove&variant_id='.$it['variant_id'].'&csrf_token='.$_SESSION['csrf_token']) ?>" class="btn btn-sm btn-danger">Xóa</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between">
        <button class="btn btn-primary">Cập nhật giỏ hàng</button>
        <a href="<?= base_url('api/cap_nhat_gio_hang.php?action=clear&csrf_token='.$_SESSION['csrf_token']) ?>" class="btn btn-outline-danger">Xóa tất cả</a>
      </div>
    </form>

    <hr>

    <!-- Tổng tiền + mã giảm giá -->
    <div class="card p-4 shadow-sm">
      <h5 class="fw-bold">Tổng thanh toán</h5>

      <form method="post" action="<?= base_url('api/ap_dung_ma_giam_gia.php') ?>" class="mt-2">
        <div class="input-group">
          <input type="text" name="voucher_code" placeholder="Nhập mã giảm giá" class="form-control"
                 value="<?= e($voucher['voucher_code'] ?? '') ?>">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
          <button class="btn btn-outline-primary">Áp dụng</button>
        </div>
      </form>

      <?php
      $tong_tien = $cart->totalBeforeDiscount();
      $giam_gia = $cart->discountAmount();
      $tong_sau = $cart->totalAfterDiscount();
      $_SESSION['tong_tien'] = $tong_sau;
      ?>

      <div class="mt-3">
        <div class="d-flex justify-content-between"><span>Tạm tính:</span><strong><?= currency($tong_tien) ?></strong></div>
        <?php if ($giam_gia > 0): ?>
          <div class="d-flex justify-content-between text-success">
            <span>Giảm giá:</span><strong>-<?= currency($giam_gia) ?></strong>
          </div>
        <?php endif; ?>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5">
          <span>Tổng cộng:</span><span><?= currency($tong_sau) ?></span>
        </div>
      </div>

      <?php if ($voucher): ?>
        <div class="alert alert-success mt-3">
          Mã <strong><?= e($voucher['voucher_code']) ?></strong> giảm <?= $voucher['discount_percent'] ?>%
          <a href="<?= base_url('api/ap_dung_ma_giam_gia.php?action=remove&csrf_token='.$_SESSION['csrf_token']) ?>" class="ms-2">(Hủy)</a>
        </div>
      <?php endif; ?>

      <hr>

      <!-- Nút thanh toán -->
      <form method="post" action="<?= base_url('thanh_toan.php') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="amount" value="<?= $tong_sau ?>">
        <button type="submit" class="btn btn-success w-100 fw-bold py-2 mt-3">
          Tiến hành thanh toán
        </button>
      </form>
    </div>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
