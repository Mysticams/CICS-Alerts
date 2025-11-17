<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | CICS AlertSOS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <style>
    body {
      background-color: #fff;
    }

    .table-header {
      background-color: #fee2e2;
      font-weight: 600;
    }

    .btn-red {
      background-color: #ef4444;
      color: white;
    }

    .btn-red:hover {
      background-color: #dc2626;
    }

    .btn-green {
      background-color: #22c55e;
      color: white;
    }

    .btn-green:hover {
      background-color: #16a34a;
    }
  </style>
</head>

<body class="font-sans">

  <!-- Navbar and Sidebar -->
  <custom-navbar></custom-navbar>
  <custom-sidebar></custom-sidebar>
  <div id="overlay" class="fixed inset-0 bg-black bg-opacity-40 z-[900] hidden md:hidden"></div>

  <main class="pt-20 lg:pt-24 p-6 lg:ml-64 min-h-screen">

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-red-700 flex items-center gap-2">
        <i data-feather="file-text" class="w-7 h-7"></i> Incident Reports
      </h1>
    </div>

    <!-- Tabs -->
    <div class="flex mb-4 border-b border-red-300">
      <button id="tabActive" class="px-4 py-2 -mb-px font-semibold text-red-600 border-b-2 border-red-600">Active Incidents</button>
      <button id="tabHistory" class="px-4 py-2 font-semibold text-gray-600 hover:text-red-600">Resolved History</button>
    </div>

    <!-- Filters (Only Type) -->
    <div id="activeFilters" class="flex flex-col md:flex-row gap-4 mb-6">
      <select id="filterType" class="px-3 py-2 border rounded-md">
        <option value="">All Types</option>
        <option value="Fire">Fire</option>
        <option value="Medical">Medical</option>
        <option value="Security">Security</option>
        <option value="Natural Disaster">Natural Disaster</option>
        <option value="Other">Other</option>
      </select>
    </div>

    <!-- Active Table -->
    <div id="activeTableContainer" class="overflow-x-auto bg-white shadow-lg rounded-2xl border border-red-100">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="table-header">
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Description</th>
            <th>Status</th>
            <th>Location</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="incidentTable" class="divide-y divide-gray-200"></tbody>
      </table>
    </div>

    <!-- History Table -->
    <div id="historyTableContainer" class="overflow-x-auto bg-white shadow-lg rounded-2xl border border-red-100 hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="table-header">
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Description</th>
            <th>Status</th>
            <th>Location</th>
            <th>Resolved At</th>
          </tr>
        </thead>
        <tbody id="historyTable" class="divide-y divide-gray-200"></tbody>
      </table>
    </div>

    <!-- Incident Modal -->
    <div id="incidentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
      <div class="bg-white rounded-2xl shadow-lg w-full max-w-2xl p-6 relative">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700"><i data-feather="x"></i></button>
        <h2 class="text-xl font-bold mb-4 text-red-600">Incident Details</h2>
        <div id="modalContent"></div>
        <div id="modalMap" class="w-full h-64 border rounded-md mt-4"></div>
      </div>
    </div>

  </main>

  <script src="components/navbar.js"></script>
  <script src="components/sidebar.js"></script>
  <script>
    feather.replace();

    // Sidebar toggle
    const overlay = document.getElementById("overlay");
    const sidebar = document.querySelector("custom-sidebar");
    window.addEventListener("toggle-sidebar", () => {
      sidebar.classList.toggle("open");
      overlay.classList.toggle("hidden");
      overlay.classList.toggle("active");
    });
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("open");
      overlay.classList.add("hidden");
    });

    // Tabs
    const tabActive = document.getElementById('tabActive');
    const tabHistory = document.getElementById('tabHistory');
    const activeTableContainer = document.getElementById('activeTableContainer');
    const historyTableContainer = document.getElementById('historyTableContainer');
    const activeFilters = document.getElementById('activeFilters');

    tabActive.addEventListener('click', () => {
      tabActive.classList.add('text-red-600', 'border-b-2', 'border-red-600');
      tabHistory.classList.remove('text-red-600', 'border-b-2', 'border-red-600');
      tabHistory.classList.add('text-gray-600');
      activeTableContainer.classList.remove('hidden');
      activeFilters.classList.remove('hidden');
      historyTableContainer.classList.add('hidden');
    });

    tabHistory.addEventListener('click', () => {
      tabHistory.classList.add('text-red-600', 'border-b-2', 'border-red-600');
      tabActive.classList.remove('text-red-600', 'border-b-2', 'border-red-600');
      tabActive.classList.add('text-gray-600');
      historyTableContainer.classList.remove('hidden');
      activeTableContainer.classList.add('hidden');
      activeFilters.classList.add('hidden');
    });

    // Incident arrays
    let incidents = [];
    let history = [];
    let modalMapInstance = null;
    let modalMarker = null;

    // Load incidents every 5s
    async function loadIncidents() {
      try {
        const res = await fetch('get_incidents.php');
        const data = await res.json();
        incidents = data.filter(i => i.status !== 'Resolved');
        history = data.filter(i => i.status === 'Resolved');
        renderTable(incidents);
        renderHistory(history);
      } catch (err) {
        console.error('Failed to load incidents:', err);
      }
    }
    setInterval(loadIncidents, 5000);
    loadIncidents();

    // Render Active Table
    function renderTable(data) {
      const tbody = document.getElementById('incidentTable');
      tbody.innerHTML = '';
      data.forEach((i, index) => {
        tbody.innerHTML += `<tr>
      <td class="px-4 py-2">${index+1}</td>
      <td class="px-4 py-2">${i.type}</td>
      <td class="px-4 py-2">${i.description}</td>
      <td class="px-4 py-2">
        <span class="px-2 py-1 rounded-full ${i.status==='Pending'?'bg-yellow-200 text-yellow-800':'bg-blue-200 text-blue-800'}">${i.status}</span>
      </td>
      <td class="px-4 py-2">
        <i data-feather="map-pin" class="text-red-600 w-4 h-4 inline"></i> ${i.latitude}, ${i.longitude}
      </td>
      <td class="px-4 py-2 flex gap-2">
        <button class="btn-red px-3 py-1 rounded-md" onclick="viewIncident(${i.id})"><i data-feather="eye"></i> View</button>
        <button class="btn-green px-3 py-1 rounded-md" onclick="updateStatus(${i.id})"><i data-feather="check-circle"></i> Mark Resolved</button>
      </td>
    </tr>`;
      });
      feather.replace();
    }

    // Render History Table
    function renderHistory(data) {
      const tbody = document.getElementById('historyTable');
      tbody.innerHTML = '';
      data.forEach((h, index) => {
        tbody.innerHTML += `<tr class="cursor-pointer hover:bg-gray-100" onclick="viewIncident(${h.id})">
      <td class="px-4 py-2">${index+1}</td>
      <td class="px-4 py-2">${h.type}</td>
      <td class="px-4 py-2">${h.description}</td>
      <td class="px-4 py-2"><span class="px-2 py-1 rounded-full bg-green-200 text-green-800">${h.status}</span></td>
      <td class="px-4 py-2"><i data-feather="map-pin" class="text-red-600 w-4 h-4 inline"></i> ${h.latitude}, ${h.longitude}</td>
      <td class="px-4 py-2">${h.resolved_at || new Date().toLocaleString()}</td>
    </tr>`;
      });
      feather.replace();
    }

    // View modal
    function viewIncident(id) {
      const incident = [...incidents, ...history].find(i => i.id == id);
      if (!incident) return;

      let mediaHtml = '';
      if (incident.media) {
        try {
          const mediaFiles = JSON.parse(incident.media);
          if (mediaFiles.length) {
            mediaHtml = '<div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">';
            mediaFiles.forEach(file => {
              const ext = file.split('.').pop().toLowerCase();
              if (['mp4', 'webm', 'ogg'].includes(ext)) {
                mediaHtml += `<video controls class="w-full h-32 object-cover rounded-md"><source src="../${file}" type="video/${ext}"></video>`;
              } else {
                mediaHtml += `<img src="../${file}" class="w-full h-32 object-cover rounded-md" />`;
              }
            });
            mediaHtml += '</div>';
          }
        } catch (e) {
          console.error(e);
        }
      }

      document.getElementById('modalContent').innerHTML = `
    <p><strong>Type:</strong> ${incident.type}</p>
    <p><strong>Description:</strong> ${incident.description}</p>
    <p><strong>Status:</strong> <span class="px-2 py-1 rounded-full ${incident.status==='Pending'?'bg-yellow-200 text-yellow-800':'bg-blue-200 text-blue-800'}">${incident.status}</span></p>
    <p><strong>Location:</strong> <i data-feather="map-pin" class="text-red-600 w-4 h-4 inline"></i> ${incident.latitude}, ${incident.longitude}</p>
    ${mediaHtml}
  `;

      const modal = document.getElementById('incidentModal');
      modal.classList.remove('hidden');

      // Initialize map only once
      if (!modalMapInstance) {
        modalMapInstance = L.map('modalMap').setView([incident.latitude, incident.longitude], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(modalMapInstance);
      } else {
        modalMapInstance.setView([incident.latitude, incident.longitude], 16);
        if (modalMarker) modalMapInstance.removeLayer(modalMarker);
      }

      // Add marker
      modalMarker = L.marker([incident.latitude, incident.longitude])
        .addTo(modalMapInstance)
        .bindPopup('Incident Location')
        .openPopup();

      // Fix map rendering even if hidden initially
      setTimeout(() => {
        modalMapInstance.invalidateSize();
      }, 200);

      feather.replace();
    }

    function closeModal() {
      document.getElementById('incidentModal').classList.add('hidden');
    }

    // Update status
    async function updateStatus(id) {
      try {
        const res = await fetch('update_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `id=${id}&status=Resolved`
        });
        const data = await res.json();
        if (data.success) {
          const incident = incidents.find(i => i.id == id);
          if (incident) {
            incident.status = 'Resolved';
            incident.resolved_at = data.resolved_at || new Date().toLocaleString();
            if (!history.find(h => h.id == id)) history.unshift(incident);
            renderTable(incidents);
            renderHistory(history);
          }
        }
      } catch (err) {
        console.error(err);
      }
    }

    // Filter by Type only
    document.getElementById('filterType').addEventListener('change', filterTable);

    function filterTable() {
      const type = document.getElementById('filterType').value;
      renderTable(incidents.filter(i => !type || i.type === type));
    }
  </script>
</body>

</html>