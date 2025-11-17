<?php
// check_ack.php
header('Content-Type: application/json');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the message parameter from query string
    $message = trim($_GET['message'] ?? '');
    if (empty($message)) {
        echo json_encode([]);
        exit;
    }

    // Fetch all recipients of this message
    $stmt = $pdo->prepare("
        SELECT phone_number, acknowledged 
        FROM alert_acknowledgement
        WHERE alert_message = ?
    ");
    $stmt->execute([$message]);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($recipients);

} catch (Exception $e) {
    echo json_encode([]);
}
