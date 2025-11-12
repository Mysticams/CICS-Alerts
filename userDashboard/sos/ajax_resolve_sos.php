<?php
require '../../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No user_id provided']);
    exit;
}

$userId = (int)$_POST['user_id'];
$pdo = pdo();

try {
    $stmt = $pdo->prepare("UPDATE users SET sos_active = 0 WHERE id = ?");
    $stmt->execute([$userId]);

    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
