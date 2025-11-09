<?php
require_once '../config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error'=>'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['error'=>'Invalid input']); exit; }

$pdo = pdo();
$update_fields = [
    'first_name'=>$data['first_name'],
    'last_name'=>$data['last_name'],
    'email'=>$data['email'],
    'id'=>$_SESSION['user_id']
];

$sql = "UPDATE users SET first_name=:first_name, last_name=:last_name, email=:email";
if(!empty($data['password'])){
    $hash_data = make_password_hash($data['password']);
    $sql .= ", password_hash=:password_hash, password_salt=:password_salt";
    $update_fields['password_hash']=$hash_data['hash'];
    $update_fields['password_salt']=$hash_data['salt'];
}
$sql .= " WHERE id=:id LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($update_fields);

echo json_encode(['success'=>true, 'message'=>'Admin settings updated']);
