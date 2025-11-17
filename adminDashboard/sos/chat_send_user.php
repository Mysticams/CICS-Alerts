<?php
session_start();
require_once '../../config.php';
$pdo = pdo();

if (!isset($_SESSION['logged_in'], $_SESSION['user_role'], $_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(403); echo "Unauthorized"; exit;
}

$userId = (int)$_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');
if ($message === '') { http_response_code(400); echo "Empty message"; exit; }

$stmt = $pdo->prepare("
    INSERT INTO messages (sender, recipient, message, user_id, timestamp)
    VALUES (:sender, :recipient, :message, :user_id, NOW())
");
$stmt->execute([
    ':sender'=>'user',
    ':recipient'=>'admin',
    ':message'=>$message,
    ':user_id'=>$userId
]);
echo "Message sent";
