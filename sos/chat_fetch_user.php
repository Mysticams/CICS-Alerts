<?php
session_start();
require_once '../config.php';
$pdo = pdo();

$userId = (int)$_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM messages WHERE user_id=:user_id ORDER BY sent_at ASC");
$stmt->execute([':user_id'=>$userId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
