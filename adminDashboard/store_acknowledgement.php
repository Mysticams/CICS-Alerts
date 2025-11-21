<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = pdo();

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if($user_id) {
    // Insert acknowledgment safely
    $stmt = $pdo->prepare("INSERT INTO sms_acknowledgements (user_id, acknowledged_at) VALUES (:user_id, NOW())");
    $stmt->execute(['user_id' => $user_id]);
    echo json_encode(['status'=>'ok']);
} else {
    echo json_encode(['status'=>'error','message'=>'No user_id provided']);
}
