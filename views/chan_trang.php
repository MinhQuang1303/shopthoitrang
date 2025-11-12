<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Thời Trang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="chat_box/chat.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<footer class="bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="text-uppercase fw-bold mb-4">Shop Thời Trang</h5>
                <p>Địa chỉ: 123 Đường Thời Trang, Quận XYZ, TP.HCM</p>
                <p>Email: <a href="mailto:support@shopthoitrang.vn" class="text-white text-decoration-none">support@shopthoitrang.vn</a></p>
                <p>Hotline: <a href="tel:0987654321" class="text-white text-decoration-none">0987.654.321</a></p>
            </div>

            <div class="col-md-4 mb-4">
                <h5 class="text-uppercase fw-bold mb-4">Hỗ trợ Khách hàng</h5>
                <ul class="list-unstyled">
                    <li><a href="/chinh-sach-doi-tra" class="text-white text-decoration-none">Chính sách Đổi trả</a></li>
                    <li><a href="/chinh-sach-bao-mat" class="text-white text-decoration-none">Chính sách Bảo mật</a></li>
                    <li><a href="/dieu-khoan" class="text-white text-decoration-none">Điều khoản Sử dụng</a></li>
                    <li><a href="/faq" class="text-white text-decoration-none">Câu hỏi thường gặp</a></li>
                </ul>
            </div>

            <div class="col-md-4 mb-4">
                <h5 class="text-uppercase fw-bold mb-4">Thanh toán & Kết nối</h5>
                <p>Chấp nhận thanh toán qua:</p>
                <div class="payment-methods">
                    <span class="badge bg-light text-dark me-2">COD</span>
                    <span class="badge bg-light text-dark me-2">MOMO</span>
                    <span class="badge bg-light text-dark me-2">VISA</span>
                </div>
            </div>
        </div>
        <hr class="mb-4">
        <div class="row">
            <div class="col-md-12 text-center">
                <p class="mb-0">© 2025 ShopThoiTrang | Thiết kế bởi Nhóm Đồ Án Quang_Xuân</p>
            </div>
        </div>
    </div>
</footer>

<?php
// Chatbox sẽ sử dụng CSS Fixed Position để dính vào góc phải.
include 'chat_box/chat_ui.php'; 
?>

<script src="chat_box/chat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>