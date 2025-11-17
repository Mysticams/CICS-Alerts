<?php
require '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

try {
    $pdo = pdo();
    $pdo->exec("TRUNCATE TABLE activity_logs");
    echo json_encode(['success'=>true,'message'=>'All activity logs have been cleared.']);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
