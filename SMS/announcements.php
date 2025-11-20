<?php
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM web_announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements</title>
<style>
body { font-family: "Segoe UI", sans-serif; background:#f8f8f8; margin:0; padding:20px; }
.container { max-width:800px; margin:0 auto; }
.announcement { background:#fff; border-radius:12px; padding:15px 20px; margin-bottom:15px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
.announcement-time { font-size:12px; color:#999; margin-bottom:8px; }
.ack-btn { background:#c8102e; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; float:right; }
</style>
</head>
<body>
<div class="container">
<h2>Latest Announcements</h2>

<?php foreach($announcements as $ann): ?>
<div class="announcement">
    <div class="announcement-time"><?= date('M d, Y H:i', strtotime($ann['created_at'])) ?></div>
    <div><?= htmlspecialchars($ann['message']) ?></div>
    <!-- optional acknowledge button -->
    <form method="POST" action="acknowledge.php">
        <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
        <button type="submit" class="ack-btn">Acknowledge</button>
    </form>
</div>
<?php endforeach; ?>

</div>
</body>
</html>
