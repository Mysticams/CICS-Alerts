<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['student', 'faculty', 'staff', 'admin'])) {
    http_response_code(403);
    exit(json_encode(['status'=>'error','msg'=>'Unauthorized']));
}

$pdo = pdo();
$userId = (int)$_SESSION['user_id'];
$otherId = isset($_GET['other_id']) ? (int)$_GET['other_id'] : 0;

if (!$otherId) exit(json_encode(['status'=>'error','msg'=>'Invalid input']));

// Fetch messages
$stmt = $pdo->prepare("SELECT cm.*, u.first_name, u.last_name 
                       FROM chat_messages cm
                       LEFT JOIN users u ON cm.sender_id = u.id
                       WHERE (cm.sender_id=? AND cm.receiver_id=?) 
                          OR (cm.sender_id=? AND cm.receiver_id=?) 
                       ORDER BY cm.created_at ASC");
$stmt->execute([$userId, $otherId, $otherId, $userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format messages with name and readable time
foreach ($messages as &$msg) {
    $msg['sender_name'] = $msg['first_name'] . ' ' . $msg['last_name'];
    $msg['time'] = date('Y-m-d H:i', strtotime($msg['created_at']));
    unset($msg['first_name'], $msg['last_name']);
}

echo json_encode(['status'=>'ok','messages'=>$messages]);
