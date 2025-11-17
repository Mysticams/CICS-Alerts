<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) exit("Access denied.");

// Fetch user messages
$messages = pdo()->prepare("SELECT * FROM sms_messages WHERE user_id=? ORDER BY sent_at DESC");
$messages->execute([$_SESSION['user_id']]);
$messages = $messages->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <style>
        body { font-family: Arial; background:#fff; color:#b00; padding:20px; }
        table { border-collapse: collapse; width:100%; margin-top:20px;}
        th, td { border:1px solid #b00; padding:8px; text-align:left;}
        th { background:#b00; color:#fff; }
        tr:nth-child(even) { background:#ffe6e6; }
        .ack { color:green; font-weight:bold; }
        a { color:#b00; text-decoration:none; font-weight:bold; }
        a:hover { text-decoration:underline; }
    </style>
</head>
<body>
<h1>Your Messages</h1>
<table>
    <tr>
        <th>Message</th>
        <th>Sent At</th>
        <th>Acknowledged</th>
    </tr>
    <?php foreach($messages as $m): ?>
    <tr>
        <td><?=htmlspecialchars($m['message'])?></td>
        <td><?=htmlspecialchars($m['sent_at'])?></td>
        <td>
            <?php if($m['acknowledged']): ?>
                <span class="ack">Yes</span>
            <?php else: ?>
                <a href="acknowledge.php?token=<?=$m['ack_token']?>">Acknowledge</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
