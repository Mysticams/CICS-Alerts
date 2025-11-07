<?php
require('../config.php');
$pdo = pdo();

// Fetch messages
$stmt = $pdo->query("SELECT sender, message, timestamp FROM messages ORDER BY timestamp ASC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $msg) {
    $class = $msg['sender'] === 'admin' ? 'admin' : 'user';
    echo '<div class="message '.$class.'">';
    echo '<div class="sender">'.ucfirst($msg['sender']).'</div>';
    echo '<div class="text">'.htmlspecialchars($msg['message']).'</div>';
    echo '<div class="timestamp">'.$msg['timestamp'].'</div>';
    echo '</div>';
}
?>
