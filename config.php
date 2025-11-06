<?php
// config.php - DB & API config (update these before running)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DB_HOST = '127.0.0.1';
const DB_NAME = 'bsu_auth';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHAR = 'utf8mb4';

// iProg SMS API token - replace with your token
const IPROG_API_TOKEN = '9a1eb56a366fc14a3d3086727eec0501272a325f';
const IPROG_API_URL = 'https://sms.iprogtech.com/api/v1/sms_messages';

// Site settings
const OTP_EXPIRY_SECONDS = 300; // 5 minutes
const PBKDF2_ITERATIONS = 100000;
const PBKDF2_BYTES = 64;

function pdo() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}

// Secure password hashing using PBKDF2 with SHA-256
function make_password_hash($password, $salt = null) {
    if ($salt === null) {
        $salt = bin2hex(random_bytes(16));
    }
    $hash = hash_pbkdf2('sha256', $password, $salt, PBKDF2_ITERATIONS, PBKDF2_BYTES, false);
    return ['salt' => $salt, 'hash' => $hash];
}

function verify_password($password, $stored_hash, $stored_salt) {
    $test = hash_pbkdf2('sha256', $password, $stored_salt, PBKDF2_ITERATIONS, PBKDF2_BYTES, false);
    // Use hash_equals to prevent timing attacks
    return hash_equals($stored_hash, $test);
}

// Send SMS via iProg
function send_sms_iprog($phone_number, $message) {
    $data = [
        'api_token' => IPROG_API_TOKEN,
        'message' => $message,
        'phone_number' => $phone_number
    ];
    $ch = curl_init(IPROG_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        // Logging would be better in production
        return ['success' => false, 'error' => $err];
    }
    // iProg returns JSON -- parse it if you want to check status codes
    return ['success' => true, 'raw' => $response];
}

// Validate BSU email format: xx-xxxxx@g.batstate-u.edu.ph
function validate_bsu_email($email) {
    // Pattern explanation:
    // ^[a-z0-9]{2}-[a-z0-9]{5}@g\.batstate-u\.edu\.ph$
    // Allow lowercase and digits; if uppercase allowed, lower-case before check
    $email = strtolower($email);
    return preg_match('/^[a-z0-9]{2}-[a-z0-9]{5}@g\.batstate-u\.edu\.ph$/', $email);
}
