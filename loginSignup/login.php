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
    if (!in_array($role, ['student','faculty','staff','admin'])) {
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
                header("Location: ../sos/admin.php"); // admin dashboard
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
                        header("Location: ../sos/user.php");
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
<html>
<head>
<meta charset="utf-8">
<title>Login | BSU Auth</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header"><div class="logo">CICS</div><h1>Login</h1></div>

  <?php if (!empty($errors)): ?><div class="error"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div><?php endif; ?>

  <form method="post">
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
      <label>Role</label>
      <select name="role" required>
        <option value="student" <?php if(($_POST['role'] ?? '')==='student') echo 'selected'; ?>>Student</option>
        <option value="faculty" <?php if(($_POST['role'] ?? '')==='faculty') echo 'selected'; ?>>Faculty</option>
        <option value="staff" <?php if(($_POST['role'] ?? '')==='staff') echo 'selected'; ?>>Staff</option>
        <option value="admin" <?php if(($_POST['role'] ?? '')==='admin') echo 'selected'; ?>>Admin</option>
      </select>
    </div>

    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>

    <button type="submit">Login</button>
  </form>

  <a class="top-link" href="forgot_password.php">Forgot password?</a>
  <a class="top-link" href="register.php">Create an account</a>
</div>
</body>
</html>
