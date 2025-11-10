<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isLogged()) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID sản phẩm']);
    exit;
}

// Kiểm tra đã yêu thích chưa
$stmt = $db->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);

if ($stmt->rowCount() > 0) {
    // Nếu có rồi => xóa (bỏ tim)
    $del = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $del->execute([$user_id, $product_id]);
    echo json_encode(['status' => 'removed']);
} else {
    // Nếu chưa có => thêm mới
    $insert = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $insert->execute([$user_id, $product_id]);
    echo json_encode(['status' => 'added']);
}
