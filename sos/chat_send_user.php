<?php
session_start();
require_once '../config.php';
$pdo = pdo();

$userId = (int)$_SESSION['user_id'] ?? 0;
$message = trim($_POST['message'] ?? '');
if ($userId && $message) {
    $stmt = $pdo->prepare("CALL insert_message(:sender,:recipient,:message,:user_id)");
    $stmt->execute([':sender'=>'user',':recipient'=>'admin',':message'=>$message,':user_id'=>$userId]);
}
