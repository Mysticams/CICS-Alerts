<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Admin access check
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    exit("Access denied. Please login as admin.");
}


// Fetch messages
$messages = pdo()->query("SELECT m.id, u.first_name, u.last_name, u.phone, m.message, m.sent_at, m.acknowledged
                          FROM sms_messages m
                          JOIN users u ON m.user_id = u.id
                          ORDER BY m.sent_at DESC")->fetchAll();

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $user_id = (int)$_POST['user_id'];
    $message_text = trim($_POST['message']);
    $stmt = pdo()->prepare("SELECT phone, first_name FROM users WHERE id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $ack_token = bin2hex(random_bytes(32));
        pdo()->prepare("INSERT INTO sms_messages (user_id, message, ack_token) VALUES (?, ?, ?)")
               ->execute([$user_id, $message_text, $ack_token]);
        $ack_link = "http://yourdomain.com/acknowledge.php?token=$ack_token";
        send_sms_iprog($user['phone'], "$message_text\nClick to acknowledge: $ack_link");
        header("Location: admin_dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial; background:#fff; color:#b00; padding:20px; }
        table { border-collapse: collapse; width:100%; margin-top:20px;}
        th, td { border:1px solid #b00; padding:8px; text-align:left;}
        th { background:#b00; color:#fff; }
        tr:nth-child(even) { background:#ffe6e6; }
        .ack { color:green; font-weight:bold; }
        form { margin-top:20px; background:#ffe6e6; padding:15px; border-radius:5px; }
        input, select, textarea { padding:5px; margin:5px 0; width:100%; }
        button { background:#b00; color:#fff; border:none; padding:10px 20px; cursor:pointer; }
        button:hover { background:#900; }
    </style>
</head>
<body>
<h1>Admin Dashboard</h1>

<form method="POST">
    <h3>Send SMS Message</h3>
    <label>User:</label>
    <select name="user_id" required>
        <?php
        $users = pdo()->query("SELECT id, first_name, last_name FROM users")->fetchAll();
        foreach($users as $u) {
            echo "<option value='{$u['id']}'>{$u['first_name']} {$u['last_name']}</option>";
        }
        ?>
    </select>
    <label>Message:</label>
    <textarea name="message" required></textarea>
    <button type="submit" name="send">Send SMS</button>
</form>

<h3>Sent Messages</h3>
<table>
    <tr>
        <th>User</th>
        <th>Phone</th>
        <th>Message</th>
        <th>Sent At</th>
        <th>Acknowledged</th>
    </tr>
    <?php foreach($messages as $m): ?>
    <tr>
        <td><?=htmlspecialchars($m['first_name'].' '.$m['last_name'])?></td>
        <td><?=htmlspecialchars($m['phone'])?></td>
        <td><?=htmlspecialchars($m['message'])?></td>
        <td><?=htmlspecialchars($m['sent_at'])?></td>
        <td><?= $m['acknowledged'] ? '<span class="ack">Yes</span>' : 'No' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
