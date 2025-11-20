<?php
// ---------------------- BACKEND ----------------------
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid request. Token missing.");
}

// Hash the token to match the database
$tokenHash = hash('sha256', $token);

// Find the matching record that hasn't expired
$stmt = $pdo->prepare("
    SELECT id, acknowledged, token_expires_at 
    FROM alert_acknowledgments 
    WHERE token_hash = ?
    LIMIT 1
");
$stmt->execute([$tokenHash]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    die("Invalid or expired acknowledgment link.");
}

// Check if token expired
if (strtotime($record['token_expires_at']) < time()) {
    die("This acknowledgment link has expired.");
}

// Update acknowledgment if not already acknowledged
if (!$record['acknowledged']) {
    $update = $pdo->prepare("
        UPDATE alert_acknowledgments
        SET acknowledged = 1, acknowledged_at = NOW()
        WHERE id = ?
    ");
    $update->execute([$record['id']]);
    $message = "Thank you! Your acknowledgment has been recorded.";
} else {
    $message = "You have already acknowledged this message.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acknowledgment</title>
<style>
:root {
    --red: #c8102e;
    --white: #fff;
    --gray: #f8f8f8;
    --dark: #222;
    --radius: 12px;
}
body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background-color: var(--gray);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}
.container {
    background-color: var(--white);
    border-radius: var(--radius);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 40px;
    max-width: 500px;
    text-align: center;
}
h2 { color: var(--red); margin-bottom: 20px; }
p { color: var(--dark); font-size: 16px; }
a.button {
    display: inline-block;
    margin-top: 20px;
    background-color: var(--red);
    color: var(--white);
    padding: 10px 20px;
    text-decoration: none;
    border-radius: var(--radius);
    font-weight: bold;
}
a.button:hover { background-color: #a10c24; }
</style>
</head>
<body>
<div class="container">
    <h2>Acknowledgment</h2>
    <p><?= htmlspecialchars($message) ?></p>
    <a href="send_announcement.php" class="button">Back to Announcements</a>
</div>
</body>
</html>
