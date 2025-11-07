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
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | BSU Auth</title>

  <!-- Tailwind + Bootstrap -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-image: url("../img/bg.png");
      font-family: 'Poppins', sans-serif;
      background-size: cover;
      background-position: center;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .container-box {
      display: flex;
      flex-direction: row;
      justify-content: center;
      align-items: stretch;
      background: #fff;
      border-radius: 1.5rem;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      max-width: 900px;
      width: 90%;
    }

    .left-panel,
    .right-panel {
      flex: 1;
      padding: 3rem;
    }

    /* Left (White) */
    .left-panel {
      background-color: #fff;
    }

    /* Right (Red) */
    .right-panel {
      background-color: #b91c1c;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      border-top-right-radius: 1.5rem;
      border-bottom-right-radius: 1.5rem;
      text-align: center;
    }

    .right-panel h2 {
      font-size: 1.8rem;
      font-weight: bold;
    }

    .right-panel p {
      color: #ffe6e6;
      margin-bottom: 1.5rem;
      max-width: 300px;
    }

    .sign-btn {
      background-color: #b91c1c;
      color: white;
      border-radius: 9999px;
      width: 100%;
      padding: 0.75rem;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
    }

    .sign-btn:hover {
      background-color: #dc2626;
    }

    .form-control {
      border-radius: 9999px;
      padding: 0.75rem 1rem;
    }

    .top-link {
      display: inline-block;
      margin-top: 1rem;
      color: #b91c1c;
      font-weight: 500;
      text-decoration: none;
    }

    .top-link:hover {
      text-decoration: underline;
    }

    .logo {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
    }

    .logo img {
      width: 70px;
      height: 70px;
      margin-right: 10px;
    }

    .success {
      background-color: #dcfce7;
      color: #166534;
      padding: 1rem;
      border-radius: 0.75rem;
      font-weight: 500;
    }

    .success a {
      color: #b91c1c;
      font-weight: 600;
      text-decoration: none;
    }

    .success a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .container-box {
        flex-direction: column;
        max-width: 95%;
      }

      .right-panel {
        border-top-left-radius: 0;
        border-bottom-left-radius: 1.5rem;
      }
    }
  </style>
</head>

<body>

  <div class="container-box">

    <!-- Left Panel: Reset Form -->
    <div class="left-panel">
      <div class="logo">
        <img src="../img/bsu.png" alt="BSU Logo">
        <h2 class="text-2xl font-bold text-red-700">Reset Password</h2>
      </div>

      <?php if ($success): ?>
        <div class="success">
          Password reset successful. <a href="login.php">Log in</a>
        </div>
      <?php else: ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

          <div>
            <label class="block font-medium text-gray-700">6-digit Code</label>
            <input type="text" name="otp" maxlength="6" class="form-control" required>
          </div>

          <div>
            <label class="block font-medium text-gray-700">New Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div>
            <label class="block font-medium text-gray-700">Confirm Password</label>
            <input type="password" name="password_confirm" class="form-control" required>
          </div>

          <button type="submit" class="sign-btn">Reset Password</button>
        </form>

        <a href="forgot_password.php" class="top-link">← Back to Forgot Password</a>
      <?php endif; ?>
    </div>

    <!-- Right Panel: Catchy Message -->
    <div class="right-panel">
      <img src="../img/bsu.png" alt="CICS Logo" class="mb-4 w-24 h-24">
      <h2>Back on Track!</h2>
      <p>Reset your password and get back to your CICS Emergency & Alerts System in no time.</p>
    </div>

  </div>

</body>

</html>