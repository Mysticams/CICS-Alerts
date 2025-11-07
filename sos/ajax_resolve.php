<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') exit;

$pdo = pdo();
$userId = (int)($_POST['user_id'] ?? 0);
if ($userId) {
    $stmt = $pdo->prepare("UPDATE users SET sos_active = 0 WHERE id = ?");
    $stmt->execute([$userId]);
}

echo json_encode(['status' => 'ok']);
