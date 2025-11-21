<?php
// SECURE SESSION CHECK
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Only admin can access
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements</title>

<!-- Font & Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://unpkg.com/feather-icons"></script>


<style>
:root {
    --main-red: #d32f2f;
    --light-red: #ffefef;
    --dark-red: #b71c1c;
    --soft-shadow: rgba(0, 0, 0, 0.15);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
}

body {
    background: #fff;
    color: #222;
    display: flex;
    min-height: 100vh;
}

.container-wrapper {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin-left: 250px; /* Sidebar width */
    margin-top: 70px;   /* Navbar height */
    padding: 20px;
}

.top-bar {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px 15px;
    background: var(--main-red);
    color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 12px var(--soft-shadow);
    margin-bottom: 25px;
}

.top-bar h1 {
    font-weight: 700;
    font-size: 28px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    align-items: stretch;
}

/* Action Cards */
.card {
    padding: 35px 20px;
    border-radius: 18px;
    text-align: center;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    box-shadow: 0 4px 18px var(--soft-shadow);
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 220px;
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    transform: rotate(25deg);
    transition: all 0.5s ease;
}

.card.action {
    background: #fff;
}

.card.action .icon {
    color: var(--main-red);
}

.card.action::before {
    background: linear-gradient(120deg, rgba(211,47,47,0.2), rgba(255,234,234,0.2));
}

.card.action:hover::before {
    transform: rotate(0deg);
}

.card.action:hover {
    transform: translateY(-8px);
    border-color: var(--main-red);
    box-shadow: 0 8px 24px var(--soft-shadow);
}

.card.action:hover .icon {
    color: var(--dark-red);
}

.icon {
    font-size: 55px;
    margin-bottom: 15px;
    transition: 0.3s ease-in-out;
}

.card h3 {
    margin-bottom: 8px;
    font-size: 20px;
    font-weight: 700;
    z-index: 1;
}

.card p {
    font-size: 14px;
    color: #555;
    z-index: 1;
}

.card button {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    border: none;
    background: transparent;
    cursor: pointer;
}

@media (max-width: 1024px) {
    .container-wrapper { margin-left: 0; margin-top: 90px; padding: 15px; }
}

@media (max-width: 600px) {
    .top-bar h1 { font-size: 22px; }
    .icon { font-size: 45px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<custom-navbar></custom-navbar>

<!-- Sidebar -->
<custom-sidebar></custom-sidebar>

<div class="container-wrapper">

    <div class="top-bar">
        <h1><i class="fa-solid fa-bullhorn"></i> Announcements</h1>
    </div>

    <div class="container">

        <!-- Action Cards Only -->
        <form method="POST" action="announcement_form_sms.php" class="card action">
            <i class="fa-solid fa-mobile-screen-button icon"></i>
            <h3>Send SMS</h3>
            <p>Notify users via text message.</p>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <button type="submit"></button>
        </form>

        <form method="POST" action="send_email.php" class="card action">
            <i class="fa-solid fa-envelope-circle-check icon"></i>
            <h3>Send Email</h3>
            <p>Send a formal email notification.</p>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <button type="submit"></button>
        </form>

        <form method="POST" action="announcement_form.php" class="card action">
            <i class="fa-solid fa-globe icon"></i>
            <h3>Web Announcement</h3>
            <p>Post an announcement on the platform.</p>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <button type="submit"></button>
        </form>

    </div>

</div>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>
</body>
</html>
