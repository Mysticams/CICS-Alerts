<?php
require_once '../config.php';
header('Content-Type: application/json');

$pdo = pdo();
$id = $_GET['id'] ?? null;

try {
    if($id){
        $stmt = $pdo->prepare("SELECT * FROM incident_history WHERE id = ?");
        $stmt->execute([$id]);
        $incident = $stmt->fetch();
        echo json_encode($incident);
    } else {
        $stmt = $pdo->query("SELECT * FROM incident_history ORDER BY resolved_at DESC");
        $history = $stmt->fetchAll();
        echo json_encode($history);
    }
} catch(Exception $e){
    echo json_encode([]);
}
