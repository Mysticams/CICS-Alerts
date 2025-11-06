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
    if (!in_array($role, ['student','faculty','staff'])) {
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
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Register | BSU Auth</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header">
    <div class="logo">CICS</div>
    <h1>Create an account</h1>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="error"><?php echo htmlspecialchars(implode("<br>", $errors)); ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="form-group">
      <label>First name</label>
      <input type="text" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
      <label>Last name</label>
      <input type="text" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
      <label>BSU Email</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="xx-xxxxx@g.batstate-u.edu.ph" required>
    </div>

    <div class="form-group">
      <label>Phone (e.g., 639XXXXXXXXX)</label>
      <input type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
      <label>Role</label>
      <select name="role" class="role-select" required>
        <option value="student" <?php if(($_POST['role'] ?? '')==='student') echo 'selected'; ?>>Student</option>
        <option value="faculty" <?php if(($_POST['role'] ?? '')==='faculty') echo 'selected'; ?>>Faculty</option>
        <option value="staff" <?php if(($_POST['role'] ?? '')==='staff') echo 'selected'; ?>>Staff</option>
      </select>
    </div>

    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>

    <div class="form-group">
      <label>Confirm Password</label>
      <input type="password" name="password_confirm" required>
    </div>

    <button type="submit">Register & Send OTP</button>
  </form>

  <a class="top-link" href="login.php">Already have an account? Log in</a>
</div>
</body>
</html>
