<?php
session_start();
require_once '../../config.php';
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
        * {
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        body {
            margin: 0;
            font-family: 'Poppins', Arial, sans-serif;
            display: flex;
            height: 100vh;
            background: linear-gradient(135deg, #ffe5e5, #ffffff);
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(183, 28, 28, 0.95);
            color: white;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
            border-right: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar h2 {
            text-align: center;
            padding: 20px;
            margin: 0;
            background: rgba(127, 0, 0, 0.9);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-list {
            flex: 1;
            overflow-y: auto;
        }

        .user-list a {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            transition: 0.3s;
        }

        .user-list a:hover,
        .user-list a.active {
            background: rgba(255, 255, 255, 0.15);
        }

        .user-list a img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            border: 2px solid #fff;
            object-fit: cover;
            background: #ffebee;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info span {
            font-size: 14px;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #4CAF50;
            border-radius: 50%;
            margin-top: 4px;
        }

        /* Chat Section */
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            position: relative;
            border-left: 1px solid #eee;
        }

        .chat-header {
            background: #b71c1c;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chat-header img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-right: 12px;
            background: #ffebee;
            border: 2px solid white;
        }

        .chat-header .name {
            font-weight: 600;
            font-size: 17px;
        }

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

        .message-wrapper.admin {
            justify-content: flex-end;
        }

        .message-wrapper.user {
            justify-content: flex-start;
        }

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

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 5px;
        }

        /* Responsive */
        @media(max-width: 768px) {
            .sidebar {
                display: none;
            }
            .chat-section {
                width: 100%;
            }
        }

    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Chat Users</h2>
        <div class="user-list">
            <?php foreach ($users as $user): ?>
                <a href="?user_id=<?= $user['id'] ?>" class="<?= ($selectedUserId == $user['id']) ? 'active' : '' ?>">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['first_name'].' '.$user['last_name']) ?>&background=ff5252&color=fff" alt="avatar">
                    <div class="user-info">
                        <span><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                        <div class="status-dot"></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-section">
        <?php if ($selectedUserId): ?>
            <?php
            $selectedUser = array_filter($users, fn($u) => $u['id'] == $selectedUserId);
            $selectedUser = reset($selectedUser);
            ?>
            <div class="chat-header">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($selectedUser['first_name'].' '.$selectedUser['last_name']) ?>&background=ff5252&color=fff">
                <div class="name"><?= htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']) ?></div>
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
        const selectedUserId = <?= $selectedUserId ?? 'null' ?>;
        const messagesContainer = document.getElementById('messages');

        async function loadMessages() {
            if (!selectedUserId) return;
            try {
                const res = await fetch('chat_fetch_admin.php?user_id=' + selectedUserId);
                const data = await res.json();
                messagesContainer.innerHTML = '';
                data.forEach(msg => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'message-wrapper ' + msg.sender;

                    const avatar = document.createElement('img');
                    avatar.src = msg.sender === 'admin'
                        ? 'https://ui-avatars.com/api/?name=Admin&background=b71c1c&color=fff'
                        : `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.user_name)}&background=ff5252&color=fff`;
                    avatar.className = 'avatar';

                    const bubble = document.createElement('div');
                    bubble.className = 'message ' + msg.sender;
                    bubble.innerHTML = `
                        <div>${msg.message}</div>
                        <div class="timestamp">${msg.sent_at}</div>
                    `;

                    if (msg.sender === 'admin') {
                        wrapper.appendChild(bubble);
                        wrapper.appendChild(avatar);
                    } else {
                        wrapper.appendChild(avatar);
                        wrapper.appendChild(bubble);
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
