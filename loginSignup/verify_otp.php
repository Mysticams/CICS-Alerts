<?php
require_once '../config.php';
$errors = [];
$success = false;
$email = $_GET['email'] ?? ($_POST['email'] ?? '');

if (!$email) {
    header("Location: register.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    if (!preg_match('/^\d{6}$/', $otp)) {
        $errors[] = "Enter a 6-digit code.";
    } else {
        $db = pdo();
        $stmt = $db->prepare("SELECT id, otp_code, otp_expires, is_verified FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
            $errors[] = "Account not found.";
        } elseif ($user['is_verified']) {
            $errors[] = "Account is already verified. You can log in.";
        } else {
            $now = new DateTime();
            $expires = new DateTime($user['otp_expires'] ?? '1970-01-01');
            if ($now > $expires) {
                $errors[] = "OTP expired. Request a new one.";
            } elseif (!hash_equals((string)$user['otp_code'], $otp)) {
                $errors[] = "Invalid code.";
            } else {
                $update = $db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = :id");
                $update->bindParam(':id', $user['id'], PDO::PARAM_INT);
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
<title>Verify OTP | BSU Auth</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header"><div class="logo">BSU</div><h1>Enter verification code</h1></div>
  <?php if ($success): ?>
    <div class="success">Account verified successfully. <a href="login.php">Log in now</a></div>
  <?php else: ?>
    <?php if (!empty($errors)): ?><div class="error"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
      <div class="form-group">
        <label>6-digit code sent to your phone</label>
        <input type="text" name="otp" maxlength="6" value="<?php echo htmlspecialchars($_POST['otp'] ?? ''); ?>" required>
      </div>
      <button type="submit">Verify</button>
    </form>
    <div class="small">Didn't receive SMS? <a href="resend_otp.php?email=<?php echo urlencode($email); ?>">Resend code</a></div>
  <?php endif; ?>
</div>
</body>
</html>
