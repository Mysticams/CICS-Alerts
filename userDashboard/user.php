<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['student', 'faculty', 'staff'])) {
    header("Location: ../index.php");
    exit;
}

$pdo = pdo();
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, sos_active, lat, lng FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../loginSignup/login.php");
    exit;
}

$lastLat = is_numeric($user['lat']) ? (float)$user['lat'] : 14.167;
$lastLng = is_numeric($user['lng']) ? (float)$user['lng'] : 121.241;
$sosActive = (int)$user['sos_active'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>User Dashboard | SOS</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --red: #d32f2f;
            --dark-red: #b71c1c;
            --bg: #fff0f0;
            --card: #fff;
            --muted: #6b6b6b;
            --green: #2e7d32;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background: var(--bg);
            color: #222;
        }

        custom-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 260px;
            z-index: 10000;
            transition: all 0.3s ease;
        }

        custom-navbar{
    position:fixed;
    top:0;left:260px;right:0;
    height:60px;
    z-index:20000; /* <<< PUT NAVBAR IN FRONT OF SIDEBAR */
    display:flex;align-items:center;justify-content:space-between;
    padding:0 20px;
    background:#fff;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}


        @media(max-width:991.98px) {
            custom-sidebar {
                width: 200px;
            }

            custom-navbar {
                left: 200px;
            }
        }

        @media(max-width:575.98px) {
            custom-sidebar {
                width: 0;
            }

            custom-navbar {
                left: 0;
            }
        }

        /* top bar with icon */
        .top-section {
            position: fixed;
            top: 60px;
            left: 260px;
            right: 0;
            height: 80px;
            background: #fff;
            z-index: 120;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        @media(max-width:991.98px) {
            .top-section {
                left: 200px;
            }
        }

        @media(max-width:575.98px) {
            .top-section {
                left: 0;
            }
        }

        #map {
            position: absolute;
            top: 140px;
            left: 260px;
            right: 0;
            bottom: 0;
            border-radius: 10px;
            overflow: hidden;
            z-index: 10;
        }

        @media(max-width:991.98px) {
            #map {
                left: 200px;
            }
        }

        @media(max-width:575.98px) {
            #map {
                left: 0;
            }
        }

        /* sos button */
        .float-center-bottom {
            position: fixed;
            left: 58%;
            transform: translateX(-50%);
            bottom: 22px;
            z-index: 40;
        }

        .sos-button {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(180deg, var(--red), var(--dark-red));
            color: #fff;
            border: 6px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.05rem;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            box-shadow: 0 18px 40px rgba(183, 28, 28, 0.25);
        }

        .gps-floating {
            position: fixed;
            right: 18px;
            bottom: 190px;
            z-index: 40;
            display: flex;
            gap: 8px;
            flex-direction: column;
        }

        .loc-btn {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            border: 2px solid var(--red);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        #chatContainer {
            position: fixed;
            bottom: 70px;
            right: 20px;
            width: 400px;
            height: 600px;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            z-index: 9999;
        }

        #toggleChat {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 110;
            background: var(--red);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>

    <custom-navbar>
        <div id="sosIndicator" style="display:<?php echo $sosActive ? 'flex' : 'none'; ?>;gap:6px;color:var(--red);font-weight:bold;">
            <div class="dot" style="width:10px;height:10px;border-radius:50%;background:var(--red);"></div>
            <span>SOS Active</span>
        </div>
    </custom-navbar>

    <custom-sidebar></custom-sidebar>

    <!-- New top section with red icon and title -->
    <div class="top-section">
        <i data-feather="alert-triangle" style="color:var(--red);width:32px;height:32px;"></i>
        <h2 style="font-size:1.6rem;font-weight:800;color:var(--red);margin:0;">SOS Control Center</h2>
    </div>

    <div id="app">
        <div id="map"></div>
    </div>

    <div class="float-center-bottom">
        <button id="sosBtn" class="sos-button" aria-pressed="<?php echo $sosActive ? 'true' : 'false'; ?>">
            <i data-feather="bell" style="width:42px;height:42px;color:#fff;"></i>
            <span id="sosLabel"><?php echo $sosActive ? 'Deactivate SOS' : 'Press SOS'; ?></span>
        </button>
    </div>

    <div class="gps-floating">
        <div class="loc-btn" id="zoomBtn" title="Zoom to my location"><i data-feather="navigation" style="color:var(--red);"></i></div>
        <div class="loc-btn" id="centerBtn" title="Center map"><i data-feather="crosshair" style="color:var(--red);"></i></div>
    </div>

    <button id="toggleChat"><i id="chatIcon" class="bi bi-chat-dots-fill"></i></button>
    <div id="chatContainer"><iframe src="sos/chat_user.php" style="width:100%;height:100%;border:none;"></iframe></div>

    <audio id="sosSound" src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" preload="auto" loop></audio>

    <script src="components/navbar.js"></script>
    <script src="components/sidebar.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        feather.replace();

        const sosEndpoint = 'sos/ajax_sos.php';
        let sosActive = <?php echo $sosActive ? 'true' : 'false'; ?>;
        let userLat = <?php echo json_encode($lastLat); ?>;
        let userLng = <?php echo json_encode($lastLng); ?>;
        let map, userMarker, trailPolyline, trailCoords = [],
            trailMaxPoints = 120;
        let alarmPlaying = false;

        function userIcon(isSOS) {
            const fill = isSOS ? '#d32f2f' : '#2e7d32';
            return L.divIcon({
                className: '',
                html: `<div class="user-marker-svg"><svg viewBox="0 0 24 24" width="44" height="44"><path d="M12 2C7.58 2 4 5.58 4 10c0 5.25 8 12 8 12s8-6.75 8-12c0-4.42-3.58-8-8-8z" fill="${fill}"></path><circle cx="12" cy="10" r="2.6" fill="#fff"></circle></svg></div>`,
                iconSize: [44, 52],
                iconAnchor: [22, 50],
                popupAnchor: [0, -46]
            });
        }

        function updateUserMarker(lat, lng, center = false) {
            trailCoords.push([lat, lng]);
            if (trailCoords.length > trailMaxPoints) trailCoords.shift();
            trailPolyline.setLatLngs(trailCoords);
            trailPolyline.setStyle({
                color: sosActive ? 'red' : 'green'
            });
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker([lat, lng], {
                icon: userIcon(sosActive)
            }).addTo(map);
            userMarker.bindPopup(`<strong>You</strong><br>SOS: ${sosActive?'YES':'No'}`);
            if (center) map.setView([lat, lng], 15);
        }

        function initMap() {
            map = L.map('map', {
                zoomControl: false
            }).setView([userLat, userLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            trailPolyline = L.polyline([], {
                color: sosActive ? 'red' : 'green',
                weight: 4,
                lineJoin: 'round',
                lineCap: 'round'
            }).addTo(map);
            updateUserMarker(userLat, userLng, true);
            L.control.zoom({
                position: 'topright'
            }).addTo(map);
        }

        function geoSuccess(pos) {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            updateUserMarker(userLat, userLng);
            sendLocation(userLat, userLng);
        }

        function geoError() {
            if (Number.isFinite(userLat) && Number.isFinite(userLng)) updateUserMarker(userLat, userLng, true);
        }

        function sendLocation(lat, lng) {
            $.post(sosEndpoint, {
                lat,
                lng,
                sos: sosActive ? 1 : 0
            });
        }

        function startAlarm() {
            if (alarmPlaying) return;
            $('#sosIndicator').show();
            $('#sosSound')[0]?.play().catch(() => {});
            alarmPlaying = true;
        }

        function stopAlarm() {
            if (!alarmPlaying) return;
            $('#sosIndicator').hide();
            $('#sosSound')[0].pause();
            $('#sosSound')[0].currentTime = 0;
            alarmPlaying = false;
        }

        function pollServerSOS() {
            $.getJSON(sosEndpoint + '?_=' + Date.now(), function(res) {
                if (!res || typeof res.sos_active === 'undefined') return;
                const serverSos = !!Number(res.sos_active);
                const forcedByAdmin = !!Number(res.forced_by_admin);
                if (sosActive !== serverSos) {
                    sosActive = serverSos;
                    $('#sosLabel').text(sosActive ? 'Deactivate SOS' : 'Press SOS');
                    updateUserMarker(userLat, userLng);
                    sosActive ? startAlarm() : stopAlarm();
                    if (forcedByAdmin && !sosActive) alert('Admin has resolved your SOS.');
                }
            });
        }

        $(document).ready(function() {
            initMap();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(geoSuccess, geoError, {
                    enableHighAccuracy: true,
                    timeout: 7000
                });
                setInterval(() => navigator.geolocation.getCurrentPosition(geoSuccess, geoError, {
                    enableHighAccuracy: true,
                    timeout: 5000
                }), 2000);
            }

            $('#sosBtn').on('click', function() {
                sosActive = !sosActive;
                $('#sosLabel').text(sosActive ? 'Deactivate SOS' : 'Press SOS');
                updateUserMarker(userLat, userLng);
                sosActive ? startAlarm() : stopAlarm();
                sendLocation(userLat, userLng);
            });

            $('#zoomBtn').on('click', () => {
                map.setView([userLat, userLng], 16);
                userMarker?.openPopup();
            });
            $('#centerBtn').on('click', () => {
                map.setView([userLat, userLng], 15);
            });
            setInterval(pollServerSOS, 3000);
            if (sosActive) startAlarm();

            // Chat toggle
            const chatContainer = $('#chatContainer');
            const toggleBtn = $('#toggleChat');
            const chatIcon = $('#chatIcon');
            let chatOpen = true;

            toggleBtn.on('click', () => {
                chatOpen = !chatOpen;
                const isMobile = $(window).width() <= 575.98;
                if (chatOpen) {
                    chatContainer.show();
                    chatContainer.css('z-index', isMobile ? 50 : 101);
                    chatIcon.removeClass('bi-chat-dots').addClass('bi-chat-dots-fill');
                } else {
                    chatContainer.hide();
                    chatIcon.removeClass('bi-chat-dots-fill').addClass('bi-chat-dots');
                }
            });
        });
    </script>
</body>

</html>