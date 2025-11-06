<?php
require '../config.php';
if(session_status() == PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_role']!=='admin') exit;

$pdo = pdo();
$stmt = $pdo->query("SELECT id, first_name, last_name, sos_active, lat, lng FROM users WHERE user_role != 'admin'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
