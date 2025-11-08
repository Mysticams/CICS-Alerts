<?php
session_start();
require_once '../config.php';
$pdo = pdo();

$users = $pdo->query("SELECT id, first_name, last_name FROM users ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($users[0]['id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Chat</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            background: #f5f5f5;
        }

        .sidebar {
            width: 250px;
            background: #b71c1c;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            text-align: center;
            padding: 15px;
            margin: 0;
            background: #7f0000;
        }

        .user-list {
            flex: 1;
            overflow-y: auto;
        }

        .user-list a {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-list a:hover,
        .user-list a.active {
            background: #ff5252;
        }

        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            border-left: 1px solid #ddd;
        }

        .chat-header {
            background: #b71c1c;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }

        .messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #ffebeb;
        }

        .message {
            padding: 10px;
            border-radius: 10px;
            max-width: 70%;
            word-wrap: break-word;
        }

        .message.admin {
            background: #b71c1c;
            color: white;
            align-self: flex-end;
        }

        .message.user {
            background: #ffccd5;
            color: black;
            align-self: flex-start;
        }

        form {
            display: flex;
            padding: 10px;
            border-top: 1px solid #ddd;
            background: #fff;
        }

        textarea {
            flex: 1;
            border: none;
            border-radius: 20px;
            padding: 10px 15px;
            font-size: 14px;
            outline: none;
            resize: none;
            background: #ffccd5;
        }

        button {
            background: #b71c1c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 16px;
            margin-left: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        button:hover {
            background: #7f0000;
        }

        .timestamp {
    font-size: 12px;
    color: #555;       /* professional gray color */
    margin-top: 5px;
    text-align: right;
    opacity: 0.8;
}
.sender {
    font-weight: bold;
    margin-bottom: 3px;
    font-size: 13px;
}
.text {
    font-size: 14px;
    line-height: 1.4;
}

    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Users</h2>
        <div class="user-list">
            <?php foreach ($users as $user): ?>
                <a href="?user_id=<?= $user['id'] ?>" class="<?= ($selectedUserId == $user['id']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-section">
        <?php if ($selectedUserId): ?>
            <div class="chat-header">
                Chat with <?= htmlspecialchars($users[array_search($selectedUserId, array_column($users, 'id'))]['first_name'] ?? 'User') ?>
            </div>
            <div class="messages" id="messages"></div>
            <form id="chatForm">
                <textarea name="message" rows="1" placeholder="Type a message..." required></textarea>
                <button type="submit">&#10148;</button>
            </form>
        <?php else: ?>
            <div style="margin:auto;text-align:center;color:#777;">No users found.</div>
        <?php endif; ?>
    </div>

    <script>
        const selectedUserId = <?= $selectedUserId ?>;
        const messagesContainer = document.getElementById('messages');

        async function loadMessages() {
            try {
                const res = await fetch('chat_fetch_admin.php?user_id=' + selectedUserId);
                const data = await res.json();
                messagesContainer.innerHTML = '';
                data.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'message ' + msg.sender;
                    div.innerHTML = `<div class="sender">${msg.sender.charAt(0).toUpperCase()+msg.sender.slice(1)}</div>
                             <div class="text">${msg.message}</div>
                             <div class="timestamp">${msg.sent_at}</div>`;
                    messagesContainer.appendChild(div);
                });
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } catch (err) {
                console.error('Error loading messages:', err);
            }
        }

        // Poll every 1 second
        setInterval(loadMessages, 1000);
        loadMessages();

        // Send via AJAX
        document.getElementById('chatForm').addEventListener('submit', async e => {
            e.preventDefault();
            const textarea = e.target.message;
            const msg = textarea.value.trim();
            if (!msg) return;

            const formData = new FormData();
            formData.append('message', msg);

            await fetch('chat_send_admin.php?user_id=' + selectedUserId, {
                method: 'POST',
                body: formData
            });
            textarea.value = '';
            loadMessages();
        });
    </script>
</body>

</html>