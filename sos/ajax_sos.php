<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$pdo = pdo();
$userId = (int)$_SESSION['user_id'];

// -----------------------------
// 1. User updates location & SOS
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
    $sos = isset($_POST['sos']) ? (int)$_POST['sos'] : 0;

    // Reset admin override when user changes SOS themselves
    $stmt = $pdo->prepare("UPDATE users SET lat = ?, lng = ?, sos_active = ?, admin_override = 0 WHERE id = ?");
    $stmt->execute([$lat, $lng, $sos, $userId]);

    echo json_encode(['status' => 'ok']);
    exit;
}

// -----------------------------
// 2. Admin resolves a user's SOS
// -----------------------------
if (isset($_GET['resolve_user'])) {
    $targetId = (int)$_GET['resolve_user'];
    $stmt = $pdo->prepare("UPDATE users SET sos_active = 0, admin_override = 1 WHERE id = ?");
    $stmt->execute([$targetId]);

    echo json_encode(['status' => 'resolved']);
    exit;
}

// -----------------------------
// 3. Regular GET — return current SOS status
// -----------------------------
$stmt = $pdo->prepare("SELECT sos_active, admin_override FROM users WHERE id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'sos_active' => $row ? (int)$row['sos_active'] : 0,
    'forced_by_admin' => $row ? (int)($row['admin_override'] ?? 0) : 0
]);
exit;
