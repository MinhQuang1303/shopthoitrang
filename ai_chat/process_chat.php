<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';

header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') exit('Vui lòng nhập câu hỏi.');

    // Gợi ý: Nhận dạng ý định người dùng
    if (preg_match('/áo|quần|váy|giày|sơ mi|váy|áo khoác|túi/i', $msg)) {
        // Tìm sản phẩm có tên liên quan
$stmt = $pdo->prepare("SELECT * FROM Products WHERE product_name LIKE ?");
        $stmt->execute(['%' . $msg . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($results) {
            echo "Mình gợi ý cho bạn một vài sản phẩm phù hợp:<br><ul>";
            foreach ($results as $sp) {
                echo "<li>🛍️ " . htmlspecialchars($sp) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "Hiện tại mình chưa tìm thấy sản phẩm phù hợp, bạn thử mô tả cụ thể hơn nhé!";
        }
    } elseif (preg_match('/đổi trả|bảo hành/i', $msg)) {
        echo "🧾 Chính sách đổi trả: Bạn có thể đổi trả sản phẩm trong vòng 7 ngày kể từ khi nhận hàng nếu còn nguyên tem, tag và chưa qua sử dụng.";
    } elseif (preg_match('/thanh toán|momo|chuyển khoản/i', $msg)) {
        echo "💳 Shop hỗ trợ thanh toán qua MoMo, chuyển khoản ngân hàng và COD (nhận hàng trả tiền).";
    } elseif (preg_match('/giao hàng|ship|vận chuyển/i', $msg)) {
        echo "🚚 Thời gian giao hàng thường từ 2–4 ngày tuỳ khu vực. Đơn nội thành thường giao trong ngày.";
    } else {
        echo "🤖 Mình chưa hiểu rõ câu hỏi. Bạn có thể hỏi về sản phẩm, cách thanh toán, đổi trả hoặc giao hàng nhé!";
    }
}
