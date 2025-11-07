<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../loginSignup/login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin - SOS Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --red: #d32f2f;
            --dark-red: #b71c1c;
            --bg: #fff0f0;
            --card-bg: #fff;
        }

        body {
            background: var(--bg);
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .app-shell {
            display: flex;
            height: 100vh;
            gap: 12px;
            padding: 12px;
            box-sizing: border-box;
        }

        .sidebar {
            width: 360px;
            min-width: 260px;
            max-width: 420px;
            background: var(--card-bg);
            border: 2px solid #bb0000;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            overflow: auto;
            transition: transform .25s ease, width .25s ease;
        }

        .sidebar.collapsed {
            transform: translateX(-110%);
            width: 0;
            padding: 0;
            border: none;
            box-shadow: none;
            overflow: hidden;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .top-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .title {
            font-size: 1.2rem;
            color: var(--dark-red);
            font-weight: 700;
        }

        .toggle-sidebar-btn {
            border-radius: 10px;
            border: 2px solid var(--red);
            background: transparent;
            color: var(--red);
            padding: 6px 10px;
            font-weight: 700;
        }

        .toggle-sidebar-btn:hover {
            background: var(--red);
            color: white;
        }

        #map {
            height: calc(100vh - 120px);
            border-radius: 10px;
            border: 3px solid #bb0000;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .user-list {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sos-item,
        .normal-item {
            padding: 10px;
            border-radius: 10px;
            font-size: 0.95rem;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .sos-item {
            background: #ffefef;
            border-left: 6px solid #c00000;
            color: #7a0000;
        }

        .normal-item {
            background: #f5fff5;
            border-left: 6px solid #008000;
            color: #0a5b0a;
        }

        .user-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .user-actions .btn {
            font-weight: 700;
            border-radius: 10px;
        }

        .leaflet-div-icon.marker-red {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--red);
            border: 2px solid #fff;
            box-shadow: 0 0 10px 3px rgba(211, 47, 47, 0.5);
        }

        .leaflet-div-icon.marker-green {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #43a047;
            border: 2px solid #fff;
            box-shadow: 0 0 10px 3px rgba(67, 160, 71, 0.35);
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .35
            }
        }

        .leaflet-div-icon.marker-red.blink {
            animation: blink 1s infinite;
        }

        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                left: 12px;
                top: 12px;
                bottom: 12px;
                z-index: 1400;
                width: 78%;
                max-width: 420px;
            }

            #map {
                height: calc(100vh - 120px);
            }
        }

        .badge-red {
            background: var(--red);
            color: white;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 10px;
        }

        /* Chat container */
        #chatContainer {
            position: fixed;
            bottom: 70px;
            right: 20px;
            width: 400px;
            height: 600px;
            z-index: 9999;
            border-radius: 10px;
            overflow: hidden;
            display: block;
            transition: none;
            /* remove any opacity/transform transition */
            opacity: 1;
            /* ensure fully opaque */
        }



        #toggleChat {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
            background: var(--red);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="mb-1" style="color:#a00000;font-weight:800;">Admin — Live SOS</h4>
                    <div style="font-size:0.9rem;color:#800;">Overview & user actions</div>
                </div>
                <div>
                    <button id="closeSidebar" class="btn btn-sm btn-outline-danger">Close</button>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>Active SOS</strong>
                    <div id="sosCounter" class="badge-red" style="display:inline-block;margin-left:8px;">0</div>
                </div>
                <div>
                    <button id="refreshBtn" class="btn btn-sm btn-danger">Refresh</button>
                </div>
            </div>

            <div class="user-list" id="users" aria-live="polite"></div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div class="top-left">
                    <button id="openSidebar" class="toggle-sidebar-btn">☰ Users</button>
                    <div class="title">Live SOS Map (Admin)</div>
                </div>
            </div>
            <div id="map"></div>
        </main>
    </div>

    <!-- Chat toggle button -->
    <button id="toggleChat"><i class="bi bi-chat-dots-fill" id="chatIcon"></i></button>

    <!-- Chat container -->
    <div id="chatContainer">
        <iframe src="chat_admin.php" style="width:100%; height:100%; border:none;"></iframe>
    </div>

    <audio id="sosAlert" src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" preload="auto" loop></audio>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        const endpoints = {
            users: 'ajax_admin_users.php',
            resolve: 'ajax_resolve_sos.php'
        };
        let map = L.map('map').setView([14.167, 121.241], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let userMarkers = {},
            pollInterval = 3000,
            sosSound = document.getElementById('sosAlert'),
            lastSOSCount = 0;

        if ("Notification" in window && Notification.permission !== "granted") Notification.requestPermission();

        function notifyAdmin(name) {
            if (Notification.permission === "granted") new Notification("🚨 SOS Alert!", {
                body: `${name} activated SOS!`
            });
            const orig = document.title;
            document.title = "🚨 SOS ALERT!";
            setTimeout(() => document.title = orig, 3500);
        }

        function fetchUsers() {
            $.getJSON(endpoints.users, function(res) {
                if (!res || res.status !== 'ok') return;
                renderUsers(res.users || []);
            }).fail(() => console.warn('Failed to fetch users.'));
        }

        function renderUsers(users) {
            const list = $('#users').empty();
            let activeSOS = 0;
            let newSOSUsers = [];
            users.forEach(u => {
                const lat = parseFloat(u.lat),
                    lng = parseFloat(u.lng);
                if (isNaN(lat) || isNaN(lng)) return;
                const iconCls = u.sos_active ? 'marker-red blink' : 'marker-green';
                const icon = L.divIcon({
                    className: `leaflet-div-icon ${iconCls}`
                });

                if (!userMarkers[u.id]) {
                    userMarkers[u.id] = L.marker([lat, lng], {
                        icon
                    }).addTo(map);
                    userMarkers[u.id].bindPopup(`<strong>${escapeHtml(u.name)}</strong><br>SOS: ${u.sos_active ? 'YES' : 'No'}`);
                } else {
                    userMarkers[u.id].setLatLng([lat, lng]).setIcon(icon);
                    userMarkers[u.id].getPopup().setContent(`<strong>${escapeHtml(u.name)}</strong><br>SOS: ${u.sos_active ? 'YES' : 'No'}`);
                }

                if (u.sos_active) {
                    activeSOS++;
                    if (!userMarkers[u.id].isSOS) newSOSUsers.push(u);
                    userMarkers[u.id].isSOS = true;
                } else {
                    userMarkers[u.id].isSOS = false;
                }

                const item = $('<div>').addClass(u.sos_active ? 'sos-item' : 'normal-item');
                const timeStr = u.updated_ts ? new Date(u.updated_ts * 1000).toLocaleString() : '';
                item.html(`<div style="display:flex;justify-content:space-between;align-items:center;"><div style="flex:1"><strong>${escapeHtml(u.name)}</strong><br><small>Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}</small><br><small class="text-muted">${timeStr}</small></div></div>`);
                const actions = $('<div class="user-actions mt-2"></div>');
                const focusBtn = $('<button class="btn btn-sm btn-outline-danger">Focus</button>').on('click', () => {
                    map.setView([lat, lng], 16);
                    if (userMarkers[u.id]) userMarkers[u.id].openPopup();
                });
                actions.append(focusBtn);
                if (u.sos_active) {
                    const resolveBtn = $('<button class="btn btn-sm btn-danger">Resolve</button>').on('click', () => {
                        resolveSOS(u.id);
                    });
                    actions.append(resolveBtn);
                }
                item.append(actions);
                list.append(item);
            });

            $('#sosCounter').text(activeSOS);
            if (activeSOS > lastSOSCount) {
                sosSound.currentTime = 0;
                sosSound.play().catch(() => {});
                newSOSUsers.forEach(u => {
                    notifyAdmin(u.name);
                    map.setView([parseFloat(u.lat), parseFloat(u.lng)], 16);
                    if (userMarkers[u.id]) userMarkers[u.id].openPopup();
                });
            }
            if (activeSOS === 0) {
                sosSound.pause();
                sosSound.currentTime = 0;
            }
            lastSOSCount = activeSOS;
        }

        function resolveSOS(userId) {
            if (!confirm('Mark this SOS as resolved?')) return;
            $.post(endpoints.resolve, {
                user_id: userId
            }, function(res) {
                if (res && res.status === 'ok') {
                    if (userMarkers[userId]) {
                        const greenIcon = L.divIcon({
                            className: 'leaflet-div-icon marker-green'
                        });
                        userMarkers[userId].setIcon(greenIcon);
                        userMarkers[userId].isSOS = false;
                    }
                    fetchUsers();
                } else {
                    alert('Failed to resolve SOS.');
                }
            }, 'json').fail(() => alert('Network error while resolving SOS.'));
        }

        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"'\/]/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;'
            } [c]));
        }

        $(document).ready(function() {
            $('#closeSidebar').on('click', () => $('#sidebar').addClass('collapsed'));
            $('#openSidebar').on('click', () => $('#sidebar').removeClass('collapsed'));
            $('#refreshBtn').on('click', fetchUsers);
            fetchUsers();
            setInterval(fetchUsers, pollInterval);
        });

        const toggleBtn = document.getElementById('toggleChat');
        const chatContainer = document.getElementById('chatContainer');
        const chatIcon = document.getElementById('chatIcon');
        let isOpen = true;

        toggleBtn.addEventListener('click', () => {
            if (isOpen) {
                chatContainer.style.display = 'none';
                chatIcon.className = 'bi bi-chat-dots';
                isOpen = false;
            } else {
                chatContainer.style.display = 'block';
                chatIcon.className = 'bi bi-chat-dots-fill';
                isOpen = true;
            }
        });
    </script>
</body>

</html>