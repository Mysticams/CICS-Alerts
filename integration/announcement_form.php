<?php
// Fetch users from the database
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT id, first_name, last_name, phone FROM users ORDER BY first_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Announcement</title>
<style>
    :root {
        --primary-red: #c8102e;
        --secondary-white: #ffffff;
        --light-gray: #f5f5f5;
        --border-radius: 12px;
    }

    body {
        font-family: "Segoe UI", Tahoma, sans-serif;
        background-color: var(--light-gray);
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }

    .container {
        background: var(--secondary-white);
        padding: 30px 40px;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }

    h2 {
        text-align: center;
        color: var(--primary-red);
        margin-bottom: 25px;
    }

    label {
        font-weight: 600;
        color: #333;
    }

    textarea {
        width: 100%;
        border: 1px solid #ccc;
        border-radius: var(--border-radius);
        padding: 10px;
        font-size: 14px;
        margin-top: 5px;
        margin-bottom: 15px;
        resize: none;
    }

    textarea:focus {
        border-color: var(--primary-red);
        outline: none;
        box-shadow: 0 0 3px rgba(200,16,46,0.3);
    }

    .users-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ccc;
        padding: 10px;
        border-radius: var(--border-radius);
        margin-bottom: 15px;
    }

    .users-list label {
        display: block;
        margin-bottom: 5px;
        cursor: pointer;
    }

    button {
        width: 100%;
        background-color: var(--primary-red);
        color: var(--secondary-white);
        border: none;
        padding: 12px;
        border-radius: var(--border-radius);
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    button:hover {
        background-color: #a10c24;
    }

    footer {
        text-align: center;
        margin-top: 20px;
        color: #777;
        font-size: 13px;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Send New Announcement</h2>
    <form action="send_announcement.php" method="POST">
        <label>Announcement Message:</label>
        <textarea name="message" rows="4" required placeholder="Type your alert message here..."></textarea>

        <label>Select Recipients:</label>
        <div class="users-list">
            <?php foreach ($users as $user): ?>
                <label>
                    <input type="checkbox" name="recipients[]" value="<?= htmlspecialchars($user['phone']) ?>" checked>
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['phone']) ?>)
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit">🚀 Send Announcement</button>
    </form>
    <footer>Broadcast System &middot; Red & White Theme</footer>
</div>
</body>
</html>
