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
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP | CICS Alerts System</title>

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
      padding: 1rem;
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
      width: 100%;
    }

    .left-panel,
    .right-panel {
      flex: 1;
      padding: 3rem;
    }

    /* Left (White Panel) */
    .left-panel {
      background-color: #fff;
    }

    /* Right (Red Panel) */
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
      min-height: 100%;
    }

    .right-panel h2 {
      font-size: 1.8rem;
      font-weight: bold;
    }

    .right-panel p {
      color: #ffe6e6;
      margin-bottom: 1.5rem;
      font-size: 1rem;
      line-height: 1.5;
      padding: 0 1rem;
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

    .alert {
      border-radius: 1rem;
    }

    /* ✅ RESPONSIVENESS */
    @media (max-width: 992px) {
      .container-box {
        max-width: 95%;
      }

      .left-panel,
      .right-panel {
        padding: 2rem;
      }
    }

    @media (max-width: 768px) {
      .container-box {
        flex-direction: column;
        width: 100%;
        max-width: 500px;
      }

      .right-panel {
        border-top-left-radius: 0;
        border-bottom-left-radius: 1.5rem;
        padding: 2rem 1.5rem;
      }

      .left-panel {
        padding: 2rem 1.5rem;
      }

      .right-panel img {
        width: 60px;
        height: 60px;
      }

      .right-panel h2 {
        font-size: 1.5rem;
      }

      .right-panel p {
        font-size: 0.95rem;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 0.5rem;
        height: auto;
      }

      .container-box {
        max-width: 100%;
        border-radius: 1rem;
      }

      .left-panel,
      .right-panel {
        padding: 1.5rem;
      }

      .sign-btn {
        font-size: 0.95rem;
      }
    }
  </style>
</head>

<body>

  <div class="container-box">

    <!-- Left Panel: Verify OTP Form -->
    <div class="left-panel">
      <h2 class="text-2xl font-bold mb-4 text-red-700">Verify Your Account</h2>

      <?php if ($success): ?>
        <div class="alert alert-success">
          Account verified successfully.
          <a href="../index.php" class="fw-semibold text-red-700 text-decoration-none">Log in now</a>
        </div>
      <?php else: ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

          <div>
            <label class="block font-medium text-gray-700">6-digit code sent to your phone</label>
            <input type="text" name="otp" maxlength="6" class="form-control" value="<?php echo htmlspecialchars($_POST['otp'] ?? ''); ?>" required>
          </div>

          <button type="submit" class="sign-btn">Verify</button>
        </form>

        <div class="small mt-3">
          Didn't receive SMS?
          <a href="resend_otp.php?email=<?php echo urlencode($email); ?>" class="text-red-700 fw-semibold text-decoration-none">Resend code</a>
        </div>

        <a href="register.php" class="top-link d-block">← Back to Register</a>
      <?php endif; ?>
    </div>

    <!-- Right Panel: Info / Catchy Message -->
    <div class="right-panel">
      <img src="../img/bsu.png" alt="CICS Logo" class="mb-4 w-24 h-24">
      <h2>Verify & Stay Safe!</h2>
      <p>Enter your 6-digit code to activate your <strong>CICS Emergency & Important Alerts</strong> account. Stay informed. Stay protected.</p>
    </div>

  </div>

</body>

</html>
