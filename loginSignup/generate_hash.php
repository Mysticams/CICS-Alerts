<?php
$password = 'Admin123!'; // your chosen admin password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
