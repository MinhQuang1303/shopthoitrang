<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/class_gio_hang.php';
require_once __DIR__ . '/../includes/ham_chung.php';

// Khởi động session an toàn
if (session_status() === PHP_SESSION_NONE) session_start();

$cart = new Cart($pdo);

// Lấy hành động
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$token  = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

// ✅ Kiểm tra CSRF token
if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
    flash_set('error', 'CSRF token không hợp lệ!');
    header('Location: ../gio_hang.php');
    exit;
}

// ===========================
// 🛠️ Xử lý hành động người dùng
// ===========================
switch ($action) {

    // Cập nhật toàn bộ giỏ hàng
    case 'update_all':
        if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $variant_id => $qty) {
                $qty = (int)$qty;
                if ($qty <= 0) {
                    $cart->remove($variant_id);
                } else {
                    $cart->update($variant_id, $qty);
                }
            }
            flash_set('success', '✅ Cập nhật giỏ hàng thành công!');
        } else {
            flash_set('error', 'Không có sản phẩm để cập nhật!');
        }
        break;

    // Xóa 1 sản phẩm
    case 'remove':
        $variant_id = (int)($_GET['variant_id'] ?? 0);
        if ($variant_id > 0) {
            $cart->remove($variant_id);
            flash_set('success', '🗑️ Đã xóa sản phẩm khỏi giỏ hàng!');
        } else {
            flash_set('error', 'Không xác định được sản phẩm cần xóa!');
        }
        break;

    // Làm mới toàn bộ giỏ hàng
    case 'clear':
        $cart->clear();
        flash_set('success', '🧹 Giỏ hàng đã được làm trống!');
        break;

    default:
        flash_set('error', 'Hành động không hợp lệ!');
        break;
}

// Quay lại trang giỏ hàng
header('Location: ../gio_hang.php');
exit;
