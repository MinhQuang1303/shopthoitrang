<?php
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de.php';

// Lấy orderId từ MoMo (hoặc order_id cũ nếu có)
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id && isset($_GET['orderId'])) {
    $order_id = (int)$_GET['orderId']; // MoMo trả về orderId
}

if(!$order_id) {
    echo "<div class='alert alert-danger'>Không tìm thấy đơn hàng</div>";
    require_once __DIR__.'/views/chan_trang.php';
    exit;
}

// Lấy đơn hàng
$order_stmt = $pdo->prepare("SELECT * FROM Orders WHERE order_id=?");
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch();

if (!$order) {
    echo "<div class='alert alert-danger'>Đơn hàng không tồn tại trong hệ thống</div>";
    require_once __DIR__.'/views/chan_trang.php';
    exit;
}

// Lấy chi tiết đơn hàng
$detail_stmt = $pdo->prepare("SELECT * FROM Order_Details WHERE order_id=?");
$detail_stmt->execute([$order_id]);
$details = $detail_stmt->fetchAll();

// Lấy thông tin thanh toán
$payment_stmt = $pdo->prepare("SELECT * FROM Payments WHERE order_id=? ORDER BY payment_id DESC LIMIT 1");
$payment_stmt->execute([$order_id]);
$payment = $payment_stmt->fetch();

// --- Cập nhật trạng thái thanh toán nếu MoMo trả về ---
if (isset($_GET['resultCode'])) {
    $resultCode = $_GET['resultCode'];
    $message = $_GET['message'] ?? '';

    if ($resultCode == '0') {
        // Thanh toán thành công
        $pdo->prepare("UPDATE Payments SET status='completed' WHERE order_id=?")->execute([$order_id]);
        $pdo->prepare("UPDATE Orders SET payment_status='paid', order_status='confirmed' WHERE order_id=?")->execute([$order_id]);
        $msg = "✅ Thanh toán thành công!";
        $alert = "alert-success";
    } else {
        // Thất bại
        $pdo->prepare("UPDATE Payments SET status='failed' WHERE order_id=?")->execute([$order_id]);
        $pdo->prepare("UPDATE Orders SET payment_status='failed' WHERE order_id=?")->execute([$order_id]);
        $msg = "❌ Thanh toán thất bại – " . htmlspecialchars($message);
        $alert = "alert-danger";
    }
} else {
    // Nếu không có resultCode, giữ nguyên trạng thái cũ
    if ($payment && $payment['status'] === 'completed') {
        $msg = "✅ Thanh toán thành công!";
        $alert = "alert-success";
    } elseif ($payment && $payment['status'] === 'failed') {
        $msg = "❌ Thanh toán thất bại!";
        $alert = "alert-danger";
    } else {
        $msg = "⏳ Đơn hàng đang chờ thanh toán.";
        $alert = "alert-warning";
    }
}
?>
<div class="container mt-4">
    <h3>Kết quả thanh toán</h3>
    <div class="alert <?= $alert ?>"><?= $msg ?></div>

    <p><strong>Mã đơn:</strong> <?= e($order['order_code']) ?> — 
       <strong>Tổng tiền:</strong> <?= currency($order['final_amount']) ?></p>

    <h4>Chi tiết đơn hàng</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sản phẩm</th><th>Số lượng</th><th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($details as $d): ?>
            <tr>
                <td><?= e($d['product_name'].' ('.$d['color'].'/'.$d['size'].')') ?></td>
                <td><?= $d['quantity'] ?></td>
                <td><?= currency($d['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h4>Phương thức thanh toán</h4>
    <?php if($order['payment_method'] === 'cod'): ?>
        <p>Chọn COD (Thanh toán khi nhận hàng)</p>
    <?php else: ?>
        <p>Thanh toán qua MoMo: <?= strtoupper(str_replace('momo_', '', $order['payment_method'])) ?></p>
        <?php if (!empty($order['momo_trans_id'])): ?>
            <p>Mã giao dịch MoMo: <?= e($order['momo_trans_id']) ?></p>
        <?php endif; ?>
        <?php if ($payment): ?>
            <p>Số tiền: <?= currency($payment['amount']) ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
