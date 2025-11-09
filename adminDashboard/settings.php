<?php
require_once '../config.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pdo = pdo();

// Fetch admin info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
$stmt->execute(['id'=>$_SESSION['user_id']]);
$admin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Settings | CICS AlertSOS</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 font-sans">

<custom-navbar></custom-navbar>
<custom-sidebar></custom-sidebar>

<main class="pt-20 lg:pt-24 p-6 lg:ml-64 min-h-screen transition-all duration-300">

<div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
        <i data-feather="settings" class="w-7 h-7 text-red-600"></i> Admin Settings
    </h1>
    <button id="saveSettings" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow transition">
        <i data-feather="save" class="inline w-5 h-5 mr-1"></i> Save Settings
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i data-feather="user" class="w-5 h-5 text-red-600"></i> Profile Settings
    </h2>
    <form id="profileForm" class="space-y-4">
        <div>
            <label for="adminName">Full Name</label>
            <input type="text" id="adminName" value="<?= htmlspecialchars($admin['first_name'].' '.$admin['last_name']) ?>" />
        </div>
        <div>
            <label for="adminEmail">Email Address</label>
            <input type="email" id="adminEmail" value="<?= htmlspecialchars($admin['email']) ?>" />
        </div>
        <div>
            <label for="adminPassword">Change Password</label>
            <input type="password" id="adminPassword" placeholder="••••••••" />
        </div>
    </form>
</div>

</div>
</main>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>
<script>
feather.replace();

document.getElementById('saveSettings').addEventListener('click', async () => {
    const [first_name, ...last] = document.getElementById('adminName').value.trim().split(' ');
    const last_name = last.join(' ');
    const payload = {
        first_name: first_name,
        last_name: last_name,
        email: document.getElementById('adminEmail').value,
        password: document.getElementById('adminPassword').value
    };

    const res = await fetch('admin_save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });
    const result = await res.json();
    alert(result.message);
    location.reload();
});
</script>
</body>
</html>
