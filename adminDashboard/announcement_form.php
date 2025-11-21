<?php
// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch users with roles
$stmt = $pdo->query("SELECT id, first_name, last_name, role FROM users ORDER BY role, first_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group users by role
$groupedUsers = [];
foreach ($users as $user) {
  $role = $user['role'] ?? 'Others';
  $groupedUsers[$role][] = $user;
}
ksort($groupedUsers);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Announcement | Web Only</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
    /* Smooth toggle for collapsible user lists */
    .transition-max-height {
      transition: max-height 0.3s ease-in-out;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

  <div class="flex flex-1 pt-16"> <!-- pt-16 to offset fixed navbar -->

    <!-- Main Content -->
    <main class="flex-1 mr-0 md:mr-64 p-6 max-w-4xl w-full mx-auto">
      <div class="bg-white p-6 rounded-2xl shadow-lg relative">

        <!-- Back button inside the container -->
        <div class="absolute top-4 left-4">
          <a href="javascript:history.back()" class="flex items-center text-red-600 font-semibold hover:text-red-800">
            <i data-feather="arrow-left" class="w-5 h-5 mr-2"></i> Back
          </a>
        </div>

        <h2 class="text-2xl font-bold text-red-600 mb-6 text-center flex justify-center items-center gap-2">
          Send New Announcement
          <i data-feather="globe" class="w-5 h-5"></i>
        </h2>

        <form action="send_announcement.php" method="POST" class="space-y-5">
          <!-- Message -->
          <div>
            <label class="block font-semibold mb-2">Announcement Message:</label>
            <textarea name="message" rows="4" required placeholder="Type your alert message here..."
              class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"></textarea>
          </div>

          <!-- Select All Users -->
          <div class="mb-3">
            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" id="selectAllUsers" class="accent-red-600 mr-2">
              <span class="font-semibold">Select All Users</span>
            </label>
          </div>

          <!-- Recipients by role -->
          <div>
            <label class="block font-semibold mb-2">Select Recipients by Role:</label>
            <?php foreach ($groupedUsers as $role => $group): ?>
              <div class="border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="bg-red-700 text-white px-4 py-2 flex justify-between items-center cursor-pointer role-header">
                  <?= htmlspecialchars($role) ?> <span class="toggle-icon">▼</span>
                </div>
                <div class="max-h-0 overflow-hidden transition-max-height role-users bg-gray-900 px-4">
                  <?php foreach ($group as $user): ?>
                    <div class="flex items-center py-1 text-gray-200">
                      <input type="checkbox" name="recipients[<?= htmlspecialchars($role) ?>][]" value="<?= $user['id'] ?>" class="accent-red-600 mr-2">
                      <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Priority -->
          <div>
            <label class="block font-semibold mb-2">Select Priority:</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="priority" value="High" class="accent-red-600" required>
                <span class="px-3 py-1 rounded-full bg-red-600 text-white text-sm">High</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="priority" value="Medium" class="accent-yellow-600" required>
                <span class="px-3 py-1 rounded-full bg-yellow-400 text-white text-sm">Medium</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="priority" value="Low" class="accent-green-600" required>
                <span class="px-3 py-1 rounded-full bg-green-600 text-white text-sm">Low</span>
              </label>
            </div>
          </div>

          <button type="submit" class="w-full bg-green-500 text-white font-semibold py-3 rounded-xl hover:bg-green-700 transition">Send Announcement</button>
        </form>
      </div>
    </main>

  </div>

  <!-- Scripts -->
  <script>
    feather.replace();

    // Toggle role users
    document.querySelectorAll('.role-header').forEach(header => {
      header.addEventListener('click', () => {
        const users = header.nextElementSibling;
        if (users.classList.contains('max-h-0')) {
          users.classList.remove('max-h-0');
          users.classList.add('max-h-96');
          header.querySelector('.toggle-icon').textContent = '▲';
        } else {
          users.classList.remove('max-h-96');
          users.classList.add('max-h-0');
          header.querySelector('.toggle-icon').textContent = '▼';
        }
      });
    });

    // Select All Users functionality
    document.getElementById('selectAllUsers').addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.role-users input[type="checkbox"]');
      checkboxes.forEach(cb => cb.checked = this.checked);
    });
  </script>
</body>

</html>