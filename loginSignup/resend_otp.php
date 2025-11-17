<?php
require_once '../config.php';
$email = $_GET['email'] ?? '';
if (!$email) {
    header("Location: register.php");
    exit;
}
try {
    $db = pdo();
    $stmt = $db->prepare("SELECT id, phone, is_verified FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception("Account not found.");
    }
    if ($user['is_verified']) {
        header("Location: ../index.php");
        exit;
    }
    $otp = random_int(100000, 999999);
    $otp_expires = (new DateTime())->add(new DateInterval('PT' . OTP_EXPIRY_SECONDS . 'S'))->format('Y-m-d H:i:s');
    $update = $db->prepare("UPDATE users SET otp_code = :otp, otp_expires = :otp_expires WHERE id = :id");
    $update->bindParam(':otp', $otp);
    $update->bindParam(':otp_expires', $otp_expires);
    $update->bindParam(':id', $user['id']);
    $update->execute();

    $message = "Your CICS Emergency and Important Alerts System verification code is: $otp. Expires in 5 minutes.";
    send_sms_iprog($user['phone'], $message);

    header("Location: verify_otp.php?email=" . urlencode($email) . "&resent=1");
    exit;
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
