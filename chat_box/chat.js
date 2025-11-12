// chat_box/chat.js

$(document).ready(function() {
    // Cuộn xuống dưới cùng khi tải
    var chatBox = $('#chat-box');
    if (chatBox.length) {
        chatBox.scrollTop(chatBox[0].scrollHeight);
    }
});

function sendMessage() {
    var userInput = $('#user-input').val().trim();
    if (userInput === "") return;

    appendMessage('user', userInput); 
    
    $.ajax({
        // Đường dẫn đã được sửa để tránh lỗi 404 (Giả định trang chính ở thư mục cha)
        url: 'chat_box/process_search.php', 
        method: 'POST',
        data: { message: userInput },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                appendMessage('ai', response.message);
                if (response.products && response.products.length > 0) {
                    displayProducts(response.products);
                }
            } else {
                appendMessage('ai', 'Lỗi hệ thống: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            // Xử lý lỗi kết nối AJAX (404, 500)
            let errorMsg = 'Đã xảy ra lỗi kết nối máy chủ.';
            if (xhr.status === 404) {
                 errorMsg = 'Lỗi 404: Không tìm thấy file xử lý (Kiểm tra lại đường dẫn process_search.php).';
            } else if (xhr.status === 500) {
                 errorMsg = 'Lỗi 500: Lỗi Server PHP/CSDL (Kiểm tra log server hoặc cấu hình DB).';
            }
            appendMessage('ai', errorMsg);
            console.error("AJAX Error:", status, error, xhr);
        }
    });

    $('#user-input').val(''); 
}

function appendMessage(sender, message) {
    var chatBox = $('#chat-box');
    var className = (sender === 'user') ? 'user-message' : ((sender === 'ai-products') ? 'ai-product-message' : 'ai-message');
    
    if(sender === 'ai-products') {
        chatBox.append('<div class="' + className + '">' + message + '</div>');
    } else {
        chatBox.append('<div class="' + className + '"><p>' + message + '</p></div>');
    }

    chatBox.scrollTop(chatBox[0].scrollHeight);
}

// chat_box/chat.js (Đoạn mã đã được cập nhật)

// ... (các hàm sendMessage và appendMessage giữ nguyên) ...

function displayProducts(products) {
    var productHtml = '<div class="product-results"><div class="product-list">';
    
    products.forEach(function(product) {
        var basePrice = parseFloat(product.base_price);
        var discountPercent = parseInt(product.discount_percent);
        var finalPrice = basePrice * (1 - discountPercent / 100);
        
        var formatPrice = function(price) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
        };

        var correctPath = 'chi_tiet_san_pham.php?product_id=' + product.product_id; 
        
        productHtml += `
            <div class="product-card">
                <a href="${correctPath}" target="_blank">
                    <img src="${product.thumbnail_url || 'placeholder.jpg'}" alt="${product.product_name}" class="product-image">
                </a>
                <div class="product-info">
                    <a href="${correctPath}" target="_blank" class="product-name">${product.product_name}</a>
                    <p class="product-price">
                        <span class="final-price">${formatPrice(finalPrice)}</span>
                        ${discountPercent > 0 ? `<del class="base-price">${formatPrice(basePrice)}</del>` : ''}
                    </p>
                </div>
            </div>
        `;
    });
    
    productHtml += '</div></div>';
    appendMessage('ai-products', productHtml); 
}