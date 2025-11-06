<?php
require_once '../config.php';

// Ensure session is set and valid
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['user_id'])) {
    header("Location: ../loginSignup/login.php");
    exit;
}

$db = pdo();
$stmt = $db->prepare("SELECT first_name, last_name, email, role FROM users WHERE id = :id LIMIT 1");
$stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: ../loginSignup/login.php");
    exit;
}

// Define user variables from database
$user_name  = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
$user_email = htmlspecialchars($user['email']);
$user_role  = htmlspecialchars($user['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>CICS AlertSOS | Dashboard</title>
<link rel="stylesheet" href="style.css" />
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: '#E53E3E',
        secondary: '#FEE2E2',
        accent: '#F87171'
      }
    }
  }
}
</script>

<style>
body { display: flex; background-color: #f9fafb; min-height: 100vh; }
main { flex: 1; margin-left: 16rem; margin-top: 4rem; padding: 2rem; transition: margin-left 0.3s ease; }
@media (max-width: 1024px) { main { margin-left: 0; padding: 1rem; } #navbarToggle { display: block; } }
#sosBtn { position: fixed; bottom: 2rem; right: 2rem; z-index: 150; }
#navbarToggle { display: none; }
</style>
</head>
<body>

<!-- Navbar -->
<custom-navbar class="relative z-50"></custom-navbar>
<!-- Sidebar -->
<custom-sidebar class="relative z-40"></custom-sidebar>

<!-- Main Content -->
<main>
  <!-- Current Date -->
  <div id="currentDate" class="flex justify-end mb-4">
    <div class="flex items-center bg-white border border-gray-300 rounded-xl shadow px-4 py-2 text-gray-800">
      <i data-feather="calendar" class="text-primary mr-2 w-4 h-4"></i>
      <span id="dateText" class="text-sm font-semibold"></span>
    </div>
  </div>

  <!-- Welcome Section -->
  <section class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-8">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">
          Welcome back, <span class="text-primary"><?= $user_name ?></span>
        </h1>
        <p class="text-gray-600"><?= $user_role ?> | <?= $user_email ?></p>
      </div>
      <div class="flex flex-wrap gap-4">
        <div class="bg-secondary px-6 py-4 rounded-lg text-center flex-1 min-w-[120px]">
          <p class="text-sm text-gray-600">Alerts Received</p>
          <p class="text-2xl font-bold text-primary">24</p>
        </div>
        <div class="bg-secondary px-6 py-4 rounded-lg text-center flex-1 min-w-[120px]">
          <p class="text-sm text-gray-600">Acknowledged</p>
          <p class="text-2xl font-bold text-primary">18</p>
        </div>
        <div class="bg-secondary px-6 py-4 rounded-lg text-center flex-1 min-w-[120px]">
          <p class="text-sm text-gray-600">Active SOS</p>
          <p class="text-2xl font-bold text-primary">0</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Notification & Filter Panel -->
  <section class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2 sm:gap-0">
      <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <i data-feather="bell"></i> Notifications
      </h2>
      <select id="alertFilter" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-primary focus:border-primary">
        <option value="all">All Alerts</option>
        <option value="high">High Priority</option>
        <option value="medium">Medium Priority</option>
        <option value="low">Low Priority</option>
      </select>
    </div>

    <div id="alertsList" class="space-y-4">
      <div class="alert-card border-l-4 border-primary bg-secondary p-4 rounded-r-lg" data-priority="high">
        <div class="flex justify-between items-start flex-wrap gap-2">
          <div>
            <h3 class="font-bold text-gray-800">Campus Lockdown</h3>
            <p class="text-gray-600 text-sm">Due to security concerns, the campus will be on lockdown until further notice.</p>
          </div>
          <span class="text-sm text-gray-500">10 min ago</span>
        </div>
      </div>

      <div class="alert-card border-l-4 border-yellow-500 bg-yellow-100 p-4 rounded-r-lg" data-priority="medium">
        <div class="flex justify-between items-start flex-wrap gap-2">
          <div>
            <h3 class="font-bold text-gray-800">Weather Advisory</h3>
            <p class="text-gray-600 text-sm">Heavy rains expected this afternoon. Classes after 3PM are canceled.</p>
          </div>
          <span class="text-sm text-gray-500">2 hours ago</span>
        </div>
      </div>

      <div class="alert-card border-l-4 border-green-500 bg-green-100 p-4 rounded-r-lg" data-priority="low">
        <div class="flex justify-between items-start flex-wrap gap-2">
          <div>
            <h3 class="font-bold text-gray-800">System Update</h3>
            <p class="text-gray-600 text-sm">Minor app updates and bug fixes have been deployed successfully.</p>
          </div>
          <span class="text-sm text-gray-500">1 day ago</span>
        </div>
      </div>
    </div>
  </section>
</main>

<button id="sosBtn" aria-label="Send SOS" class="bg-primary hover:bg-red-700 text-white rounded-full p-6 shadow-lg transform transition hover:scale-110">
  <i data-feather="alert-triangle" class="w-8 h-8"></i>
</button>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>

<script>
feather.replace();

// Current date
document.getElementById('dateText').textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

// SOS button
document.getElementById('sosBtn').addEventListener('click', () => {
  if(confirm('🚨 Send SOS Alert now?')) {
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(pos => {
        alert('🚨 SOS Sent! Location shared with CICS admins.');
        console.log('Coordinates:', pos.coords.latitude, pos.coords.longitude);
      });
    } else alert('Geolocation not supported.');
  }
});

// Alert filter
document.getElementById('alertFilter').addEventListener('change', function() {
  const selected = this.value;
  document.querySelectorAll('.alert-card').forEach(alert => {
    alert.style.display = selected === 'all' || alert.dataset.priority === selected ? 'block' : 'none';
  });
});

// Sidebar toggle (if navbarToggle exists)
const toggleBtn = document.getElementById('navbarToggle');
const sidebar = document.querySelector('custom-sidebar');
if(toggleBtn && sidebar) {
  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('active');
  });
}
</script>

</body>
</html>
