<?php
require_once '../config.php';
$errors = [];
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }
    if (!preg_match('/^(?:63|0)?9[0-9]{9}$/', $phone)) {
        $errors[] = "Enter the phone number used on the account.";
    }
    if (empty($errors)) {
        try {
            $db = pdo();
            $stmt = $db->prepare("SELECT id, phone FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
            if (!$user) {
                $errors[] = "No account found with that email.";
            } elseif ($user['phone'] !== $phone) {
                $errors[] = "Phone number does not match our records.";
            } else {
                // Generate OTP & expiry
                $otp = random_int(100000, 999999);
                $otp_expires = (new DateTime())->add(new DateInterval('PT' . OTP_EXPIRY_SECONDS . 'S'))->format('Y-m-d H:i:s');
                $update = $db->prepare("UPDATE users SET otp_code = :otp, otp_expires = :otp_expires WHERE id = :id");
                $update->bindParam(':otp', $otp);
                $update->bindParam(':otp_expires', $otp_expires);
                $update->bindParam(':id', $user['id']);
                $update->execute();

                $message = "Your CICS Emergency and Important Alerts System password reset code is: $otp. Expires in 5 minutes.";
                send_sms_iprog($user['phone'], $message);

                // Redirect to reset page
                header("Location: reset_password.php?email=" . urlencode($email));
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Server error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Forgot Password | BSU Auth</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header"><div class="logo">BSU</div><h1>Forgot password</h1></div>
  <?php if (!empty($errors)): ?><div class="error"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div><?php endif; ?>
  <?php if ($info): ?><div class="success"><?php echo htmlspecialchars($info); ?></div><?php endif; ?>

  <form method="post">
    <div class="form-group"><label>BSU Email</label><input type="email" name="email" required></div>
    <div class="form-group"><label>Phone (used during registration)</label><input type="text" name="phone" required></div>
    <button type="submit">Send reset code</button>
  </form>

  <a class="top-link" href="login.php">Back to login</a>
</div>
</body>
</html>
