<?php
require_once '../config.php';
header('Content-Type: application/json');

$pdo = pdo();
$method = $_SERVER['REQUEST_METHOD'];

// === READ all hotlines ===
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM emergency_hotlines ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
}

// === CREATE new hotline ===
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['organization'], $data['description'], $data['phone_number'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO emergency_hotlines (organization, description, phone_number) VALUES (?, ?, ?)");
    $stmt->execute([$data['organization'], $data['description'], $data['phone_number']]);
    echo json_encode(['success' => true, 'message' => 'Hotline added successfully']);
    exit;
}

// === UPDATE hotline ===
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'], $data['organization'], $data['description'], $data['phone_number'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE emergency_hotlines SET organization=?, description=?, phone_number=? WHERE id=?");
    $stmt->execute([$data['organization'], $data['description'], $data['phone_number'], $data['id']]);
    echo json_encode(['success' => true, 'message' => 'Hotline updated successfully']);
    exit;
}

// === DELETE hotline ===
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM emergency_hotlines WHERE id=?");
    $stmt->execute([$data['id']]);
    echo json_encode(['success' => true, 'message' => 'Hotline deleted']);
    exit;
}
?>
