<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Incident | CICS AlertSOS</title>
  <link rel="stylesheet" href="../style.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <style>
    body {
      background-color: #f9fafb;
      font-family: 'Inter', sans-serif;
    }

    main {
      margin-top: 80px;
      margin-left: 250px;
      transition: margin-left 0.3s ease-in-out;
      z-index: 800;
      position: relative;
    }

    /* Mobile view adjustments */
    @media (max-width: 768px) {
      custom-sidebar {
        transform: translateX(-100%);
      }
      custom-sidebar.open {
        transform: translateX(0);
      }
      main {
        margin-left: 0;
      }
      #overlay.active {
        display: block;
      }
    }

    #overlay {
      display: none;
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 850;
    }

    /* Feather icons red */
    [data-feather] {
      stroke: #dc2626;
    }

    /* Form styles */
    label {
      font-weight: 600;
      color: #374151; /* gray-700 */
    }
    input, select, textarea {
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #dc2626;
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
    }
    button[type="submit"]:hover {
      background-color: #b91c1c;
    }

    /* Alerts styling */
    .alert {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-radius: 10px;
      background-color: #fee2e2; /* light red */
      color: #b91c1c; /* red-700 */
      font-weight: 500;
      margin-bottom: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .alert i {
      stroke-width: 2.5;
      min-width: 24px;
      min-height: 24px;
    }

    .alert-text {
      flex: 1;
    }

    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      min-width: 280px;
      padding: 14px 18px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 12px;
      color: white;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 9999;
      opacity: 0;
      transform: translateY(-20px);
      transition: opacity 0.4s ease, transform 0.4s ease;
    }
    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }
    .toast.success {
      background-color: #16a34a;
    }
    .toast.error {
      background-color: #dc2626;
    }
    .toast i {
      stroke: white;
      stroke-width: 2.5;
    }

    /* Map popup styling */
    #map {
      border-radius: 10px;
    }
  </style>
</head>
<body>
  <!-- Navbar and Sidebar -->
  <custom-navbar></custom-navbar>
  <custom-sidebar></custom-sidebar>
  <div id="overlay"></div>

  <main class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-red-600 flex items-center gap-2">
        <i data-feather="file-text" class="w-7 h-7"></i> Report an Incident
      </h1>
    </div>

    <!-- Example alert -->
    <div class="alert">
      <i data-feather="alert-triangle" class="w-6 h-6"></i>
      <span class="alert-text">Make sure to provide accurate location for faster response.</span>
    </div>

    <form id="incidentForm" enctype="multipart/form-data" class="space-y-6">
      <div>
        <label>Incident Type</label>
        <select id="incidentType" name="incidentType" required class="w-full px-3 py-2 border rounded-md">
          <option value="" disabled selected>Select an incident type</option>
          <option value="Fire">Fire</option>
          <option value="Medical">Medical Emergency</option>
          <option value="Security">Security Issue</option>
          <option value="Natural Disaster">Natural Disaster</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div>
        <label>Description</label>
        <textarea id="description" name="description" rows="4" required class="w-full px-3 py-2 border rounded-md"></textarea>
      </div>

      <div>
        <label>Upload Photos/Videos</label>
        <input type="file" name="media[]" id="media" accept="image/*,video/*" multiple class="w-full p-2 border rounded-md">
      </div>

      <div>
        <label>Location</label>
        <div id="locationDisplay" class="p-3 bg-gray-100 border rounded-md mb-3 flex items-center gap-2">
          <i data-feather="map-pin" class="w-5 h-5 stroke-red-600"></i>
          <span>Detecting location...</span>
        </div>
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">
        <div id="map" class="w-full h-64 border rounded-md"></div>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md flex items-center gap-2">
          <i data-feather="send" class="w-5 h-5"></i> Submit Report
        </button>
      </div>
    </form>
  </main>

  <script src="components/navbar.js"></script>
  <script src="components/sidebar.js"></script>
  <script>
    feather.replace();

    const overlay = document.getElementById("overlay");
    const sidebar = document.querySelector("custom-sidebar");

    window.addEventListener("toggle-sidebar", () => {
      sidebar.classList.toggle("open");
      overlay.classList.toggle("active");
    });

    overlay.addEventListener("click", () => {
      sidebar.classList.remove("open");
      overlay.classList.remove("active");
    });

    // Map setup
    let map, marker;
    function initMap(lat, lng) {
      map = L.map('map').setView([lat, lng], 16);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OSM'
      }).addTo(map);
      marker = L.marker([lat, lng]).addTo(map).bindPopup("<i data-feather='map-pin'></i> Your Location").openPopup();
      feather.replace();
    }

    function updateMarker(lat, lng) {
      marker.setLatLng([lat, lng]).update().bindPopup("Current Location").openPopup();
      map.setView([lat, lng], map.getZoom());
      feather.replace();
    }

    // Geolocation
    window.onload = function() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
          const lat = pos.coords.latitude, lon = pos.coords.longitude;
          document.getElementById('latitude').value = lat;
          document.getElementById('longitude').value = lon;
          document.getElementById('locationDisplay').innerHTML = `<i data-feather="map-pin" class="w-5 h-5 stroke-red-600"></i> Latitude: <b>${lat.toFixed(6)}</b>, Longitude: <b>${lon.toFixed(6)}</b>`;
          feather.replace();
          initMap(lat, lon);

          navigator.geolocation.watchPosition(p => {
            const newLat = p.coords.latitude, newLon = p.coords.longitude;
            document.getElementById('latitude').value = newLat;
            document.getElementById('longitude').value = newLon;
            document.getElementById('locationDisplay').innerHTML = `<i data-feather="map-pin" class="w-5 h-5 stroke-red-600"></i> Latitude: <b>${newLat.toFixed(6)}</b>, Longitude: <b>${newLon.toFixed(6)}</b>`;
            feather.replace();
            updateMarker(newLat, newLon);
          }, err => console.error(err), { enableHighAccuracy: true, maximumAge: 1000 });

        }, () => {
          document.getElementById('locationDisplay').innerHTML = `<i data-feather="alert-triangle" class="w-5 h-5 stroke-red-600"></i> Location denied`;
          feather.replace();
          initMap(13.9404, 121.6202);
        }, { enableHighAccuracy: true });
      } else {
        document.getElementById('locationDisplay').innerHTML = `<i data-feather="x-circle" class="w-5 h-5 stroke-red-600"></i> Geolocation not supported`;
        feather.replace();
        initMap(13.9404, 121.6202);
      }
    }

    // Toast popup function
    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.innerHTML = `
        <i data-feather="${type === 'success' ? 'check-circle' : 'alert-triangle'}" class="w-6 h-6"></i>
        <span>${message}</span>
      `;
      document.body.appendChild(toast);
      feather.replace();

      setTimeout(() => toast.classList.add('show'), 50);
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
      }, 4000);
    }

    // Submit incident handler
    document.getElementById('incidentForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const res = await fetch('submit_incident.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        showToast('Incident submitted successfully!', 'success');
        this.reset();
      } else {
        showToast('Failed: ' + data.message, 'error');
      }
    });
  </script>
</body>
</html>
