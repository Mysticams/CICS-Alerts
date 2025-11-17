<?php
require '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$pdo = pdo();

// Pagination & search
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
$offset = ($page-1)*$limit;

// Total logs
$total = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al
LEFT JOIN users u ON u.id = al.user_id
WHERE u.first_name LIKE :search OR u.last_name LIKE :search OR al.action LIKE :search");
$total->execute(['search'=>$search]);
$totalRows = $total->fetchColumn();

// Fetch logs
$stmt = $pdo->prepare("SELECT al.id, al.action, al.created_at, u.first_name, u.last_name
FROM activity_logs al
LEFT JOIN users u ON u.id = al.user_id
WHERE u.first_name LIKE :search OR u.last_name LIKE :search OR al.action LIKE :search
ORDER BY al.id DESC LIMIT :offset, :limit");

$stmt->bindValue(':search', $search, PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$logs = $stmt->fetchAll();
echo json_encode(['logs'=>$logs,'total'=>$totalRows,'page'=>$page,'limit'=>$limit]);
