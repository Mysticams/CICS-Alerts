<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'])) {
    
    $announcementId = (int)$_POST['announcement_id'];
    $userId = $_SESSION['user_id'] ?? null; // Make sure user is logged in
    
    if (!$userId) {
        header("Location: index.php"); // redirect if not logged in
        exit;
    }
    
    // Insert acknowledgement into a separate table
    $stmt = $pdo->prepare("INSERT INTO announcements_acknowledged (announcement_id, user_id, acknowledged_at)
                           VALUES (:announcement_id, :user_id, NOW())
                           ON DUPLICATE KEY UPDATE acknowledged_at = NOW()");
    $stmt->execute([
        ':announcement_id' => $announcementId,
        ':user_id' => $userId
    ]);

    // Redirect back to the notifications page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
