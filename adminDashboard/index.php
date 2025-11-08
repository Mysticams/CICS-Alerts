<?php
session_start();
require_once '../config.php'; // Ensure pdo() is defined

$pdo = pdo(); // Initialize PDO connection

// --- ADMIN ONLY ACCESS ---
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../loginSignup/login.php");
    exit;
}

// --- DASHBOARD STATS ---
try {
    $totalUsers   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?? 0;
    $totalAlerts  = $pdo->query("SELECT COUNT(*) FROM alerts")->fetchColumn() ?? 0;
    $acknowledged = $pdo->query("SELECT COUNT(*) FROM alerts WHERE status='acknowledged'")->fetchColumn() ?? 0;
    $activeSOS    = $pdo->query("SELECT COUNT(*) FROM alerts WHERE status='active' AND type='sos'")->fetchColumn() ?? 0;

    $stmt = $pdo->prepare("SELECT * FROM alerts ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recentAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// --- HELPER FUNCTION ---
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return $diff . ' second' . ($diff != 1 ? 's' : '') . ' ago';
    if ($diff < 3600) return floor($diff/60) . ' minute' . (floor($diff/60) != 1 ? 's' : '') . ' ago';
    if ($diff < 86400) return floor($diff/3600) . ' hour' . (floor($diff/3600) != 1 ? 's' : '') . ' ago';
    return floor($diff/86400) . ' day' . (floor($diff/86400) != 1 ? 's' : '') . ' ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Dashboard | CICS AlertSOS</title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Feather Icons -->
<script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-100 font-sans">

  <!-- Navbar -->
  <custom-navbar></custom-navbar>

  <!-- Sidebar -->
  <custom-sidebar></custom-sidebar>

  <main class="pt-20 lg:pt-24 p-6 lg:ml-64 min-h-screen transition-all duration-300">
    <!-- Dashboard Overview -->
    <section class="mb-8">
      <div class="flex justify-between items-center mb-4">
        <div>
          <h2 class="text-2xl font-bold text-red-700">Dashboard Overview</h2>
          <p class="text-gray-600">Welcome back, <span class="font-semibold">Admin</span></p>
        </div>
        <div class="flex items-center space-x-4">
          <div class="bg-white p-2 rounded-lg shadow flex items-center">
            <i data-feather="calendar" class="text-red-600 mr-2"></i>
            <span id="currentDate"></span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="stats-card bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 mr-4">
              <i data-feather="users" class="text-red-600"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total Users</p>
              <h3 class="text-2xl font-bold"><?= $totalUsers ?></h3>
            </div>
          </div>
        </div>
        <div class="stats-card bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 mr-4">
              <i data-feather="bell" class="text-red-600"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total Alerts</p>
              <h3 class="text-2xl font-bold"><?= $totalAlerts ?></h3>
            </div>
          </div>
        </div>
        <div class="stats-card bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 mr-4">
              <i data-feather="check-circle" class="text-red-600"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Acknowledged</p>
              <h3 class="text-2xl font-bold"><?= $acknowledged ?></h3>
            </div>
          </div>
        </div>
        <div class="stats-card bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 mr-4">
              <i data-feather="alert-triangle" class="text-red-600"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Active SOS</p>
              <h3 class="text-2xl font-bold"><?= $activeSOS ?></h3>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent Alerts -->
    <section class="mb-8">
      <h2 class="text-2xl font-bold mb-4 text-red-700">Real-time Notifications</h2>
      <div class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="p-4 bg-red-700 text-white flex items-center">
          <i data-feather="bell" class="mr-2"></i>
          <span>Live Alerts</span>
        </div>
        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
          <?php foreach ($recentAlerts as $alert): ?>
          <div class="p-4 hover:bg-gray-50 flex items-start">
            <div class="bg-red-100 p-2 rounded-full mr-3">
              <i data-feather="alert-triangle" class="text-red-600"></i>
            </div>
            <div>
              <p class="font-medium"><?= htmlspecialchars($alert['title']) ?></p>
              <p class="text-sm text-gray-500"><?= htmlspecialchars($alert['location'] ?? 'Unknown location') ?></p>
              <p class="text-xs text-gray-400 mt-1"><?= timeAgo($alert['created_at']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>
<script src="script.js"></script>
<script>
feather.replace();

document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
});
</script>
</body>
</html>
