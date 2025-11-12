<?php
// ai_chat/ai_helper.php
// File này không cần nạp env.php nữa vì process_chat.php sẽ nạp cau_hinh.php

// Nạp OpenAI SDK
use OpenAI;

/**
 * Lưu lịch sử chat vào CSDL
 */
function save_chat($user_id, $message, $reply) {
    global $pdo; // $pdo sẽ được cung cấp từ file nạp nó (process_chat.php)
    
    // Kiểm tra xem $pdo đã được khởi tạo chưa
    if (!$pdo) {
        error_log("AI Chat Save Error: Biến \$pdo không tồn tại.");
        return;
    }

    $sql = "INSERT INTO ai_chat_history (user_id, message, reply) VALUES (?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $message, $reply]);
    } catch (Exception $e) {
        error_log("AI Chat Save Error: " . $e->getMessage());
    }
}

/**
 * Lấy câu trả lời từ OpenAI API
 */
function get_ai_reply($message) {
    // Kiểm tra xem API Key có tồn tại không
    if (empty(OPENAI_API_KEY)) {
        error_log("Lỗi AI: OPENAI_API_KEY chưa được cấu hình.");
        return "Xin lỗi, mình chưa được cấu hình. 😅";
    }

    try {
        $client = OpenAI::client(OPENAI_API_KEY);
        $response = $client->chat()->create([
            'model' => OPENAI_MODEL ,// Dùng đúng tên hằng số OPEN_MODEL
            'temperature' => 0.8,
            'max_tokens' => 500,
            'messages' => [
                ['role' => 'system', 'content' => 'Bạn là trợ lý thời trang siêu dễ thương, trả lời ngắn gọn, dùng emoji, nói tiếng Việt.'],
                ['role' => 'user', 'content' => $message]
            ]
        ]);
        return trim($response->choices[0]->message->content);

    } catch (Exception $e) {
        // Lấy thông báo lỗi chi tiết
        $error_msg = $e->getMessage();
        
        // Ghi lại lỗi chi tiết để debug
        error_log("Lỗi gọi OpenAI API: " . $error_msg);

        // === BẮT ĐẦU: PHẦN XỬ LÝ LỖI QUOTA CỦA BẠN ===
        // (Tôi đã thêm cả chữ "limit" để bắt lỗi chắc chắn hơn)
        if (stripos($error_msg, 'quota') !== false || stripos($error_msg, 'limit') !== false) {
            return "Xin lỗi, hệ thống AI đang hết hạn mức sử dụng. Vui lòng thử lại sau hoặc liên hệ admin để được hỗ trợ. 😊";
        }
        // === KẾT THÚC: PHẦN XỬ LỖI QUOTA ===

        // Lỗi chung (nếu không phải lỗi quota)
        return "Mình đang bận chút xíu, bạn hỏi lại sau 10s nha 🏃‍♀️💨";
    }
}