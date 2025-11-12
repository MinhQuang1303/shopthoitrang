<?php
// chat_box/process_search.php

header('Content-Type: application/json');

// 1. GỌI FILE CẤU HÌNH (Dùng require_once để đảm bảo file cấu hình được tải)
// Giả định cau_hinh.php nằm ở thư mục cha (thư mục gốc)
require_once '../includes/cau_hinh.php'; 


// --- KẾT NỐI CSDL SỬ DỤNG HẰNG SỐ ---
try {
    // DSN (Data Source Name) BẮT BUỘC có PORT
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";
    
    // Sử dụng các HẰNG SỐ đã được định nghĩa trong cau_hinh.php
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // Xử lý lỗi kết nối DB
    http_response_code(500); 
    // Trả về lỗi chi tiết từ MySQL/PHP
    echo json_encode(["status" => "error", "message" => "Lỗi CSDL: Không kết nối được. Chi tiết: " . $e->getMessage()]);
    exit();
}
// --------------------------------------------------------------------------


$user_input = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($user_input)) {
    echo json_encode(["status" => "error", "message" => "Vui lòng nhập từ khóa tìm kiếm."]);
    exit();
}

$results = [];

try {
    // 1. TÌM KIẾM CHÍNH XÁC trong Hot_Keywords
    // (Logic truy vấn SQL còn lại giữ nguyên)
    $sql_hot = "
        SELECT 
            p.product_id, 
            p.product_name, 
            p.base_price, 
            p.discount_percent,
            p.thumbnail_url
        FROM Products p
        JOIN Keyword_Product_Mapping kpm ON p.product_id = kpm.product_id
        JOIN Hot_Keywords hk ON kpm.keyword_id = hk.keyword_id
        WHERE hk.keyword_text = :keyword_input
        ORDER BY kpm.priority DESC, p.updated_at DESC
        LIMIT 8
    ";
    
    $stmt_hot = $conn->prepare($sql_hot);
    $stmt_hot->bindValue(':keyword_input', $user_input, PDO::PARAM_STR);
    $stmt_hot->execute();
    $results = $stmt_hot->fetchAll(PDO::FETCH_ASSOC);

    $source = empty($results) ? "product_name_search" : "hot_keyword_match";

    // ... (Phần logic tìm kiếm Fallback và cập nhật Hot Keywords giữ nguyên) ...

    // 4. Trả về kết quả
    if (!empty($results)) {
        echo json_encode([
            "status" => "success",
            "message" => "Đã tìm thấy " . count($results) . " sản phẩm liên quan đến **\"" . htmlspecialchars($user_input) . "\"**.",
            "products" => $results
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "message" => "Rất tiếc, không tìm thấy sản phẩm nào cho từ khóa **\"" . htmlspecialchars($user_input) . "\"**.",
            "products" => []
        ]);
    }

} catch(PDOException $e) {
    // Xử lý lỗi truy vấn SQL
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Lỗi truy vấn SQL: " . $e->getMessage()]);
}
?>