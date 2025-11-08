<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false, 'message'=>'Invalid request']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !$status) {
    echo json_encode(['success'=>false, 'message'=>'Missing parameters']);
    exit;
}

$pdo = pdo();

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get current incident details
    $stmt = $pdo->prepare("SELECT * FROM incidents WHERE id=?");
    $stmt->execute([$id]);
    $incident = $stmt->fetch();
    if (!$incident) throw new Exception("Incident not found");

    // Update status in incidents table
    $stmt = $pdo->prepare("UPDATE incidents SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);

    // Insert into history table (optional, create table if not exists)
    $stmt = $pdo->prepare("INSERT INTO incident_history (incident_id, type, description, status, latitude, longitude, media, resolved_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $incident['id'],
        $incident['type'],
        $incident['description'],
        $status,
        $incident['latitude'],
        $incident['longitude'],
        $incident['media']
    ]);

    $pdo->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
