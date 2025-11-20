<?php
require_once '../config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim(strtolower($_POST['email'] ?? ''));
  $phone = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';

  // Basic validations
  if ($first === '' || !preg_match('/^[A-Za-z\-\' ]{2,100}$/', $first)) {
    $errors[] = "First name is required and should contain only letters, spaces, hyphens or apostrophes.";
  }
  if ($last === '' || !preg_match('/^[A-Za-z\-\' ]{2,100}$/', $last)) {
    $errors[] = "Last name is required and should contain only letters, spaces, hyphens or apostrophes.";
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !validate_bsu_email($email)) {
    $errors[] = "Please use a valid BSU email in the form xx-xxxxx@g.batstate-u.edu.ph.";
  }
  // Phone validation
  if (!preg_match('/^(?:63|0)?9[0-9]{9}$/', $phone)) {
    $errors[] = "Please provide a valid Philippine mobile number (e.g., 639XXXXXXXXX or 09XXXXXXXXX).";
  }
  if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
  }
  if ($password !== $password_confirm) {
    $errors[] = "Passwords do not match.";
  }

  if (empty($errors)) {
    try {
      $db = pdo();
      // Check if email exists
      $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
      $stmt->bindParam(':email', $email, PDO::PARAM_STR);
      $stmt->execute();
      if ($stmt->fetch()) {
        $errors[] = "An account with that email already exists.";
      } else {
        // Create password hash
        $pw = make_password_hash($password);
        $salt = $pw['salt'];
        $hash = $pw['hash'];

        // Generate OTP
        $otp = random_int(100000, 999999);
        $otp_expires = (new DateTime())->add(new DateInterval('PT' . OTP_EXPIRY_SECONDS . 'S'))->format('Y-m-d H:i:s');
        $role = "student"; // Default role

        // FIXED INSERT
        $insert = $db->prepare("
          INSERT INTO users 
          (first_name, last_name, email, phone, role, password_hash, password_salt, otp_code, otp_expires, is_verified)
          VALUES 
          (:first, :last, :email, :phone, :role, :hash, :salt, :otp, :otp_expires, 0)
        ");
        $insert->bindParam(':first', $first);
        $insert->bindParam(':last', $last);
        $insert->bindParam(':email', $email);
        $insert->bindParam(':phone', $phone);
        $insert->bindParam(':role', $role);
        $insert->bindParam(':hash', $hash);
        $insert->bindParam(':salt', $salt);
        $insert->bindParam(':otp', $otp);
        $insert->bindParam(':otp_expires', $otp_expires);
        $insert->execute();

        // Send SMS
        $message = "Your CICS Alerts System verification code is: $otp. Expires in 5 minutes.";
        $sms = send_sms_iprog($phone, $message);

        header("Location: verify_otp.php?email=" . urlencode($email));
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
  <title>Register | CICS Alerts System</title>

  <!-- Tailwind + Bootstrap -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-image: url("../img/bg.png");
      background-size: fixed;
      background-repeat: no-repeat;
      background-position: center;
      background-attachment: fixed;
      font-family: 'Poppins', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      padding: 1rem;
    }

    .container-box {
      display: flex;
      justify-content: center;
      align-items: stretch;
      background: #fff;
      border-radius: 1.5rem;
      overflow: hidden;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      max-width: 1100px;
      width: 100%;
      flex-wrap: wrap;
    }

    .left-panel {
      flex: 1 1 60%;
      padding: 3rem;
      background: #fff;
      min-width: 320px;
    }

    .right-panel {
      flex: 1 1 40%;
      background-color: #b91c1c;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 3rem 2rem;
      min-width: 280px;
    }

    .left-panel h2 {
      color: #b91c1c;
      margin-bottom: 1.5rem;
      font-weight: 700;
      text-align: center;
    }

    .form-control,
    select {
      border-radius: 9999px;
      padding: 0.75rem 1rem;
      border: 1px solid #ccc;
    }

    .form-control:focus,
    select:focus {
      outline: none;
      border-color: #b91c1c;
      box-shadow: 0 0 5px rgba(185, 28, 28, 0.4);
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
      transform: scale(1.03);
    }

    .top-link {
      display: inline-block;
      margin-top: 1rem;
      color: #b91c1c;
      font-weight: 500;
      text-decoration: none;
      text-align: center;
      width: 100%;
    }

    .top-link:hover {
      text-decoration: underline;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem 2rem;
    }

    .form-grid div {
      display: flex;
      flex-direction: column;
    }

    .right-panel img {
      width: 90px;
      height: 90px;
      margin-bottom: 1rem;
    }

    .right-panel h2 {
      font-size: 1.8rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .right-panel p {
      color: #ffe6e6;
      margin: 1rem 0 2rem;
      font-size: 1rem;
      line-height: 1.5;
      max-width: 320px;
    }

    .signin-btn {
      border: 2px solid white;
      border-radius: 9999px;
      padding: 0.6rem 2rem;
      color: white;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .signin-btn:hover {
      background-color: white;
      color: #b91c1c;
      transform: scale(1.05);
    }

    @media (max-width: 992px) {
      .container-box {
        flex-direction: column-reverse;
        align-items: center;
      }

      .left-panel,
      .right-panel {
        flex: 1 1 100%;
        width: 100%;
        padding: 2rem 1.5rem;
      }

      .right-panel {
        border-radius: 1.5rem 1.5rem 0 0;
      }

      .left-panel {
        border-radius: 0 0 1.5rem 1.5rem;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }

      .right-panel img {
        width: 70px;
        height: 70px;
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
      }

      .container-box {
        border-radius: 1rem;
      }

      .sign-btn {
        font-size: 0.9rem;
      }

      .right-panel p {
        font-size: 0.85rem;
      }
    }
  </style>
</head>

<body>

  <div class="container-box">

    <div class="left-panel">
      <h2>Create an Account</h2>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="form-grid">
          <div>
            <label class="block font-medium text-gray-700">First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" class="form-control" required>
          </div>

          <div>
            <label class="block font-medium text-gray-700">Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" class="form-control" required>
          </div>

          <div>
            <label class="block font-medium text-gray-700">Gsuite Account</label>
            <input type="email" name="email" placeholder="xx-xxxxx@g.batstate-u.edu.ph" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" class="form-control" required>
          </div>

          <div>
            <label class="block font-medium text-gray-700">Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" class="form-control" required>
          </div>

          <div>
            <label class="block font-medium text-gray-700">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div>
            <label class="block font-medium text-gray-700">Confirm Password</label>
            <input type="password" name="password_confirm" class="form-control" required>
          </div>
        </div>

        <button type="submit" class="sign-btn mt-3">Register & Send OTP</button>
      </form>

      <a href="../index.php" class="top-link">Already have an account? Log in</a>
    </div>

    <div class="right-panel">
      <img src="../img/bsu.png" alt="CICS Logo">
      <h2>Welcome to CICS Alerts!</h2>
      <p>Register to access the <strong>CICS Emergency & Important Alerts System</strong>. Stay informed. Stay safe!</p>
      <a href="../index.php" class="signin-btn">Sign In</a>
    </div>

  </div>

</body>

</html>
