<?php
require_once '../config.php';
header('Content-Type: application/json');

try {
    $pdo = pdo();

    $type = $_POST['incidentType'] ?? '';
    $description = $_POST['description'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';

    // Handle media uploads
    $mediaFiles = [];
    if (!empty($_FILES['media']['name'][0])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        foreach ($_FILES['media']['name'] as $index => $name) {
            $tmpName = $_FILES['media']['tmp_name'][$index];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newName = uniqid() . '.' . $ext;
            move_uploaded_file($tmpName, $uploadDir . $newName);
            $mediaFiles[] = 'uploads/' . $newName;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO incidents (type, description, latitude, longitude, media) VALUES (:type, :desc, :lat, :lng, :media)");
    $stmt->execute([
        ':type' => $type,
        ':desc' => $description,
        ':lat' => $latitude,
        ':lng' => $longitude,
        ':media' => json_encode($mediaFiles)
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
