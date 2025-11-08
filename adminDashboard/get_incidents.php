<?php
require_once '../config.php';
header('Content-Type: application/json');

try {
    $pdo = pdo();
    $stmt = $pdo->query("SELECT * FROM incidents ORDER BY created_at DESC");
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($incidents);
} catch (Exception $e){
    echo json_encode(['error' => $e->getMessage()]);
}
