<?php
require '../config.php';

$pdo = pdo();
$rows = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name, lat, lng, sos_active, UNIX_TIMESTAMP(updated_at) AS updated_ts FROM users")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status' => 'ok', 'users' => $rows]);
