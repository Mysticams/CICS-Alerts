<?php
require_once '../config.php';
$errors = [];

// Define the single admin credentials
define('ADMIN_EMAIL', 'admin@g.batstate-u.edu.ph');
define('ADMIN_PASSWORD', 'StrongAdminPassword123!'); // CHANGE this to a strong password

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim(strtolower($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email.";
  }
  if ($password === '') {
    $errors[] = "Enter your password.";
  }
  if (!in_array($role, ['student', 'faculty', 'staff', 'admin'])) {
    $errors[] = "Invalid role.";
  }

  if (empty($errors)) {
    // --- ADMIN LOGIN FLOW ---
    if ($role === 'admin') {
      if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
        $_SESSION['user_id'] = 0; // admin has fixed id
        $_SESSION['user_role'] = 'admin';
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true);
        header("Location: ../adminDashboard/index.php"); // admin dashboard
        exit;
      } else {
        $errors[] = "Invalid admin credentials.";
      }
    } else {
      // --- NORMAL USER LOGIN ---
      try {
        $db = pdo();
        $stmt = $db->prepare("SELECT id, password_hash, password_salt, is_verified, role FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
          $errors[] = "Invalid credentials.";
        } else {
          if ($user['role'] !== $role) {
            $errors[] = "Invalid role selection for this account.";
          } elseif (!verify_password($password, $user['password_hash'], $user['password_salt'])) {
            $errors[] = "Invalid credentials.";
          } elseif (!$user['is_verified']) {
            $errors[] = "Account not verified. Please verify via OTP.";
          } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            session_regenerate_id(true);
            header("Location: ../userDashboard/index.php");
            exit;
          }
        }
      } catch (Exception $e) {
        $errors[] = "Server error: " . $e->getMessage();
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
  <title>Login | CICS Emergency Alerts</title>

  <!-- Tailwind + Bootstrap -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-image: url("../img/bg.png");
      font-family: 'Poppins', sans-serif;
      background-size:fixed;
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
      width: 90%;
      min-height: 550px;
    }

    .left-panel,
    .right-panel {
      flex: 1;
      padding: 3rem;
    }

    .left-panel {
      background-color: #fff;
    }

    .right-panel {
      background-color: #b91c1c;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      position: relative;
      border-top-right-radius: 1.5rem;
      border-bottom-right-radius: 1.5rem;
      text-align: center;
    }

    .right-panel img {
      width: 100px;
      margin-bottom: 1rem;
      max-width: 80%;
      height: auto;
    }

    .right-panel h2 {
      font-size: 1.8rem;
      font-weight: bold;
      margin-bottom: 0.75rem;
    }

    .right-panel p {
      color: #ffe6e6;
      margin-bottom: 1.5rem;
      font-size: 1rem;
      line-height: 1.6;
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

    .signup-btn {
      border: 2px solid white;
      border-radius: 9999px;
      padding: 0.6rem 2rem;
      color: white;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .signup-btn:hover {
      background-color: white;
      color: #b91c1c;
    }

    .form-control,
    select {
      border-radius: 9999px;
      padding: 0.75rem 1rem;
      font-size: 1rem;
    }

    .alert {
      font-size: 0.9rem;
      border-radius: 1rem;
      padding: 0.75rem 1rem;
    }

    /* ✅ Added for responsiveness */
    @media (max-width: 992px) {
      .container-box {
        flex-direction: column;
        width: 95%;
        min-height: auto;
      }

      .left-panel,
      .right-panel {
        padding: 2rem;
      }

      .right-panel {
        border-radius: 0 0 1.5rem 1.5rem;
      }

      .right-panel h2 {
        font-size: 1.5rem;
      }

      .right-panel p {
        font-size: 0.95rem;
      }

      body {
        height: auto;
        min-height: 100vh;
      }
    }

    @media (max-width: 576px) {
      .left-panel {
        padding: 1.5rem;
      }

      .right-panel {
        padding: 1.5rem;
      }

      .right-panel img {
        width: 80px;
      }

      h2 {
        font-size: 1.3rem !important;
      }

      .sign-btn {
        padding: 0.6rem;
        font-size: 0.95rem;
      }

      .signup-btn {
        padding: 0.5rem 1.5rem;
        font-size: 0.9rem;
      }

      .form-control,
      select {
        font-size: 0.9rem;
        padding: 0.6rem 0.8rem;
      }
    }

    @media (max-width: 400px) {
      .container-box {
        border-radius: 1rem;
      }

      .right-panel p {
        font-size: 0.85rem;
      }
    }
  </style>
</head>

<body>

  <div class="container-box">

    <!-- Left: Sign In Form -->
    <div class="left-panel">
      <h2 class="text-2xl font-bold mb-4 text-red-700">Sign In</h2>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <div>
          <label class="block font-medium text-gray-700">Email</label>
          <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div>
          <label class="block font-medium text-gray-700">Role</label>
          <select name="role" class="form-select" required>
            <option value="student" <?php if (($_POST['role'] ?? '') === 'student') echo 'selected'; ?>>Student</option>
            <option value="faculty" <?php if (($_POST['role'] ?? '') === 'faculty') echo 'selected'; ?>>Faculty</option>
            <option value="staff" <?php if (($_POST['role'] ?? '') === 'staff') echo 'selected'; ?>>Staff</option>
            <option value="admin" <?php if (($_POST['role'] ?? '') === 'admin') echo 'selected'; ?>>Admin</option>
          </select>
        </div>

        <div>
          <label class="block font-medium text-gray-700">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="sign-btn">Sign In</button>
      </form>

      <div class="flex justify-between mt-3">
        <a href="forgot_password.php" class="text-red-600 hover:underline">Forgot Password?</a>
      </div>
    </div>

    <!-- Right: Sign Up CTA -->
    <div class="right-panel">
      <img src="../img/bsu.png" alt="CICS Logo">
      <h2>Heads Up, CICS!</h2>
      <p>Stay informed, stay safe!<br>
        Join the <strong>CICS Emergency & Important Alerts System</strong> to receive real-time updates and alerts that matter most to you.</p>
      <a href="register.php" class="signup-btn">Sign Up</a>
    </div>

  </div>

</body>

</html>
