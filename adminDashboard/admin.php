<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Only allow admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin - SOS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #fff6f6;
        }

        /* Map */
        #map {
            height: 75vh;
            border-radius: 1rem;
            border: 3px solid #d32f2f;
        }

        /* Red & green markers */
        .leaflet-div-icon.marker-red {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #d32f2f;
            border: 2px solid #fff;
            box-shadow: 0 0 12px 4px rgba(211, 47, 47, 0.5);
        }

        .leaflet-div-icon.marker-green {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #43a047;
            border: 2px solid #fff;
            box-shadow: 0 0 12px 4px rgba(67, 160, 71, 0.35);
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .blink { animation: blink 1s infinite; }

        /* Floating Chat */
        #chatContainer {
            position: fixed;
            bottom: 90px;
            right: 25px;
            width: 380px;
            height: 520px;
            z-index: 9999;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            border: 2px solid #d32f2f;
            background: white;
            display: none;
        }

        #toggleChat {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 10000;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 50%;
            width: 58px;
            height: 58px;
            font-size: 1.6rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        #toggleChat:hover { transform: scale(1.1); background: #b71c1c; }
    </style>
</head>

<body>
    <!-- Navbar -->
    <custom-navbar class="relative z-50"></custom-navbar>

    <!-- Sidebar -->
    <custom-sidebar class="relative z-40"></custom-sidebar>

    <!-- Main Content -->
    <main class="pt-20 lg:pt-24 p-6 lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-3">
           <h1 class="text-3xl font-extrabold flex items-center gap-2 text-red-700">
    <i data-feather="map-pin"></i> Live SOS Dashboard
</h1>


            <div class="bg-white shadow-md rounded-lg px-5 py-2 flex items-center gap-3 border border-red-200">
                <span class="font-semibold text-gray-700 text-sm">Active SOS:</span>
                <span id="sosCounter" class="bg-red-600 text-white font-bold text-lg px-3 py-1 rounded-md">0</span>
                <button id="refreshBtn"
                    class="ml-3 bg-red-700 hover:bg-red-800 text-white px-3 py-1 rounded-md text-sm font-medium">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Content Layout -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Map Section -->
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-lg p-4 border border-red-100">
                <div id="map"></div>
            </div>

            <!-- User List -->
            <div class="bg-white rounded-2xl shadow-lg border border-red-100 p-4 overflow-y-auto max-h-[75vh]">
                <h2 class="text-xl font-semibold text-gray-700 border-b pb-2 mb-3 flex items-center gap-2">
                    <i data-feather="users" class="text-red-600"></i> User SOS Activity
                </h2>
                <div id="users" class="flex flex-col gap-3" aria-live="polite"></div>
            </div>
        </div>
    </main>

    <!-- Chat Floating -->
    <button id="toggleChat"><i class="bi bi-chat-dots-fill"></i></button>
    <div id="chatContainer">
        <iframe src="sos/chat_admin.php" style="width:100%; height:100%; border:none;"></iframe>
    </div>

    <!-- Sound -->
    <audio id="sosAlert" src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" preload="auto" loop></audio>

    <!-- Scripts -->
    <script src="components/navbar.js"></script>
    <script src="components/sidebar.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        feather.replace();

        const endpoints = {
            users: 'sos/ajax_admin_users.php',
            resolve: 'sos/ajax_resolve_sos.php'
        };

        const map = L.map('map').setView([14.167, 121.241], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let userMarkers = {}, pollInterval = 3000, sosSound = document.getElementById('sosAlert'), lastSOSCount = 0;

        if ("Notification" in window && Notification.permission !== "granted")
            Notification.requestPermission();

        function notifyAdmin(name) {
            if (Notification.permission === "granted")
                new Notification("🚨 SOS Alert!", { body: `${name} activated SOS!` });

            const orig = document.title;
            document.title = "🚨 SOS ALERT!";
            setTimeout(() => document.title = orig, 3500);
        }

        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"'\/]/g, c => ({
                "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;", "/": "&#x2F;"
            }[c]));
        }

        function fetchUsers() {
            $.getJSON(endpoints.users, function (res) {
                if (!res || res.status !== 'ok') return;
                renderUsers(res.users || []);
            }).fail(() => console.warn('Failed to fetch users.'));
        }

        function renderUsers(users) {
            const list = $('#users').empty();
            let activeSOS = 0, newSOSUsers = [];

            users.forEach(u => {
                const lat = parseFloat(u.lat), lng = parseFloat(u.lng);
                if (isNaN(lat) || isNaN(lng)) return;

                const iconCls = u.sos_active ? 'marker-red blink' : 'marker-green';
                const icon = L.divIcon({ className: `leaflet-div-icon ${iconCls}` });

                if (!userMarkers[u.id]) {
                    userMarkers[u.id] = L.marker([lat, lng], { icon }).addTo(map);
                    userMarkers[u.id].bindPopup(`<strong>${escapeHtml(u.name)}</strong><br>SOS: ${u.sos_active ? 'YES' : 'No'}`);
                } else {
                    userMarkers[u.id].setLatLng([lat, lng]).setIcon(icon);
                    userMarkers[u.id].getPopup().setContent(`<strong>${escapeHtml(u.name)}</strong><br>SOS: ${u.sos_active ? 'YES' : 'No'}`);
                }

                if (u.sos_active) { activeSOS++; if (!userMarkers[u.id].isSOS) newSOSUsers.push(u); userMarkers[u.id].isSOS = true; }
                else { userMarkers[u.id].isSOS = false; }

                const card = $(`
                    <div class="${u.sos_active ? 'bg-red-50 border-l-4 border-red-600' : 'bg-green-50 border-l-4 border-green-600'} p-3 rounded-lg shadow-sm">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-bold text-gray-800">${escapeHtml(u.name)}</div>
                                <small class="text-gray-500">Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}</small>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded ${u.sos_active ? 'bg-red-600 text-white' : 'bg-green-600 text-white'}">
                                ${u.sos_active ? 'ACTIVE' : 'SAFE'}
                            </span>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button class="bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded text-sm font-medium">Focus</button>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-sm font-medium">Chat</button>
                            ${u.sos_active ? `<button class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm font-medium">Resolve</button>` : ''}
                        </div>
                    </div>
                `);

                card.find('button:contains("Focus")').on('click', () => {
                    map.setView([lat, lng], 16);
                    userMarkers[u.id].openPopup();
                });

                card.find('button:contains("Chat")').on('click', () => {
                    chatContainer.style.display = 'block';
                    toggleBtn.querySelector('i').className = 'bi bi-chat-dots-fill';
                    const iframe = chatContainer.querySelector('iframe');
                    iframe.src = `sos/chat_admin.php?user_id=${u.id}&user_name=${encodeURIComponent(u.name)}`;
                    isOpen = true;
                });

                card.find('button:contains("Resolve")').on('click', () => resolveSOS(u.id));

                list.append(card);
            });

            $('#sosCounter').text(activeSOS);

            if (activeSOS > lastSOSCount) {
                sosSound.currentTime = 0;
                sosSound.play().catch(() => { });
                newSOSUsers.forEach(u => {
                    notifyAdmin(u.name);
                    map.setView([parseFloat(u.lat), parseFloat(u.lng)], 16);
                    if (userMarkers[u.id]) userMarkers[u.id].openPopup();
                });
            }

            if (activeSOS === 0) { sosSound.pause(); sosSound.currentTime = 0; }

            lastSOSCount = activeSOS;
        }

        function resolveSOS(userId) {
            if (!confirm('Mark this SOS as resolved?')) return;
            $.post(endpoints.resolve, { user_id: userId }, function (res) {
                if (res && res.status === 'ok') fetchUsers();
                else alert('Failed to resolve SOS.');
            }, 'json').fail(() => alert('Network error while resolving SOS.'));
        }

        $(document).ready(() => {
            $('#refreshBtn').on('click', fetchUsers);
            fetchUsers();
            setInterval(fetchUsers, pollInterval);
        });

        const toggleBtn = document.getElementById('toggleChat'),
            chatContainer = document.getElementById('chatContainer');
        let isOpen = false;

        toggleBtn.addEventListener('click', () => {
            isOpen = !isOpen;
            chatContainer.style.display = isOpen ? 'block' : 'none';
            toggleBtn.querySelector('i').className = isOpen ? 'bi bi-chat-dots-fill' : 'bi bi-chat-dots';
        });
    </script>
</body>

</html>
