<?php
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Emergency Hotlines | CICS AlertSOS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
    @keyframes fade-in {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .animate-fade-in {
      animation: fade-in 0.3s ease forwards;
    }

    /* Toast Animations */
    @keyframes slide-in {
      from {
        opacity: 0;
        transform: translateX(50px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes slide-out {
      from {
        opacity: 1;
        transform: translateX(0);
      }

      to {
        opacity: 0;
        transform: translateX(50px);
      }
    }

    .toast-show {
      animation: slide-in 0.4s ease forwards;
    }

    .toast-hide {
      animation: slide-out 0.4s ease forwards;
    }
  </style>
</head>

<body class="bg-gray-100 font-sans text-gray-800">

  <custom-navbar></custom-navbar>
  <custom-sidebar></custom-sidebar>

  <main class="pt-28 md:pt-24 md:ml-64 px-4 md:px-8 transition-all duration-300">

    <!-- Header and Add Button -->
    <div id="headerSection" class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4 sm:gap-0 relative">
      <h1 class="text-3xl sm:text-4xl font-bold text-red-600 text-center sm:text-left flex items-center gap-2">
        <i data-feather="phone" class="w-6 h-6"></i>
        Manage Emergency Hotlines
      </h1>
      <button id="addHotlineBtn" class="flex items-center justify-center gap-2 bg-red-600 text-white px-6 py-3 rounded-xl hover:bg-red-700 transition shadow-md">
        <i data-feather="plus" class="w-5 h-5"></i> Add Hotline
      </button>
    </div>

    <!-- Hotline List -->
    <div id="hotlineList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>

    <!-- Modal -->
    <div id="hotlineModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 px-4">
      <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 w-full max-w-md animate-fade-in">
        <h2 id="modalTitle" class="text-2xl font-semibold mb-6 text-gray-900">Add Hotline</h2>
        <form id="hotlineForm" class="space-y-5">
          <input type="hidden" id="hotlineId">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Organization</label>
            <input type="text" id="orgName" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-red-500 focus:outline-none" placeholder="e.g. Fire Department" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <input type="text" id="orgDesc" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-red-500 focus:outline-none" placeholder="e.g. Handles fire emergencies" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
            <input type="text" id="orgPhone" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-red-500 focus:outline-none" placeholder="e.g. 911" required>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" id="cancelModalBtn" class="px-5 py-3 bg-gray-200 rounded-xl hover:bg-gray-300 transition">Cancel</button>
            <button type="submit" class="px-5 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition">Save</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <!-- Toast Container (Top Right) -->
  <div id="toastContainer" class="fixed top-24 right-5 z-50 flex flex-col items-end gap-3"></div>

  <script src="components/navbar.js"></script>
  <script src="components/sidebar.js"></script>
  <script>
    feather.replace();

    const apiUrl = "hotline_api.php";
    const list = document.getElementById('hotlineList');
    const modal = document.getElementById('hotlineModal');
    const form = document.getElementById('hotlineForm');
    const modalTitle = document.getElementById('modalTitle');
    const addBtn = document.getElementById('addHotlineBtn');
    const cancelBtn = document.getElementById('cancelModalBtn');
    const toastContainer = document.getElementById('toastContainer');
    let editId = null;

    // Toast function
    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `px-5 py-3 rounded-xl shadow-md text-white toast-show flex items-center justify-between w-full max-w-sm`;
      toast.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
      toast.innerHTML = `
    <span class="flex-1">${message}</span>
    <button class="ml-3 font-bold" onclick="this.parentElement.remove()">×</button>
  `;
      toastContainer.appendChild(toast);

      setTimeout(() => {
        toast.classList.add('toast-hide');
        setTimeout(() => toast.remove(), 400);
      }, 3000);
    }

    // Load hotlines
    async function loadHotlines() {
      try {
        const res = await fetch(apiUrl);
        const data = await res.json();
        renderHotlines(data);
      } catch (err) {
        console.error('Error loading hotlines:', err);
        showToast('Failed to load hotlines', 'error');
      }
    }

    // Render hotlines
    function renderHotlines(data) {
      list.innerHTML = '';
      data.forEach(h => {
        const card = document.createElement('div');
        card.className = `
      bg-white border border-gray-200 rounded-2xl p-6 flex flex-col justify-between shadow-md 
      hover:shadow-xl transition transform hover:-translate-y-1
    `;
        card.innerHTML = `
      <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-900">${h.organization}</h2>
          <i data-feather="briefcase" class="w-5 h-5 text-gray-400"></i>
        </div>
        <p class="text-gray-600 text-sm">${h.description}</p>
        <div class="flex items-center gap-3 mt-2">
          <i data-feather="phone" class="w-5 h-5 text-red-600"></i>
          <a href="tel:${h.phone_number}" class="text-red-700 font-bold text-lg bg-red-100 px-3 py-1 rounded-full shadow-sm hover:bg-red-200 transition">
            ${h.phone_number}
          </a>
        </div>
      </div>
      <div class="mt-6 flex justify-end gap-4">
        <button class="flex items-center text-blue-500 hover:text-blue-700 edit-btn gap-1" data-id="${h.id}">
          <i data-feather="edit-3" class="w-4 h-4"></i> Edit
        </button>
        <button class="flex items-center text-red-500 hover:text-red-700 delete-btn gap-1" data-id="${h.id}">
          <i data-feather="trash-2" class="w-4 h-4"></i> Delete
        </button>
      </div>
    `;
        list.appendChild(card);
      });
      feather.replace();
    }

    // Event listeners
    addBtn.addEventListener('click', () => {
      modal.classList.remove('hidden');
      form.reset();
      editId = null;
      modalTitle.textContent = "Add Hotline";
    });

    cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const data = {
        id: editId,
        organization: orgName.value,
        description: orgDesc.value,
        phone_number: orgPhone.value
      };
      const method = editId ? 'PUT' : 'POST';
      try {
        const res = await fetch(apiUrl, {
          method,
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        });
        const result = await res.json();
        modal.classList.add('hidden');
        loadHotlines();
        showToast(result.message || 'Hotline saved successfully!', 'success');
      } catch (err) {
        console.error('Error saving hotline:', err);
        showToast('Failed to save hotline', 'error');
      }
    });

    document.addEventListener('click', async e => {
      if (e.target.closest('.delete-btn')) {
        if (confirm('Delete this hotline?')) {
          const id = e.target.closest('.delete-btn').dataset.id;
          try {
            await fetch(apiUrl, {
              method: 'DELETE',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                id
              })
            });
            loadHotlines();
            showToast('Hotline deleted successfully!', 'success');
          } catch (err) {
            console.error('Error deleting hotline:', err);
            showToast('Failed to delete hotline', 'error');
          }
        }
      }

      if (e.target.closest('.edit-btn')) {
        const id = e.target.closest('.edit-btn').dataset.id;
        const res = await fetch(apiUrl);
        const data = await res.json();
        const h = data.find(item => item.id == id);
        orgName.value = h.organization;
        orgDesc.value = h.description;
        orgPhone.value = h.phone_number;
        editId = id;
        modalTitle.textContent = "Edit Hotline";
        modal.classList.remove('hidden');
      }
    });

    loadHotlines();
  </script>
</body>

</html>