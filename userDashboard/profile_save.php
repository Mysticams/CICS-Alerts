<?php
require_once '../config.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'You must be logged in.']);
    exit;
}

// === JSON profile info update ===
$data = json_decode(file_get_contents('php://input'), true);
if(isset($data['first_name'], $data['last_name'], $data['phone'])){
    $first_name = trim($data['first_name']);
    $last_name  = trim($data['last_name']);
    $phone      = trim($data['phone']);

    if(empty($first_name) || empty($last_name) || empty($phone)){
        echo json_encode(['success'=>false,'message'=>'All fields are required.']); exit;
    }

    try{
        $stmt = pdo()->prepare("UPDATE users SET first_name=:first_name, last_name=:last_name, phone=:phone WHERE id=:id");
        $stmt->execute([
            'first_name'=>$first_name,
            'last_name'=>$last_name,
            'phone'=>$phone,
            'id'=>$_SESSION['user_id']
        ]);
        echo json_encode(['success'=>true,'message'=>'Profile updated successfully!']); exit;
    } catch(PDOException $e){
        echo json_encode(['success'=>false,'message'=>'Database error: '.$e->getMessage()]); exit;
    }
}

// === Profile picture upload ===
if(isset($_FILES['profile_pic'])){
    $file = $_FILES['profile_pic'];
    $allowed = ['image/jpeg','image/png','image/gif'];
    if(!in_array($file['type'],$allowed)){
        echo json_encode(['success'=>false,'message'=>'Only JPG, PNG, GIF allowed']); exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = 'user_'.$_SESSION['user_id'].'_'.time().'.'.$ext;
    $uploadDir = '../uploads/profile_pics/';
    if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

    if(move_uploaded_file($file['tmp_name'], $uploadDir.$newName)){
        $stmt = pdo()->prepare("UPDATE users SET profile_pic=:pic WHERE id=:id");
        $stmt->execute(['pic'=>$newName,'id'=>$_SESSION['user_id']]);
        echo json_encode(['success'=>true,'message'=>'✅ Profile picture updated!','filename'=>$newName]); exit;
    } else {
        echo json_encode(['success'=>false,'message'=>'Upload failed']); exit;
    }
}

echo json_encode(['success'=>false,'message'=>'No valid data sent']);
?>
