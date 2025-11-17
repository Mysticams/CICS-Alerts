<?php
require_once 'config.php';

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

// Define the single admin credentials
define('ADMIN_EMAIL', 'admin@g.batstate-u.edu.ph');
define('ADMIN_PASSWORD', 'StrongAdminPassword123!'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }
    if ($password === '') {
        $errors[] = "Enter your password.";
    }

    if (empty($errors)) {
        // Admin login
        if ($email === ADMIN_EMAIL) {
            if ($password === ADMIN_PASSWORD) {
                $_SESSION['user_id'] = 0;
                $_SESSION['user_role'] = 'admin';
                $_SESSION['logged_in'] = true;
                session_regenerate_id(true);
                header("Location: adminDashboard/index.php");
                exit;
            } else {
                $errors[] = "Invalid admin password.";
            }
        }

        // Normal users login
        try {
            $db = pdo();
            $stmt = $db->prepare("
                SELECT id, password_hash, password_salt, is_verified, role 
                FROM users 
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = "Invalid email or password.";
            } else {
                if (!verify_password($password, $user['password_hash'], $user['password_salt'])) {
                    $errors[] = "Invalid email or password.";
                } elseif (!$user['is_verified']) {
                    $errors[] = "Account not verified. Please verify via OTP.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    session_regenerate_id(true);
                    header("Location: userDashboard/index.php");
                    exit;
                }
            }
        } catch (Exception $e) {
            $errors[] = "Server error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | CICS Emergency Alerts</title>
<!-- Tailwind + Bootstrap -->
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background-image: url("img/bg.png");
    font-family: 'Poppins', sans-serif;
    background-size: cover;
    background-position: center;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}

.container-box {
    display: flex;
    flex-direction: row;
    background: #fff;
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    max-width: 900px;
    width: 90%;
}

.left-panel, .right-panel {
    flex: 1;
    padding: 3rem;
}

.left-panel {
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right-panel {
    background: #b91c1c;
    color: #fff;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 3rem;
}

.right-panel img {
    width: 120px;
    margin-bottom: 1rem;
}

.right-panel h2 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.75rem;
}

.right-panel p {
    font-size: 1rem;
    line-height: 1.6;
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
    transition: 0.3s;
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
    transition: 0.3s;
    text-decoration: none;
}

.signup-btn:hover {
    background-color: white;
    color: #b91c1c;
}

.form-control {
    border-radius: 9999px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
}

.alert {
    font-size: 0.9rem;
    border-radius: 1rem;
    padding: 0.75rem 1rem;
}

/* Responsive */
@media (max-width: 992px) {
    .container-box {
        flex-direction: column;
    }
    .right-panel {
        border-radius: 0 0 1.5rem 1.5rem;
        padding: 2rem;
    }
}

@media (max-width: 576px) {
    .left-panel, .right-panel {
        padding: 1.5rem;
    }
    .right-panel img {
        width: 80px;
    }
    .right-panel h2 {
        font-size: 1.5rem;
    }
    .right-panel p {
        font-size: 0.9rem;
    }
}
</style>
</head>

<body>
<div class="container-box">

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <h2 class="text-2xl font-bold mb-4 text-red-700">Sign In</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= implode("<br>", $errors); ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block font-medium text-gray-700">Email</label>
                <input type="email" name="email" class="form-control" required
                    value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div>
                <label class="block font-medium text-gray-700">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="sign-btn">Sign In</button>
        </form>

        <div class="mt-3 text-center">
            <a href="loginSignup/forgot_password.php" class="text-red-600 hover:underline">Forgot Password?</a>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <img src="img/bsu.png" alt="CICS Logo">
        <h2>Heads Up, CICS!</h2>
        <p>Stay informed with real-time alerts from the CICS Emergency & Important Alerts System.</p>
        <a href="loginSignup/register.php" class="signup-btn mt-2">Sign Up</a>
    </div>

</div>
</body>
</html>
