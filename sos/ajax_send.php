<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'student') exit;

$pdo = pdo();
$userId = (int)$_SESSION['user_id'];
$msg = trim($_POST['message'] ?? '');

if ($msg) {
    $stmt = $pdo->prepare("INSERT INTO sos_chat (sender,sender_id,receiver_id,text) VALUES ('user',?,0,?)");
    $stmt->execute([$userId, $msg]);
    echo json_encode(['status' => 'ok']);
    exit;
}
echo json_encode(['status' => 'error']);
