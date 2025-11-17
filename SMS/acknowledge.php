<?php
require_once '../config.php';

$token = $_GET['token'] ?? '';
if (!$token) exit("<h2 style='color:red;text-align:center;'>Invalid token.</h2>");

$stmt = pdo()->prepare("SELECT m.id, u.first_name FROM sms_messages m JOIN users u ON m.user_id=u.id WHERE ack_token=? LIMIT 1");
$stmt->execute([$token]);
$message = $stmt->fetch();
if (!$message) exit("<h2 style='color:red;text-align:center;'>Invalid or expired token.</h2>");

// Mark as acknowledged
pdo()->prepare("UPDATE sms_messages SET acknowledged=1 WHERE id=?")->execute([$message['id']]);

echo "<h2 style='color:green;text-align:center;'>Thank you {$message['first_name']}! Your acknowledgement has been recorded.</h2>";
