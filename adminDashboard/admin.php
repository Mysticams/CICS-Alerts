<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// Only allow admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../loginSignup/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin - SOS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind & Feather Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Bootstrap Icons for chat toggle -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        body { font-family: Arial, sans-serif; background: #fff0f0; }

        /* Map */
        #map { height: calc(100vh - 100px); border-radius: 1rem; border: 3px solid #bb0000; }

        /* SOS badges */
        .badge-red { background: #d32f2f; color: white; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 0.5rem; }

        /* Chat container */
        #chatContainer {
            position: fixed;
            bottom: 70px;
            right: 20px;
            width: 380px;
            height: 550px;
            z-index: 9999;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            display: block;
            border: 2px solid #d32f2f;
            background: white;
        }

        #toggleChat {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 50%;
            width: 55px;
            height: 55px;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s ease-in-out;
        }

        #toggleChat:hover { transform: scale(1.1); }

        .leaflet-div-icon.marker-red { width: 18px; height: 18px; border-radius: 50%; background: #d32f2f; border: 2px solid #fff; box-shadow: 0 0 10px 3px rgba(211, 47, 47, 0.5); }
        .leaflet-div-icon.marker-green { width: 18px; height: 18px; border-radius: 50%; background: #43a047; border: 2px solid #fff; box-shadow: 0 0 10px 3px rgba(67, 160, 71, 0.35); }

        @keyframes blink { 0%, 100% { opacity: 1 } 50% { opacity: .35 } }
        .leaflet-div-icon.marker-red.blink { animation: blink 1s infinite; }
    </style>
</head>

<body>

    <!-- Navbar -->
    <custom-navbar class="relative z-50"></custom-navbar>

    <!-- Sidebar -->
    <custom-sidebar class="relative z-40"></custom-sidebar>

    <!-- Main content -->
    <main class="pt-20 lg:pt-24 p-4 lg:ml-64 min-h-screen transition-all duration-300">

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Live SOS Map (Admin)</h1>
            <div class="flex items-center gap-2">
                <span class="font-semibold">Active SOS:</span>
                <span id="sosCounter" class="badge-red">0</span>
            </div>
        </div>

        <div class="flex gap-4 flex-col lg:flex-row">
            <!-- Map -->
            <div class="flex-1">
                <div id="map"></div>
            </div>

            <!-- User list -->
            <div class="w-full lg:w-80 bg-white rounded-2xl shadow-lg p-4 overflow-y-auto max-h-[80vh]">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="font-bold text-gray-700">User List</h2>
                    <button id="refreshBtn" class="bg-red-700 text-white px-3 py-1 rounded-md font-semibold hover:bg-red-800">Refresh</button>
                </div>
                <div id="users" class="flex flex-col gap-2" aria-live="polite"></div>
            </div>
        </div>
    </main>

    <!-- Chat Toggle -->
    <button id="toggleChat"><i class="bi bi-chat-dots-fill"></i></button>
    <div id="chatContainer">
        <iframe src="sos/chat_admin.php" style="width:100%; height:100%; border:none;"></iframe>
    </div>

    <!-- SOS Alert Sound -->
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

        let map = L.map('map').setView([14.167, 121.241], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let userMarkers = {}, pollInterval = 3000, sosSound = document.getElementById('sosAlert'), lastSOSCount = 0;

        if ("Notification" in window && Notification.permission !== "granted") Notification.requestPermission();

        function notifyAdmin(name) {
            if (Notification.permission === "granted") new Notification("🚨 SOS Alert!", { body: `${name} activated SOS!` });
            const orig = document.title;
            document.title = "🚨 SOS ALERT!";
            setTimeout(() => document.title = orig, 3500);
        }

        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"'\/]/g, c => ({"&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;", "/": "&#x2F;"}[c]));
        }

        function fetchUsers() {
            $.getJSON(endpoints.users, function(res) {
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

                const item = $('<div>').addClass(u.sos_active ? 'bg-red-100 p-2 rounded-md' : 'bg-green-100 p-2 rounded-md');
                const timeStr = u.updated_ts ? new Date(u.updated_ts * 1000).toLocaleString() : '';
                item.html(`<div class="font-semibold">${escapeHtml(u.name)}</div><small class="text-gray-600">Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)} | ${timeStr}</small>`);

                const actions = $('<div class="flex gap-2 mt-1"></div>');

                // Focus button
                actions.append($('<button class="bg-gray-200 px-2 py-1 rounded-md">Focus</button>').on('click', () => {
                    map.setView([lat, lng], 16); 
                    if (userMarkers[u.id]) userMarkers[u.id].openPopup();
                }));

                // Chat button
                actions.append($('<button class="bg-blue-600 text-white px-2 py-1 rounded-md">Chat</button>').on('click', () => {
                    chatContainer.style.display = 'block';
                    toggleBtn.querySelector('i').className = 'bi bi-chat-dots-fill';
                    const iframe = chatContainer.querySelector('iframe');
                    iframe.src = `sos/chat_admin.php?user_id=${u.id}&user_name=${encodeURIComponent(u.name)}`;
                    isOpen = true;
                }));

                // Resolve button
                if (u.sos_active) actions.append($('<button class="bg-red-600 text-white px-2 py-1 rounded-md">Resolve</button>').on('click', () => resolveSOS(u.id)));

                item.append(actions);
                list.append(item);
            });

            $('#sosCounter').text(activeSOS);

            if (activeSOS > lastSOSCount) {
                sosSound.currentTime = 0; sosSound.play().catch(() => {});
                newSOSUsers.forEach(u => { notifyAdmin(u.name); map.setView([parseFloat(u.lat), parseFloat(u.lng)], 16); if (userMarkers[u.id]) userMarkers[u.id].openPopup(); });
            }

            if (activeSOS === 0) { sosSound.pause(); sosSound.currentTime = 0; }

            lastSOSCount = activeSOS;
        }

        function resolveSOS(userId) {
            if (!confirm('Mark this SOS as resolved?')) return;
            $.post(endpoints.resolve, { user_id: userId }, function(res) {
                if (res && res.status === 'ok') { 
                    if (userMarkers[userId]) { 
                        userMarkers[userId].setIcon(L.divIcon({ className: 'leaflet-div-icon marker-green' })); 
                        userMarkers[userId].isSOS = false; 
                    } 
                    fetchUsers(); 
                } else { alert('Failed to resolve SOS.'); }
            }, 'json').fail(() => alert('Network error while resolving SOS.'));
        }

        $(document).ready(function() { 
            $('#refreshBtn').on('click', fetchUsers); 
            fetchUsers(); 
            setInterval(fetchUsers, pollInterval); 
        });

        const toggleBtn = document.getElementById('toggleChat'), chatContainer = document.getElementById('chatContainer');
        let isOpen = true;
        toggleBtn.addEventListener('click', () => {
            if (isOpen) { chatContainer.style.display = 'none'; toggleBtn.querySelector('i').className = 'bi bi-chat-dots'; }
            else { chatContainer.style.display = 'block'; toggleBtn.querySelector('i').className = 'bi bi-chat-dots-fill'; }
            isOpen = !isOpen;
        });
    </script>
</body>
</html>
