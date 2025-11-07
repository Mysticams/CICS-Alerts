<?php
session_start();
require_once '../config.php';

// ✅ Get the PDO connection (config uses function pdo())
$pdo = pdo();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $sender = 'user';
    $recipient = 'admin';
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $pdo->prepare("CALL insert_message(:sender, :recipient, :message)");
        $stmt->execute([
            ':sender' => $sender,
            ':recipient' => $recipient,
            ':message' => $message
        ]);
    }

    header('Location: chat_user.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Chat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .chat-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            width: 400px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin: 50px auto;
            height: 500px;
        }

        h1 {
            background-color: #b71c1c;
            color: white;
            margin: 0;
            padding: 15px;
            text-align: center;
        }

        .messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background-color: #ffebeb;
        }

        .message {
            display: flex;
            flex-direction: column;
            padding: 10px;
            border-radius: 10px;
            max-width: 80%;
            word-wrap: break-word;
        }

        .message.admin {
            background-color: #b71c1c;
            color: white;
            align-self: flex-start;
            /* Admin on left */
        }

        .message.user {
            background-color: #ffccd5;
            color: #000;
            align-self: flex-end;
            /* User on right */
        }

        .sent {
            background-color: #b71c1c;
            color: white;
            align-self: flex-end;
        }

        .received {
            background-color: #ffccd5;
            color: #000;
            align-self: flex-start;
        }

        .sender {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .timestamp {
            font-size: 10px;
            color: #777;
            text-align: right;
            margin-top: 5px;
        }

        form {
            display: flex;
            padding: 10px;
            background-color: #ffffff;
            border-top: 1px solid #ddd;
        }

        textarea {
            flex: 1;
            border: none;
            border-radius: 20px;
            padding: 10px 15px;
            font-size: 14px;
            outline: none;
            resize: none;
            background-color: #ffccd5;
        }

        button {
            background-color: #990000;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            cursor: pointer;
        }

        button:hover {
            background-color: #7f0000;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <h1>User Chat</h1>

        <div class="messages" id="messages-container">
            <!-- Messages will load here -->
        </div>

        <form action="chat_user.php" method="post">
            <textarea name="message" rows="1" placeholder="Type a message..." required></textarea>
            <button type="submit">&#10148;</button>
        </form>
    </div>

    <script>
        function loadMessages() {
            fetch('load_messages.php')
                .then(response => response.text())
                .then(data => {
                    const container = document.getElementById('messages-container');
                    container.innerHTML = data;
                    container.scrollTop = container.scrollHeight;
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        setInterval(loadMessages, 1000);
        window.onload = loadMessages;
    </script>
</body>

</html>