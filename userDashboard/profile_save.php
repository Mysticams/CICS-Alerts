<?php
require_once '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

$pdo = pdo();
$user_id = $_SESSION['user_id'];

// Handle profile picture upload
if (!empty($_FILES['profile_pic']['name'])) {
    $file = $_FILES['profile_pic'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','gif'];
    if(!in_array(strtolower($ext), $allowed)){
        echo json_encode(['success'=>false,'message'=>'Invalid image type']);
        exit;
    }

    $newName = 'profile_'.$user_id.'_'.time().'.'.$ext;
    $uploadDir = '../uploads/profile_pics/';
    if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if(move_uploaded_file($file['tmp_name'], $uploadDir.$newName)){
        $stmt = $pdo->prepare("UPDATE users SET profile_pic=:pic WHERE id=:id");
        $stmt->execute(['pic'=>$newName,'id'=>$user_id]);
        echo json_encode(['success'=>true,'message'=>'Profile picture updated']);
        exit;
    } else {
        echo json_encode(['success'=>false,'message'=>'Failed to upload image']);
        exit;
    }
}

// Handle profile info update (JSON POST)
$data = json_decode(file_get_contents('php://input'), true);
if($data){
    $stmt = $pdo->prepare("UPDATE users SET first_name=:first, last_name=:last, phone=:phone, address=:address, birthday=:birthday, gender=:gender WHERE id=:id");
    $stmt->execute([
        'first' => $data['first_name'] ?? '',
        'last' => $data['last_name'] ?? '',
        'phone' => $data['phone'] ?? '',
        'address' => $data['address'] ?? '',
        'birthday' => $data['birthday'] ?? null,
        'gender' => $data['gender'] ?? '',
        'id' => $user_id
    ]);
    echo json_encode(['success'=>true,'message'=>'Profile updated successfully']);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Nothing to update']);
