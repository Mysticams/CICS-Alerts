<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Only allow admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Load settings
$settingsFile = __DIR__ . '/admin_settings.json';
$admin = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'email' => 'admin@g.batstate-u.edu.ph',
    'dark_mode' => 0,
    'allow_notifications' => 1,
    'show_system_logs' => 0,
    'maintenance_mode' => 0,
    'profile_pic' => null
];

$isDark = intval($admin['dark_mode']) === 1;

// Tailwind classes according to dark mode
$bgBody = $isDark ? 'bg-gray-900' : 'bg-white';
$textBody = $isDark ? 'text-gray-100' : 'text-gray-900';
$bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
$textCard = $isDark ? 'text-gray-100' : 'text-gray-900';
$borderCard = $isDark ? 'border-gray-700' : 'border-gray-300';
$inputBg = $isDark ? 'bg-gray-700' : 'bg-white';
$inputText = $isDark ? 'text-gray-100' : 'text-gray-900';
$inputBorder = $isDark ? 'border-gray-600' : 'border-gray-300';
$labelText = $isDark ? 'text-gray-100' : 'text-gray-800';
$subText = $isDark ? 'text-gray-300' : 'text-gray-600';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Settings | CICS AlertSOS</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<style>
.drop-zone { border: 2px dashed #000000ff; border-radius: 1rem; padding: 1rem; text-align: center; cursor: pointer; transition: 0.2s; }
.drop-zone.dragover { background-color: #fee2e2; }
#toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 9999; }
.toast { min-width: 250px; margin-top: 10px; padding: 12px 16px; border-radius: 0.5rem; color: #fff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: space-between; animation: slideIn 0.5s ease forwards; }
.toast.success { background-color: #16a34a; }
.toast.error { background-color: #dc2626; }
@keyframes slideIn { from {transform: translateX(100%); opacity: 0;} to {transform: translateX(0); opacity: 1;} }
</style>
</head>
<body class="<?= "$bgBody $textBody font-sans" ?>">

<!-- Navbar -->
<custom-navbar class="relative z-50"></custom-navbar>

<!-- Sidebar -->
<custom-sidebar class="relative z-40"></custom-sidebar>

<main class="pt-20 lg:pt-24 p-6 lg:ml-64 min-h-screen transition-all duration-300">

<!-- Profile Card -->
<div class="rounded-2xl shadow-lg p-6 mb-6 flex items-center gap-6 <?= "$bgCard $textCard border $borderCard" ?>">
    <img id="profileCardPic" src="<?= $admin['profile_pic'] ? 'uploads/' . $admin['profile_pic'] : '../img/default-avatar.png' ?>" class="w-24 h-24 rounded-full border <?= $borderCard ?>" alt="Profile Picture">
    <div>
        <h2 id="profileCardName" class="text-2xl font-bold"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></h2>
        <p id="profileCardEmail" class="<?= $subText ?>"><?= htmlspecialchars($admin['email']) ?></p>
        <p class="<?= $subText ?> text-sm">System Administrator</p>
    </div>
</div>

<h1 class="text-3xl font-bold mb-6 flex items-center gap-2 text-red-600">
    <i data-feather="settings" class="w-7 h-7"></i> Admin Settings
</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

<!-- Profile Settings -->
<div class="rounded-2xl shadow-lg p-6 border <?= "$bgCard $textCard $borderCard" ?>">
    <h2 class="text-xl font-semibold mb-4 flex items-center gap-2 text-red-600">
        <i data-feather="user" class="w-5 h-5"></i> Profile Settings
    </h2>
    <form id="profileForm" class="space-y-4" enctype="multipart/form-data">
        <div>
            <label class="<?= $labelText ?>">Full Name</label>
            <input type="text" id="adminName" name="adminName" class="w-full rounded-lg border px-3 py-2 <?= "$inputBg $inputText $inputBorder" ?>" value="<?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>" />
        </div>
        <div>
            <label class="<?= $labelText ?>">Email Address</label>
            <input type="email" id="adminEmail" name="adminEmail" class="w-full rounded-lg border px-3 py-2 <?= "$inputBg $inputText $inputBorder" ?>" value="<?= htmlspecialchars($admin['email']) ?>" />
        </div>
        <div>
            <label class="<?= $labelText ?>">Profile Picture</label>
            <div id="dropZone" class="drop-zone">
                <p class="<?= $subText ?>">Drag & drop or click to select</p>
                <input type="file" id="profilePic" name="profilePic" class="hidden" accept="image/*">
            </div>
            <img id="profilePreview" class="mt-2 w-24 h-24 rounded-full border <?= $borderCard ?>" src="<?= $admin['profile_pic'] ? 'uploads/' . $admin['profile_pic'] : '../img/default-avatar.png' ?>" alt="Profile Preview">
        </div>
    </form>
</div>

<!-- Admin Features -->
<div class="rounded-2xl shadow-lg p-6 border <?= "$bgCard $textCard $borderCard" ?>">
    <h2 class="text-xl font-semibold mb-4 flex items-center gap-2 text-red-600">
        <i data-feather="tool" class="w-5 h-5"></i> Admin Features
    </h2>
    <form id="featuresForm" class="space-y-4">
        <div class="flex items-center gap-2">
            <input type="checkbox" id="darkMode" name="dark_mode" <?= $isDark ? 'checked' : '' ?> class="accent-red-600">
            <label for="darkMode" class="<?= $labelText ?>">Enable Dark Mode</label>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" id="allowNotifications" name="allow_notifications" <?= intval($admin['allow_notifications']) === 1 ? 'checked' : '' ?> class="accent-red-600">
            <label for="allowNotifications" class="<?= $labelText ?>">Allow Notifications</label>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" id="showSystemLogs" name="show_system_logs" <?= intval($admin['show_system_logs']) === 1 ? 'checked' : '' ?> class="accent-red-600">
            <label for="showSystemLogs" class="<?= $labelText ?>">Show System Logs</label>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" id="maintenanceMode" name="maintenance_mode" <?= intval($admin['maintenance_mode']) === 1 ? 'checked' : '' ?> class="accent-red-600">
            <label for="maintenanceMode" class="<?= $labelText ?>">Enable Maintenance Mode</label>
        </div>
    </form>
</div>
</div>

<button id="saveSettings" class="mt-6 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow flex items-center gap-2">
    <i data-feather="save" class="w-5 h-5"></i> Save All Settings
</button>

<div id="toast-container"></div>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>
<script>
feather.replace();

// Elements
const profileName = document.getElementById('adminName');
const profileEmail = document.getElementById('adminEmail');
const profilePreview = document.getElementById('profilePreview');
const profileCardPic = document.getElementById('profileCardPic');
const profileCardName = document.getElementById('profileCardName');
const profileCardEmail = document.getElementById('profileCardEmail');
const fileInput = document.getElementById('profilePic');
const darkModeToggle = document.getElementById('darkMode');
const allowNotificationsToggle = document.getElementById('allowNotifications');
const showSystemLogsToggle = document.getElementById('showSystemLogs');
const maintenanceModeToggle = document.getElementById('maintenanceMode');
const dropZone = document.getElementById('dropZone');

function showToast(msg, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${msg}</span><button onclick="this.parentElement.remove()">✖</button>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// Drag & drop preview
dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        previewFile(fileInput.files[0]);
    }
});
fileInput.addEventListener('change', () => previewFile(fileInput.files[0]));

function previewFile(file) {
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            profilePreview.src = e.target.result;
            profileCardPic.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Dark mode toggle function
function updateDarkMode(isDark) {
    // Body
    document.body.classList.toggle('bg-white', !isDark);
    document.body.classList.toggle('text-gray-900', !isDark);
    document.body.classList.toggle('bg-gray-900', isDark);
    document.body.classList.toggle('text-gray-100', isDark);

    // Cards
    document.querySelectorAll('.shadow-lg').forEach(card => {
        card.classList.toggle('bg-white', !isDark);
        card.classList.toggle('text-gray-900', !isDark);
        card.classList.toggle('border-gray-300', !isDark);
        card.classList.toggle('bg-gray-800', isDark);
        card.classList.toggle('text-gray-100', isDark);
        card.classList.toggle('border-gray-700', isDark);
    });

    // Inputs
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="file"]').forEach(input => {
        input.classList.toggle('bg-white', !isDark);
        input.classList.toggle('text-gray-900', !isDark);
        input.classList.toggle('border-gray-300', !isDark);
        input.classList.toggle('bg-gray-700', isDark);
        input.classList.toggle('text-gray-100', isDark);
        input.classList.toggle('border-gray-600', isDark);
    });

    // Labels
    document.querySelectorAll('label').forEach(label => {
        label.classList.toggle('text-gray-800', !isDark);
        label.classList.toggle('text-gray-100', isDark);
    });

    // Subtexts
    document.querySelectorAll('.text-sm, .text-gray-600').forEach(el => {
        el.classList.toggle('text-gray-600', !isDark);
        el.classList.toggle('text-gray-300', isDark);
    });
}

// Initialize dark mode on load
updateDarkMode(darkModeToggle.checked);

// Toggle live
darkModeToggle.addEventListener('change', () => updateDarkMode(darkModeToggle.checked));

// Save settings
document.getElementById('saveSettings').addEventListener('click', async () => {
    const fullName = profileName.value.trim();
    const nameParts = fullName.split(' ');
    const first_name = nameParts.shift() || '';
    const last_name = nameParts.join(' ') || first_name;

    const formData = new FormData();
    formData.append('first_name', first_name);
    formData.append('last_name', last_name);
    formData.append('email', profileEmail.value);
    formData.append('dark_mode', darkModeToggle.checked ? 1 : 0);
    formData.append('allow_notifications', allowNotificationsToggle.checked ? 1 : 0);
    formData.append('show_system_logs', showSystemLogsToggle.checked ? 1 : 0);
    formData.append('maintenance_mode', maintenanceModeToggle.checked ? 1 : 0);
    if (fileInput.files[0]) formData.append('profilePic', fileInput.files[0]);

    try {
        const res = await fetch('admin_save.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            showToast(result.message, 'success');
            profileCardName.textContent = `${first_name} ${last_name}`;
            profileCardEmail.textContent = profileEmail.value;
            if (result.profile_pic) {
                const picUrl = 'uploads/' + result.profile_pic + '?' + new Date().getTime();
                profilePreview.src = picUrl;
                profileCardPic.src = picUrl;
            }
        } else {
            showToast(result.message, 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Failed to save settings', 'error');
    }
});
</script>
</main>
</body>
</html>
