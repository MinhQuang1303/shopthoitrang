<?php
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../user/tieu_de_k_banner.php';

if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Lấy danh sách đơn hàng của user
$stmt = $db->prepare('
    SELECT order_id, order_code, status, total_amount, final_amount, created_at
    FROM Orders
    WHERE user_id = ?
    ORDER BY created_at DESC
');
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2>Lịch sử mua hàng</h2>
    <?php if (count($orders) === 0): ?>
        <p>Bạn chưa có đơn hàng nào.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="card mb-4">
                <div class="card-header">
                    Mã đơn hàng: <?= e($order['order_code']) ?> |
                    Trạng thái: <?= ucfirst(e($order['status'])) ?> |
                    Tổng tiền: <?= number_format($order['final_amount'], 0, ',', '.') ?>₫ |
                    Ngày đặt: <?= e($order['created_at']) ?>
                </div>
                <div class="card-body">
                    <h5>Chi tiết sản phẩm:</h5>
                    <?php
                    // Lấy chi tiết đơn hàng
                    $stmtDetails = $db->prepare('
                        SELECT od.*, pv.variant_id, p.product_id, p.product_name
                        FROM Order_Details od
                        JOIN Product_Variants pv ON od.variant_id = pv.variant_id
                        JOIN Products p ON pv.product_id = p.product_id
                        WHERE od.order_id = ?
                    ');
                    $stmtDetails->execute([$order['order_id']]);
                    $orderItems = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Màu</th>
                                <th>Size</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th>Đánh giá</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?= e($item['product_name']) ?></td>
                                <td><?= e($item['color']) ?></td>
                                <td><?= e($item['size']) ?></td>
                                <td><?= number_format($item['price'], 0, ',', '.') ?>₫</td>
                                <td><?= e($item['quantity']) ?></td>
                                <td><?= number_format($item['subtotal'], 0, ',', '.') ?>₫</td>
                                <td>
                                    <?php
                                    // Kiểm tra xem user đã đánh giá chưa
                                    $stmtReview = $db->prepare('
                                        SELECT * FROM Reviews 
                                        WHERE product_id = ? AND user_id = ? AND order_id = ?
                                    ');
                                    $stmtReview->execute([$item['product_id'], $user_id, $order['order_id']]);
                                    $review = $stmtReview->fetch(PDO::FETCH_ASSOC);
                                    if ($review):
                                        echo "⭐ " . e($review['rating']) . "/5 <br>" . e($review['comment']);
                                    else:
                                        if ($order['status'] === 'delivered'): ?>
                                            <a href="<?= base_url('user/form_danh_gia.php?order_id=' . $order['order_id'] . '&product_id=' . $item['product_id']) ?>" class="btn btn-sm btn-primary">Đánh giá</a>
                                        <?php else:
                                            echo '-';
                                        endif;
                                    endif;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>
