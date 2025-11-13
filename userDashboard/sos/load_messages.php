<?php
require('../../config.php');

//Start session safely (avoid duplicate warnings)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = pdo();

//Determine whose messages to load
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user') {
    // For regular user, get their own messages
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT sender, message, timestamp 
        FROM messages 
        WHERE user_id = :user_id 
        ORDER BY timestamp ASC
    ");
    $stmt->execute([':user_id' => $userId]);

} elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    // For admin, check if viewing a specific user's chat
    if (isset($_GET['user_id'])) {
        $userId = (int)$_GET['user_id'];

        $stmt = $pdo->prepare("
            SELECT sender, message, timestamp 
            FROM messages 
            WHERE user_id = :user_id 
            ORDER BY timestamp ASC
        ");
        $stmt->execute([':user_id' => $userId]);
    } else {
        // No user selected yet
        echo "<div style='text-align:center; color:#777;'>Select a user to start chatting.</div>";
        exit;
    }
} else {
    // Not logged in or invalid role
    echo "<div style='text-align:center; color:red;'>Unauthorized access.</div>";
    exit;
}

//Display messages
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $msg) {
    $class = $msg['sender'] === 'admin' ? 'admin' : 'user';
    echo '<div class="message ' . $class . '">';
    echo '<div class="sender">' . ucfirst($msg['sender']) . '</div>';
    echo '<div class="text">' . htmlspecialchars($msg['message']) . '</div>';
    echo '<div class="timestamp">' . $msg['timestamp'] . '</div>';
    echo '</div>';
}
?>
