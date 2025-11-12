const box = document.getElementById('ai-messages');
const input = document.getElementById('ai-input');
const send = document.getElementById('ai-send');

function add(msg, type) {
    const div = document.createElement('div');
    div.className = `ai-msg ${type}`;
    div.innerHTML = msg;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

send.onclick = () => sendMsg();
input.addEventListener('keypress', e => e.key === 'Enter' && sendMsg());

function sendMsg() {
    const msg = input.value.trim();
    if (!msg) return;
    add(msg, 'user');
    input.value = '';
    add('AI đang gõ...', 'ai');

    fetch('ai_chat/process_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(data => {
        box.lastChild.remove();
        add(data.reply || 'Mình chưa hiểu, hỏi lại nha', 'ai');
    })
    .catch(() => {
        box.lastChild.remove();
        add('Lỗi mạng rồi, thử lại nha', 'ai');
    });
}

setTimeout(() => {
    add('Chào bạn!<br>Mình là trợ lý thời trang đây<br>Bạn cần tìm kiếm về size, giá, phối đồ thì nhắn mình nha', 'ai');
}, 1000);