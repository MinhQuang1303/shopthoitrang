<?php
// ai_chat/process_chat.php

// --- SỬA LỖI QUAN TRỌNG ---
// 1. Nạp file cấu hình (để có API Key, DB config và nạp Composer)
require_once __DIR__ . '/../includes/cau_hinh.php'; // ĐÚNG
// 2. Nạp file kết nối DB (để tạo biến $pdo)
require_once __DIR__ . '/../includes/ket_noi_db.php'; 
// 3. Nạp file helper (sau khi đã có $pdo và API keys)
require_once __DIR__ . '/ai_helper.php';
// --- KẾT THÚC SỬA LỖI ---

session_start();

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    // Trả về JSON hợp lệ
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['reply' => 'Method not allowed']);
    exit;
}

// Đặt header JSON ngay từ đầu
header('Content-Type: application/json; charset=utf-8');

$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['reply' => 'Bạn chưa nhập gì mà 🙄']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

// Lấy câu trả lời từ AI
$reply = get_ai_reply($message);

// Lưu vào CSDL (nếu $user_id tồn tại)
if ($user_id) {
    save_chat($user_id, $message, $reply);
}

// Trả về JSON
// Không cần ob_start/ob_end_clean nếu bạn không echo gì khác
echo json_encode(['reply' => $reply]);
exit;