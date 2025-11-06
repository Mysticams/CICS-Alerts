<?php
require_once '../config.php';
$errors = [];
$success = false;
$email = $_GET['email'] ?? ($_POST['email'] ?? '');
if (!$email) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    if (!preg_match('/^\d{6}$/', $otp)) $errors[] = "Enter a 6-digit code.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $password_confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $db = pdo();
        $stmt = $db->prepare("SELECT id, otp_code, otp_expires FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
            $errors[] = "Account not found.";
        } else {
            $now = new DateTime();
            $expires = new DateTime($user['otp_expires'] ?? '1970-01-01');
            if ($now > $expires) {
                $errors[] = "OTP expired. Request a new one.";
            } elseif (!hash_equals((string)$user['otp_code'], $otp)) {
                $errors[] = "Invalid code.";
            } else {
                $pw = make_password_hash($password);
                $update = $db->prepare("UPDATE users SET password_hash = :hash, password_salt = :salt, otp_code = NULL, otp_expires = NULL WHERE id = :id");
                $update->bindParam(':hash', $pw['hash']);
                $update->bindParam(':salt', $pw['salt']);
                $update->bindParam(':id', $user['id']);
                $update->execute();
                $success = true;
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Reset Password | BSU Auth</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header"><div class="logo">BSU</div><h1>Reset password</h1></div>

  <?php if ($success): ?>
    <div class="success">Password reset successful. <a href="login.php">Log in</a></div>
  <?php else: ?>
    <?php if (!empty($errors)): ?><div class="error"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
      <div class="form-group"><label>6-digit code</label><input type="text" name="otp" maxlength="6" required></div>
      <div class="form-group"><label>New password</label><input type="password" name="password" required></div>
      <div class="form-group"><label>Confirm password</label><input type="password" name="password_confirm" required></div>
      <button type="submit">Reset password</button>
    </form>
    <div class="small"><a href="forgot_password.php">Resend code</a></div>
  <?php endif; ?>
</div>
</body>
</html>
