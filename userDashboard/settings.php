<?php
require_once '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch user data safely
$pdo = pdo();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found!";
    exit;
}

// Safe variables
$first_name = !empty($user['first_name']) ? $user['first_name'] : '';
$last_name = !empty($user['last_name']) ? $user['last_name'] : '';
$email = !empty($user['email']) ? $user['email'] : '';
$phone = !empty($user['phone']) ? $user['phone'] : '';
$profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : '';
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | CICS AlertSOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        body, html {margin:0; padding:0; font-family:'Inter', sans-serif; background:#f5f7fa;}
        main {min-height:100vh; margin-left:0; padding:2rem 1rem; transition:all 0.3s;}
        @media (min-width:1024px){ main { margin-left:17rem; padding:3rem 2rem; } }
        .card { border-radius:1rem; padding:2rem; box-shadow:0 10px 25px rgba(0,0,0,0.08); background:#fff; max-width:800px; margin:0 auto; display:flex; flex-direction:column; gap:1.5rem; }
        .profile-pic { border-radius:9999px; background:#f87171; display:flex; align-items:center; justify-content:center; font-weight:bold; color:white; overflow:hidden; border:4px solid #fff; transition: transform 0.2s, box-shadow 0.3s; box-shadow:0 4px 15px rgba(0,0,0,0.2);}
        .profile-pic:hover { transform:scale(1.08); box-shadow:0 8px 25px rgba(0,0,0,0.25);}
        input:read-only { background:transparent; border:none; font-size:1rem; }
        input, select, textarea, button { width:100%; padding:0.65rem 0.9rem; border-radius:0.5rem; border:1px solid #e5e7eb; font-size:0.95rem; transition:all 0.3s; }
        input:focus, select:focus { outline:none; box-shadow:0 0 0 2px rgba(248,113,113,0.3); border-color:#f87171; }
        .menu-btn { cursor:pointer; font-size:1.8rem; color:#374151; transition:color 0.3s; }
        .menu-btn:hover { color:#f87171; }
        .menu-dropdown { display:none; position:absolute; right:0; top:2.5rem; background:#fff; border:1px solid #e5e7eb; border-radius:0.75rem; z-index:10; box-shadow:0 10px 25px rgba(0,0,0,0.15); min-width:240px; padding:1rem; }
        .menu-dropdown.active { display:block; }
        button:hover { background-color:#ef4444; }
        label { font-weight:500; margin-bottom:0.25rem; display:block; color:#374151; }
        .profile-pic-label { position:absolute; bottom:0; right:0; background:#ef4444; color:white; cursor:pointer; transition:0.2s; display:flex; align-items:center; justify-content:center; }
        .profile-pic-label:hover { background:#b91c1c; }
        @keyframes slide-in { 0% { transform:translateX(100%); opacity:0; } 100% { transform:translateX(0); opacity:1; } }
        .animate-slide-in { animation:slide-in 0.3s ease forwards; }
    </style>
</head>
<body class="theme-light">
    <custom-navbar class="relative z-50"></custom-navbar>
    <custom-sidebar class="relative z-40"></custom-sidebar>

    <main class="pt-24">
        <div class="card relative">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 relative">
                <div class="flex items-center gap-3">
                    <i data-feather="user" class="text-red-600"></i>
                    <h1 class="text-2xl sm:text-3xl font-bold text-red-600">Profile Settings</h1>
                </div>
                <span id="menuBtn" class="menu-btn self-end sm:self-auto">⋮</span>
                <div id="menuDropdown" class="menu-dropdown">
                    <button id="editProfileBtn" class="w-full text-left py-2 px-2 hover:bg-gray-100 rounded transition">Edit Profile</button>
                    <hr class="my-2 border-gray-300">
                    <form id="dashboardSettingsForm" class="space-y-3">
                        <h2 class="font-semibold text-gray-700">Dashboard Personalization</h2>
                        <div>
                            <label>Theme</label>
                            <select id="themeSelect" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                                <option value="red">Red Accent</option>
                            </select>
                        </div>
                        <div>
                            <label>Sidebar Layout</label>
                            <select id="sidebarLayout" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="expanded">Expanded</option>
                                <option value="compact">Compact</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="notificationsToggle" class="h-5 w-5 accent-red-600">
                            <label for="notificationsToggle" class="text-sm font-medium text-gray-600">Enable notifications</label>
                        </div>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 w-full transition">Save</button>
                    </form>
                </div>
            </div>

            <!-- Profile Picture -->
            <div class="flex flex-col items-center relative mt-8">
                <div id="profilePicPreview" class="profile-pic w-28 h-28 sm:w-32 sm:h-32 md:w-36 md:h-36 text-2xl sm:text-3xl md:text-4xl">
                    <?php if ($profile_pic): ?>
                        <img src="../uploads/profile_pics/<?= htmlspecialchars($profile_pic) ?>" class="w-full h-full object-cover rounded-full">
                    <?php else: ?>
                        <?= $initials ?: 'NN' ?>
                    <?php endif; ?>
                </div>
                <label for="profilePicInput" class="profile-pic-label w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-full shadow-md">
                    <i data-feather="camera" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6"></i>
                </label>
                <input type="file" id="profilePicInput" accept="image/*" class="hidden">
                <p class="text-sm text-gray-500 text-center mt-2 sm:text-base">Tap the camera icon to change your profile picture</p>
            </div>

            <!-- Profile Info Form -->
            <form id="profileForm" class="space-y-4 mt-6">
                <div>
                    <label>Full Name</label>
                    <input type="text" id="userName" value="<?= htmlspecialchars(trim("$first_name $last_name")) ?>" readonly class="bg-gray-100 rounded-md">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" id="userEmail" value="<?= htmlspecialchars($email) ?>" readonly class="bg-gray-100 rounded-md">
                </div>
                <div>
                    <label>Phone Number</label>
                    <input type="tel" id="userPhone" value="<?= htmlspecialchars($phone) ?>" readonly class="bg-gray-100 rounded-md">
                </div>
                <button type="submit" id="saveProfileBtn" class="hidden mt-3 bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 w-full sm:w-auto transition">Save Changes</button>
            </form>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-5 right-5 flex flex-col gap-3 z-50"></div>

    <script src="components/navbar.js"></script>
    <script src="components/sidebar.js"></script>
    <script>
        feather.replace();

        function showToast(message, type = 'success', duration = 3000) {
            const toast = document.createElement('div');
            toast.className = `px-4 py-3 rounded shadow text-white ${type==='success'?'bg-green-600':'bg-red-600'} animate-slide-in`;
            toast.textContent = message;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                setTimeout(() => toast.remove(), 500);
            }, duration);
        }

        const menuBtn = document.getElementById('menuBtn');
        const menuDropdown = document.getElementById('menuDropdown');
        menuBtn.addEventListener('click', () => menuDropdown.classList.toggle('active'));

        const editProfileBtn = document.getElementById('editProfileBtn');
        const profileForm = document.getElementById('profileForm');
        const saveBtn = document.getElementById('saveProfileBtn');
        editProfileBtn.addEventListener('click', () => {
            profileForm.querySelectorAll('input').forEach(input => input.readOnly = false);
            saveBtn.classList.remove('hidden');
            menuDropdown.classList.remove('active');
        });

        const profilePicInput = document.getElementById('profilePicInput');
        const profilePicPreview = document.getElementById('profilePicPreview');

        profilePicInput.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                profilePicPreview.innerHTML = '';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full h-full object-cover rounded-full';
                profilePicPreview.appendChild(img);
            };
            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('profile_pic', file);

            try {
                const res = await fetch('profile_save.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                showToast(result.message, result.success ? 'success' : 'error');
            } catch (err) {
                console.error(err);
                showToast('Error uploading profile picture.', 'error');
            }
        });

        profileForm.addEventListener('submit', async e => {
            e.preventDefault();
            const [first_name, ...lastParts] = document.getElementById('userName').value.trim().split(' ');
            const last_name = lastParts.join(' ');
            const phone = document.getElementById('userPhone').value;

            try {
                const res = await fetch('profile_save.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({first_name, last_name, phone})
                });
                const result = await res.json();
                showToast(result.message, result.success ? 'success' : 'error');
                profileForm.querySelectorAll('input').forEach(input => input.readOnly = true);
                saveBtn.classList.add('hidden');
            } catch (err) {
                console.error(err);
                showToast('Error updating profile.', 'error');
            }
        });

        document.getElementById('dashboardSettingsForm').addEventListener('submit', e => {
            e.preventDefault();
            const theme = document.getElementById('themeSelect').value;
            const sidebar = document.getElementById('sidebarLayout').value;
            const notifications = document.getElementById('notificationsToggle').checked;

            document.body.classList.remove('theme-light', 'theme-dark', 'theme-red');
            document.body.classList.add('theme-' + theme);
            document.querySelector('custom-sidebar').style.width = sidebar === 'compact' ? '5rem' : '17rem';

            showToast(`Dashboard personalization applied! Notifications ${notifications?'enabled':'disabled'}`, 'success');
        });
    </script>
</body>
</html>
