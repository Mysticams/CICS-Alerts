<?php
session_start();
require_once '../../config.php';
$pdo = pdo();

// Only allow logged-in users
if (!isset($_SESSION['logged_in'], $_SESSION['user_role'], $_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');

if ($message === '') {
    http_response_code(400);
    echo "Empty message";
    exit;
}

// Insert message directly using PDO (no stored procedure)
$stmt = $pdo->prepare("
    INSERT INTO messages (sender, recipient, message, user_id, timestamp)
    VALUES (:sender, :recipient, :message, :user_id, NOW())
");

$stmt->execute([
    ':sender' => 'user',
    ':recipient' => 'admin',
    ':message' => $message,
    ':user_id' => $userId
]);

echo "Message sent";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Chat</title>
<style>
* { box-sizing: border-box; transition: all 0.3s ease; }
body {
    margin: 0;
    font-family: 'Poppins', Arial, sans-serif;
    display: flex;
    height: 100vh;
    background: linear-gradient(135deg, #ffe5e5, #ffffff);
}
.chat-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
    position: relative;
}
.chat-header {
    background: #b71c1c;
    color: white;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}
.chat-header img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-right: 12px;
    background: #ffebee;
    border: 2px solid white;
}
.chat-header .name { font-weight: 600; font-size: 17px; }

.messages {
    flex: 1;
    padding: 20px 25px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: linear-gradient(180deg, #fff5f5, #ffffff);
    scroll-behavior: smooth;
}

.message-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.message-wrapper.admin { justify-content: flex-start; } /* Admin on left */
.message-wrapper.user { justify-content: flex-end; }   /* User on right */

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #ffebee;
    flex-shrink: 0;
}

.message {
    padding: 12px 16px;
    border-radius: 15px;
    max-width: 70%;
    font-size: 15px;
    line-height: 1.5;
    position: relative;
    word-wrap: break-word;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.message.admin {
    background: #b71c1c;
    color: white;
    border-bottom-right-radius: 5px;
}

.message.user {
    background: #ffe5e5;
    color: #111;
    border-bottom-left-radius: 5px;
}

.timestamp {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    text-align: right;
}

/* Input Area */
form {
    display: flex;
    padding: 15px 20px;
    border-top: 1px solid #eee;
    background: #fff;
    align-items: center;
}

textarea {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 25px;
    padding: 12px 15px;
    font-size: 15px;
    outline: none;
    resize: none;
    background: #fff8f8;
    transition: 0.2s;
}

textarea:focus {
    border-color: #b71c1c;
    background: #fff;
}

button {
    background: #b71c1c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    font-size: 20px;
    margin-left: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
    box-shadow: 0 4px 8px rgba(183, 28, 28, 0.4);
}

button:hover {
    background: #7f0000;
    transform: scale(1.1);
}

/* Scrollbar */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }
</style>
</head>
<body>
<div class="chat-section">
    <div class="chat-header">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=ff5252&color=fff" alt="User">
        <div class="name"><?= htmlspecialchars($userName) ?></div>
    </div>

    <div class="messages" id="messages"></div>

    <form id="chatForm">
        <textarea name="message" rows="1" placeholder="Type a message..." required></textarea>
        <button type="submit">&#10148;</button>
    </form>
</div>

<script>
const userId = <?= $userId ?>;
const messagesContainer = document.getElementById('messages');

async function loadMessages() {
    try {
        const res = await fetch('chat_fetch_user.php');
        const data = await res.json();
        messagesContainer.innerHTML = '';

        data.forEach(msg => {
            const wrapper = document.createElement('div');
            wrapper.className = 'message-wrapper ' + msg.sender;

            const avatar = document.createElement('img');
            avatar.className = 'avatar';
            avatar.src = msg.sender === 'admin'
                ? 'https://ui-avatars.com/api/?name=Admin&background=b71c1c&color=fff'
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.user_name)}&background=ff5252&color=fff`;

            const bubble = document.createElement('div');
            bubble.className = 'message ' + msg.sender;
            bubble.innerHTML = `<div>${msg.message}</div><div class="timestamp">${msg.sent_at}</div>`;

            if (msg.sender === 'admin') {
                wrapper.appendChild(avatar);
                wrapper.appendChild(bubble);
            } else {
                wrapper.appendChild(bubble);
                wrapper.appendChild(avatar);
            }

            messagesContainer.appendChild(wrapper);
        });

        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (err) {
        console.error('Error loading messages:', err);
    }
}

setInterval(loadMessages, 1000);
loadMessages();

document.getElementById('chatForm').addEventListener('submit', async e => {
    e.preventDefault();
    const textarea = e.target.message;
    const msg = textarea.value.trim();
    if (!msg) return;

    const formData = new FormData();
    formData.append('message', msg);
    await fetch('chat_send_user.php', { method: 'POST', body: formData });

    textarea.value = '';
    loadMessages();
});
</script>
</body>
</html>
