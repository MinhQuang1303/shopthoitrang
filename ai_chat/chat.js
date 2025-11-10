document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("chat-toggle");
  const chatWindow = document.getElementById("chat-window");
  const closeBtn = document.getElementById("chat-close");
  const sendBtn = document.getElementById("chat-send");
  const input = document.getElementById("chat-input");
  const messages = document.getElementById("chat-messages");

  toggle.onclick = () => chatWindow.classList.toggle("hidden");
  closeBtn.onclick = () => chatWindow.classList.add("hidden");

  sendBtn.onclick = sendMessage;
  input.addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
  });

  function appendMessage(content, type) {
    const div = document.createElement("div");
    div.className = type === "user" ? "user-msg" : "ai-msg";
    div.innerHTML = content;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
  }

  function sendMessage() {
    const text = input.value.trim();
    if (!text) return;
    appendMessage(text, "user");
    input.value = "";

    fetch("ai_chat/process_chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "message=" + encodeURIComponent(text)
    })
      .then(res => res.text())
      .then(reply => appendMessage(reply, "ai"))
      .catch(() =>
        appendMessage("⚠️ Xin lỗi, hệ thống đang bận. Thử lại sau!", "ai")
      );
  }
});
