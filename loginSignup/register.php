<?php
require_once '../config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim(strtolower($_POST['email'] ?? ''));
  $phone = trim($_POST['phone'] ?? '');
  $role = $_POST['role'] ?? 'student';
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
  // Phone validation (Philippines format starting with 63 or 09)
  if (!preg_match('/^(?:63|0)?9[0-9]{9}$/', $phone)) {
    $errors[] = "Please provide a valid Philippine mobile number (e.g., 639XXXXXXXXX or 09XXXXXXXXX).";
  }
  if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
  }
  if ($password !== $password_confirm) {
    $errors[] = "Passwords do not match.";
  }
  if (!in_array($role, ['student', 'faculty', 'staff'])) {
    $errors[] = "Invalid role selected.";
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

        $insert = $db->prepare("INSERT INTO users (first_name, last_name, email, phone, role, password_hash, password_salt, otp_code, otp_expires, is_verified) VALUES (:first, :last, :email, :phone, :role, :hash, :salt, :otp, :otp_expires, 0)");
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
        $message = "Your CICS Emergency and Important Alerts System verification code is: $otp. Expires in 5 minutes.";
        $sms = send_sms_iprog($phone, $message);
        // We won't treat SMS failure as a fatal error here — but you may log it.

        $success = true;
        // Optionally redirect to OTP verification page with email param
        header("Location: verify_otp.php?email=" . urlencode($email));
        exit;
      }
    } catch (Exception $e) {
      $errors[] = "Server error: " . $e->getMessage();
    }
  }
}
?>
<?php
require_once '../config.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim(strtolower($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';
  $role = $_POST['role'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email.";
  }
  if ($password === '' || $confirm === '') {
    $errors[] = "Enter and confirm your password.";
  } elseif ($password !== $confirm) {
    $errors[] = "Passwords do not match.";
  }
  if (!in_array($role, ['student', 'faculty', 'staff'])) {
    $errors[] = "Invalid role.";
  }

  if (empty($errors)) {
    try {
      $db = pdo();
      $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->fetch()) {
        $errors[] = "Email already registered.";
      } else {
        $salt = bin2hex(random_bytes(16));
        $hash = hash('sha256', $password . $salt);
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, password_salt, role, is_verified) VALUES (:email, :hash, :salt, :role, 0)");
        $stmt->execute([':email' => $email, ':hash' => $hash, ':salt' => $salt, ':role' => $role]);
        header("Location: login.php");
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
      background-size: cover;
      background-position: center;
      font-family: 'Poppins', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
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
      width: 95%;
    }

    .left-panel {
      flex: 0 0 65%;
      padding: 3rem;
      background: #fff;
    }

    .right-panel {
      flex: 0 0 35%;
      background-color: #b91c1c;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 3rem 2rem;
    }

    /* Left Panel - Registration Form */
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

    /* Two-column form grid */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem 2rem;
    }

    .form-grid div {
      display: flex;
      flex-direction: column;
    }

    /* Right Panel - Info Section */
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

    /* Responsive Design */
    @media (max-width: 900px) {
      .container-box {
        flex-direction: column-reverse;
        max-width: 95%;
      }

      .left-panel,
      .right-panel {
        flex: 1 1 100%;
        padding: 2rem;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <div class="container-box">

    <!-- Left Panel -->
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
            <label class="block font-medium text-gray-700">Role</label>
            <select name="role" class="form-select" required>
              <option value="student" <?php if (($_POST['role'] ?? '') === 'student') echo 'selected'; ?>>Student</option>
              <option value="faculty" <?php if (($_POST['role'] ?? '') === 'faculty') echo 'selected'; ?>>Faculty</option>
              <option value="staff" <?php if (($_POST['role'] ?? '') === 'staff') echo 'selected'; ?>>Staff</option>
            </select>
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

      <a href="login.php" class="top-link">Already have an account? Log in</a>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">
      <img src="../img/bsu.png" alt="CICS Logo">
      <h2>Welcome to CICS Alerts!</h2>
      <p>Register with your personal details to use all features of the <strong>CICS Emergency & Important Alerts System</strong>. Stay informed. Stay safe!</p>
      <a href="login.php" class="signin-btn">Sign In</a>
    </div>

  </div>

</body>

</html>