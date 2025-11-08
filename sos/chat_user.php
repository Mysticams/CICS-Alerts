<?php
session_start();
require_once '../config.php';
$pdo = pdo();

if (!isset($_SESSION['logged_in'], $_SESSION['user_id'], $_SESSION['user_role'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$userId = (int)$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Chat</title>
<style>
body { font-family: Arial,sans-serif; margin:0; padding:0; }
.chat-container { width:400px; margin:50px auto; display:flex; flex-direction:column; height:500px; background:rgba(255,255,255,0.95); border-radius:15px; box-shadow:0 4px 10px rgba(0,0,0,0.2);}
h1 { background:#b71c1c; color:white; margin:0; padding:15px; text-align:center;}
.messages { flex:1; padding:15px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; background:#ffebeb;}
.message { padding:10px; border-radius:10px; max-width:80%; word-wrap:break-word; }
.message.admin { background:#b71c1c; color:white; align-self:flex-start; }
.message.user { background:#ffccd5; color:#000; align-self:flex-end; }
.sender { font-weight:bold; font-size:12px; margin-bottom:5px; }
.timestamp { font-size:10px; color:#777; text-align:right; margin-top:5px; }
form { display:flex; padding:10px; border-top:1px solid #ddd; background:#fff;}
textarea { flex:1; border:none; border-radius:20px; padding:10px 15px; font-size:14px; outline:none; resize:none; background:#ffccd5;}
button { background:#990000; color:white; border:none; border-radius:50%; width:40px; height:40px; font-size:16px; margin-left:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
button:hover { background:#7f0000; }
</style>
</head>
<body>
<div class="chat-container">
    <h1>Chat with Admin</h1>
    <div class="messages" id="messages"></div>

    <form id="chatForm">
        <textarea name="message" rows="2" placeholder="Type your message..." required></textarea>
        <button type="submit">➤</button>
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
            const div = document.createElement('div');
            div.className = 'message ' + msg.sender;
            div.innerHTML = `<div class="sender">${msg.sender.charAt(0).toUpperCase() + msg.sender.slice(1)}</div>
                             <div class="text">${msg.message}</div>
                             <div class="timestamp">${msg.sent_at}</div>`;
            messagesContainer.appendChild(div);
        });
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (err) {
        console.error('Error loading messages:', err);
    }
}

// Load messages every 1 second
setInterval(loadMessages, 1000);
loadMessages();

// Send message via AJAX
document.getElementById('chatForm').addEventListener('submit', async e => {
    e.preventDefault();
    const textarea = e.target.message;
    const message = textarea.value.trim();
    if (!message) return;

    const formData = new FormData();
    formData.append('message', message);

    try {
        await fetch('chat_send_user.php', { method:'POST', body:formData });
        textarea.value = '';
        loadMessages();
    } catch (err) {
        console.error('Error sending message:', err);
    }
});
</script>
</body>
</html>
