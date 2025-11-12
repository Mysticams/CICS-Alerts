<?php
require '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['student', 'faculty', 'staff'])) {
    header("Location: ../loginSignup/login.php");
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
            background: var(--bg);
            font-family: Inter, Arial, sans-serif;
            color: #222;
        }

        #app {
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            box-sizing: border-box;
            z-index: 60; /* topbar above map */
            position: relative;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--red), #ff6f6f);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            box-shadow: 0 6px 20px rgba(211, 47, 47, 0.2);
        }

        .title {
            font-weight: 800;
            color: var(--dark-red);
            font-size: 1rem;
        }

        .sub {
            font-size: 0.75rem;
            color: var(--muted);
        }

        /* --- MAP --- */
        #map {
            position: absolute;
            top: 50px;
            left: 260px; /* Desktop sidebar width */
            right: 12px;
            bottom: 12px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            z-index: 10; /* behind sidebar */
        }

        /* Sidebar always on top */
        custom-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            z-index: 100 !important;
        }

        /* Medium screens */
        @media (max-width: 991.98px) {
            #map {
                left: 200px; /* narrower sidebar */
                top: 50px;
                right: 10px;
                bottom: 12px;
            }
        }

        /* Small screens / mobile */
        @media (max-width: 575.98px) {
            #map {
                left: 0;
                top: 46px;
                right: 6px;
                bottom: 16px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
                height: auto;
                padding: 8px;
            }
        }

        /* SOS Button */
        .float-center-bottom {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: 22px;
            z-index: 1600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sos-button {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(180deg, var(--red), var(--dark-red));
            color: white;
            border: 6px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 18px 40px rgba(183, 28, 28, 0.25), 0 6px 14px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.05rem;
            text-align: center;
            cursor: pointer;
            transition: transform .14s ease, box-shadow .14s ease;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            box-sizing: border-box;
        }

        .sos-button svg {
            width: 42px;
            height: 42px;
            fill: #fff;
        }

        .sos-button:hover {
            transform: scale(1.05);
        }

        /* GPS buttons */
        .gps-floating {
            position: fixed;
            right: 18px;
            bottom: 190px;
            z-index: 1600;
            display: flex;
            gap: 8px;
            flex-direction: column;
            align-items: center;
        }

        .loc-btn {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            border: 2px solid var(--red);
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            font-weight: 800;
            transition: transform .1s ease;
        }

        .loc-btn:active {
            transform: scale(.98);
        }

        .loc-btn svg {
            width: 28px;
            height: 28px;
            stroke: var(--red);
            stroke-width: 1.8;
            fill: none;
        }

        .loc-btn:hover {
            background: var(--red);
        }

        .loc-btn:hover svg {
            stroke: #fff;
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
            transition: background 0.2s ease;
        }

        #toggleChat:hover {
            background: var(--dark-red);
        }

        /* Responsive adjustments for mobile */
        @media (max-width: 991.98px) {
            .gps-floating {
                bottom: 160px;
                right: 14px;
            }
            .sos-button {
                width: 135px;
                height: 135px;
                font-size: 1rem;
            }
            #chatContainer {
                width: 300px;
                height: 450px;
            }
        }

        @media (max-width: 575.98px) {
            .gps-floating {
                bottom: 130px;
                right: 12px;
            }
            .sos-button {
                width: 120px;
                height: 120px;
                font-size: 0.9rem;
            }
            #chatContainer {
                width: 90%;
                height: 50%;
                bottom: 70px;
                right: 5%;
            }
        }
    </style>
</head>

<body>
    <custom-navbar class="relative z-50"></custom-navbar>
    <custom-sidebar class="relative z-50"></custom-sidebar>

    <div id="app">
        <div class="topbar">
            <div class="brand">
                <div class="logo">SOS</div>
                <div>
                    <div class="title">SOS — Live</div>
                    <div class="sub">Welcome, <?php echo htmlspecialchars($user['first_name']); ?></div>
                </div>
            </div>
            <div style="display:flex;gap:12px;align-items:center">
                <div class="sub">Last known shown if GPS off</div>
            </div>
        </div>

        <div id="map"></div>
    </div>

    <div class="float-center-bottom">
        <button id="sosBtn" class="sos-button" aria-pressed="<?php echo $sosActive ? 'true' : 'false'; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 4c.38 0 .72.21.88.55l7 14A1 1 0 0 1 19 20H5a1 1 0 0 1-.88-1.45l7-14A.99.99 0 0 1 12 4zm0 4a1 1 0 0 0-1 1v4a1 
        1 0 0 0 2 0V9a1 1 0 0 0-1-1zm0 8a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 12 16z" />
            </svg>
            <span id="sosLabel"><?php echo $sosActive ? 'Deactivate SOS' : 'Press SOS'; ?></span>
        </button>
    </div>

    <div class="gps-floating">
        <div class="loc-btn" id="zoomBtn" title="Zoom to my location">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 12 7 12s7-6.75 7-12c0-3.87-3.13-7-7-7zM12 11.5A2.5 2.5 0 1 0 12 6.5 2.5 2.5 0 0 0 12 11.5z"></path>
            </svg>
        </div>
        <div class="loc-btn" id="centerBtn" title="Center map">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <circle cx="12" cy="12" r="10" stroke-width="1.6" stroke="currentColor" fill="none"></circle>
                <path d="M14 10l-4 1 1 4 3-5z" />
            </svg>
        </div>
    </div>

    <button id="toggleChat" title="Toggle Chat">
        <i id="chatIcon" class="bi bi-chat-dots-fill"></i>
    </button>

    <div id="chatContainer">
        <iframe src="sos/chat_user.php" style="width:100%; height:100%; border:none;"></iframe>
    </div>

    <audio id="sosSound" src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" preload="auto" loop></audio>

    <script src="components/navbar.js"></script>
    <script src="components/sidebar.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
       const sosEndpoint = 'sos/ajax_sos.php';
        let sosActive = <?php echo $sosActive ? 'true' : 'false'; ?>;
        let userLat = <?php echo json_encode($lastLat); ?>;
        let userLng = <?php echo json_encode($lastLng); ?>;
        let map, userMarker, trailPolyline, trailCoords = [],
            trailMaxPoints = 120;
        let alarmPlaying = false,
            audioCtx = null,
            alarmOsc = null,
            alarmGain = null;

        /* --- MAP & USER MARKER --- */
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

        function userIcon(isSOS) {
            const fill = isSOS ? '#d32f2f' : '#2e7d32';
            const blinkClass = isSOS ? ' pulse-red' : '';
            const html = `<div class="user-marker-svg${blinkClass}"><svg viewBox="0 0 24 24" width="44" height="44" xmlns="http://www.w3.org/2000/svg">
        <path class="pin-fill" d="M12 2C7.58 2 4 5.58 4 10c0 5.25 8 12 8 12s8-6.75 8-12c0-4.42-3.58-8-8-8z" fill="${fill}"></path>
        <circle class="pin-center" cx="12" cy="10" r="2.6" fill="#fff"></circle>
    </svg></div>`;
            return L.divIcon({
                className: '',
                html,
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
            const pathEl = trailPolyline._path;
            if (pathEl) pathEl.classList.toggle('sos-trail', sosActive);
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker([lat, lng], {
                icon: userIcon(sosActive)
            }).addTo(map);
            userMarker.bindPopup(`<strong>You</strong><br>SOS: ${sosActive ? 'YES' : 'No'}`);
            if (center) map.setView([lat, lng], 15);
        }

        /* --- GEOLOCATION --- */
        function geoSuccess(pos) {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            updateUserMarker(userLat, userLng);
            sendLocation(userLat, userLng);
        }

        function geoError(err) {
            if (Number.isFinite(userLat) && Number.isFinite(userLng)) updateUserMarker(userLat, userLng, true);
        }

        function sendLocation(lat, lng) {
            $.post(sosEndpoint, {
                lat,
                lng,
                sos: sosActive ? 1 : 0
            }, () => {}, 'json');
        }

        /* --- ALARM --- */
        function startAlarm() {
            if (alarmPlaying) return;
            try {
                audioCtx = new(window.AudioContext || window.webkitAudioContext)();
                alarmOsc = audioCtx.createOscillator();
                alarmGain = audioCtx.createGain();
                alarmOsc.type = 'sine';
                alarmOsc.frequency.value = 880;
                alarmGain.gain.value = 0.12;
                alarmOsc.connect(alarmGain);
                alarmGain.connect(audioCtx.destination);
                alarmOsc.start();
                alarmPlaying = true;
                let up = true;
                window.alarmPulse = setInterval(() => {
                    alarmGain.gain.linearRampToValueAtTime(up ? 0.22 : 0.02, audioCtx.currentTime + 0.18);
                    up = !up;
                }, 360);
                $('#sosSound')[0]?.play().catch(() => {});
            } catch (e) {
                console.warn(e);
            }
        }

        function stopAlarm() {
            if (!alarmPlaying) return;
            try {
                if (alarmOsc) alarmOsc.stop();
                if (window.alarmPulse) clearInterval(window.alarmPulse);
                if (alarmGain) alarmGain.disconnect();
                if (alarmOsc) alarmOsc.disconnect();
                if (audioCtx) audioCtx.close();
            } catch (e) {
                console.warn(e);
            }
            $('#sosSound')[0]?.pause();
            $('#sosSound')[0].currentTime = 0;
            alarmOsc = alarmGain = audioCtx = null;
            alarmPlaying = false;
        }

        /* --- SERVER SOS POLL --- */
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

            // SOS button
            $('#sosBtn').on('click', function() {
                sosActive = !sosActive;
                $('#sosLabel').text(sosActive ? 'Deactivate SOS' : 'Press SOS');
                $(this).css('transform', 'scale(0.98)');
                setTimeout(() => $(this).css('transform', ''), 160);
                updateUserMarker(userLat, userLng);
                sosActive ? startAlarm() : stopAlarm();
                sendLocation(userLat, userLng);
            });

            // Zoom & center
            $('#zoomBtn').on('click', () => {
                map.setView([userLat, userLng], 16);
                userMarker?.openPopup();
            });
            $('#centerBtn').on('click', () => {
                map.setView([userLat, userLng], 15);
            });

            // Poll SOS from server
            setInterval(pollServerSOS, 3000);
            $('#sosLabel').text(sosActive ? 'Deactivate SOS' : 'Press SOS');
            if (sosActive) startAlarm();

            // Chat toggle
            const chatContainer = $('#chatContainer');
            const toggleBtn = $('#toggleChat');
            const chatIcon = $('#chatIcon');
            let chatOpen = true; // Chat visible initially

            toggleBtn.on('click', () => {
                chatOpen = !chatOpen;
                if (chatOpen) {
                    chatContainer.show();
                    chatIcon.removeClass('bi-chat-dots').addClass('bi-chat-dots-fill'); // filled when open
                } else {
                    chatContainer.hide();
                    chatIcon.removeClass('bi-chat-dots-fill').addClass('bi-chat-dots'); // outline when closed
                }
            });
        });
    </script>
</body>
</html>

