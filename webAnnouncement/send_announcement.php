<?php
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $priority = $_POST['priority'] ?? 'Medium';
    $selectedRecipients = $_POST['recipients'] ?? [];

    if ($message !== '' && !empty($selectedRecipients)) {
        // Insert announcement first
        $stmt = $pdo->prepare("INSERT INTO web_announcements (message, priority) VALUES (:msg, :priority)");
        $stmt->execute(['msg'=>$message, 'priority'=>$priority]);
        $announcement_id = $pdo->lastInsertId();

        if ($announcement_id) {
            $userIds = [];
            foreach ($selectedRecipients as $role => $ids) {
                foreach ($ids as $id) $userIds[] = $id;
            }

            $stmt = $pdo->prepare("INSERT INTO announcement_acknowledgements (user_id, announcement_id, web_status) VALUES (:uid, :aid, 'Sent')");
            foreach ($userIds as $uid) {
                $stmt->execute(['uid'=>$uid, 'aid'=>$announcement_id]);
            }
        }
    }

    header("Location: announcement_results.php");
    exit();
}
