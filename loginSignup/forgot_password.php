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
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | BSU Auth</title>

  <!-- Tailwind + Bootstrap -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-image: url("../img/bg.png");
      font-family: 'Poppins', sans-serif;
      background-size: fixed;
      background-repeat: no-repeat;
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
      transition: all 0.3s ease;
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

    /* 📱 Responsive Adjustments */
    @media (max-width: 1024px) {
      .container-box {
        width: 95%;
      }

      .left-panel,
      .right-panel {
        padding: 2rem;
      }

      .right-panel h2 {
        font-size: 1.6rem;
      }
    }

    @media (max-width: 768px) {
      body {
        height: auto;
        padding: 2rem 0;
      }

      .container-box {
        flex-direction: column;
        max-width: 95%;
      }

      .right-panel {
        border-top-left-radius: 0;
        border-bottom-left-radius: 1.5rem;
        padding: 2rem;
      }

      .right-panel img {
        width: 80px;
        height: 80px;
      }
    }

    @media (max-width: 480px) {
      .left-panel,
      .right-panel {
        padding: 1.5rem;
      }

      .right-panel h2 {
        font-size: 1.4rem;
      }

      .sign-btn {
        padding: 0.65rem;
      }
    }
  </style>
</head>

<body>

  <div class="container-box">

    <!-- Left Panel: Forgot Password Form -->
    <div class="left-panel">
      <h2 class="text-2xl font-bold mb-4 text-red-700">Forgot Password</h2>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div>
      <?php endif; ?>

      <?php if (!empty($info)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <div>
          <label class="block font-medium text-gray-700">BSU Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>

        <div>
          <label class="block font-medium text-gray-700">Phone (used during registration)</label>
          <input type="text" name="phone" class="form-control" required>
        </div>

        <button type="submit" class="sign-btn">Send Reset Code</button>
      </form>

      <a href="login.php" class="top-link">← Back to Login</a>
    </div>

    <!-- Right Panel: Info / Catchy Message -->
    <div class="right-panel">
      <img src="../img/bsu.png" alt="CICS Logo" class="mb-4 w-24 h-24">
      <h2>Reset Fast, Stay Alert!</h2>
      <p>We’ve got your back — reset your password and stay informed.</p>
    </div>

  </div>

</body>

</html>
