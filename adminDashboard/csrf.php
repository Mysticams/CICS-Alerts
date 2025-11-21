<?php
// CSRF token validation (include this file on any action pages like send_sms, send_email, etc.)
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die("⚠️ CSRF VALIDATION FAILED. Request Blocked!");
}
?>
