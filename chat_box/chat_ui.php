<?php
// chat_box/chat_ui.php
?>
<div class="chat-container">
    <h2>🔎 Hỗ trợ Tìm kiếm Sản phẩm</h2>
    
    <div id="chat-box" class="chat-box">
        <div class="ai-message">
            <p>Chào bạn! Hãy nhập từ khóa tìm kiếm để tôi tìm kiếm sản phẩm giúp bạn.</p>
        </div>
    </div>
    
    <div class="chat-input">
        <input type="text" id="user-input" placeholder="Nhập từ khóa tìm kiếm của bạn..." onkeydown="if (event.keyCode == 13) sendMessage()">
        <button onclick="sendMessage()">Gửi</button>
    </div>
</div>