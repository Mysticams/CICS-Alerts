<?php
require '../../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$pdo = pdo();
$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = trim($_POST['message']);
    $receiver = ($role === 'admin') ? 'user' : 'admin';
    $stmt = $pdo->prepare("INSERT INTO chat_messages (sender, receiver, user_id, message, ts) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())");
    $stmt->execute([$role, $receiver, $userId, $msg]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Handle fetching messages
$since = isset($_GET['since']) ? (int)$_GET['since'] : 0;

if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE ts > ? ORDER BY ts ASC");
    $stmt->execute([$since]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE user_id = ? AND ts > ? ORDER BY ts ASC");
    $stmt->execute([$userId, $since]);
}

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['status' => 'ok', 'messages' => $messages]);
