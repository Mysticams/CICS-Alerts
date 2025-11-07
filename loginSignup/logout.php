<?php
require_once '../config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure session keys exist before unsetting
    if (isset($_SESSION['logged_in']) || isset($_SESSION['user_id']) || isset($_SESSION['user_role'])) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }
}
header("Location: login.php");
exit;
