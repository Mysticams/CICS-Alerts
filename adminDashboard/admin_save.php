<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Only allow admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Path to settings file
$settingsFile = __DIR__ . '/admin_settings.json';
$admin = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

// Sanitize POST data
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : ($admin['first_name'] ?? 'System');
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : ($admin['last_name'] ?? 'Administrator');
$email = isset($_POST['email']) ? trim($_POST['email']) : ($admin['email'] ?? 'admin@g.batstate-u.edu.ph');
$dark_mode = isset($_POST['dark_mode']) ? intval($_POST['dark_mode']) : ($admin['dark_mode'] ?? 0);
$allow_notifications = isset($_POST['allow_notifications']) ? intval($_POST['allow_notifications']) : ($admin['allow_notifications'] ?? 1);
$show_system_logs = isset($_POST['show_system_logs']) ? intval($_POST['show_system_logs']) : ($admin['show_system_logs'] ?? 0);
$maintenance_mode = isset($_POST['maintenance_mode']) ? intval($_POST['maintenance_mode']) : ($admin['maintenance_mode'] ?? 0);

// Handle profile picture upload
$profile_pic = $admin['profile_pic'] ?? null;

if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileTmp = $_FILES['profilePic']['tmp_name'];
    $fileExt = pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION);
    $fileName = 'profile_' . time() . '.' . $fileExt;
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmp, $destPath)) {
        $profile_pic = $fileName;
    }
}

// Save settings
$adminSettings = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'dark_mode' => $dark_mode,
    'allow_notifications' => $allow_notifications,
    'show_system_logs' => $show_system_logs,
    'maintenance_mode' => $maintenance_mode,
    'profile_pic' => $profile_pic
];

if (file_put_contents($settingsFile, json_encode($adminSettings, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully!',
        'profile_pic' => $profile_pic
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save settings.'
    ]);
}
