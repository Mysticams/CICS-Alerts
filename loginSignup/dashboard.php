<?php
require_once '../config.php';
// Ensure session is set
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$db = pdo();
$stmt = $db->prepare("SELECT first_name, last_name, email, role FROM users WHERE id = :id LIMIT 1");
$stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();
if (!$user) {
    // Invalid session
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Dashboard | BSU Auth</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header"><div class="logo">BSU</div><h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?></h1></div>

  <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
  <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
  <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>

  <form action="logout.php" method="post">
    <button type="submit">Logout</button>
  </form>

  <div class="footer">BSU Auth • Secure Session</div>
</div>
</body>
</html>
