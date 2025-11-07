<?php
require 'config.php';
$pdo = pdo();
$stmt = $pdo->query("SELECT u.id,u.first_name,u.last_name,IF(se.status='active',1,0) as sos_active,IFNULL(se.lat,14.167) as lat,IFNULL(se.lng,121.239) as lng FROM users u LEFT JOIN sos_events se ON u.id=se.user_id");
echo json_encode($stmt->fetchAll());
