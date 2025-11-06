<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['student', 'faculty', 'staff','admin'])) {
    http_response_code(403);
    exit(json_encode(['status'=>'error','msg'=>'Unauthorized']));
}

$pdo = pdo();
$sender = (int)$_SESSION['user_id'];
$receiver = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message = trim($_POST['message'] ?? '');

if (!$receiver || !$message) {
    exit(json_encode(['status'=>'error','msg'=>'Invalid input']));
}

// Prevent XSS
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

$stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?,?,?)");
if ($stmt->execute([$sender, $receiver, $message])) {
    echo json_encode(['status'=>'ok']);
} else {
    echo json_encode(['status'=>'error','msg'=>'DB error']);
}
