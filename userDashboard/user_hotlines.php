<?php
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Emergency Hotlines | CICS AlertSOS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-gray-100 font-sans text-gray-800">

<custom-navbar></custom-navbar>
<custom-sidebar></custom-sidebar>

<main class="pt-28 md:pt-24 md:ml-64 px-4 md:px-8">
    <h1 class="text-3xl sm:text-4xl font-bold text-red-600 mb-6 flex items-center gap-2">
      <i data-feather="phone" class="w-6 h-6 text-red-600"></i>
      Emergency Hotlines
    </h1>
    <div id="hotlineList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
  </div>
</main>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>
<script>
feather.replace();

async function loadHotlines() {
  const res = await fetch("hotline_api.php");
  const data = await res.json();
  const list = document.getElementById('hotlineList');
  list.innerHTML = '';

  data.forEach(h => {
    const card = document.createElement('div');
    card.className = `
      bg-white border border-gray-200 rounded-2xl p-6 flex flex-col justify-between 
      shadow-md hover:shadow-xl transition transform hover:-translate-y-1
    `;
    card.innerHTML = `
      <div class="flex flex-col gap-3">
        <!-- Organization -->
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-900">${h.organization}</h2>
          <i data-feather="briefcase" class="w-5 h-5 text-gray-400"></i>
        </div>

        <!-- Description -->
        <p class="text-gray-600 text-sm">${h.description}</p>

        <!-- Phone Number -->
        <div class="flex items-center gap-3 mt-2">
          <i data-feather="phone" class="w-5 h-5 text-red-600"></i>
          <a href="tel:${h.phone_number}" class="text-red-700 font-bold text-lg bg-red-100 px-3 py-1 rounded-full shadow-sm hover:bg-red-200 transition">
            ${h.phone_number}
          </a>
        </div>
      </div>

      <!-- Emergency Note -->
      <div class="mt-4 border-t border-gray-100 pt-2">
        <span class="text-sm text-red-500 italic">Call immediately in case of emergency</span>
      </div>
    `;
    list.appendChild(card);
  });

  feather.replace();
}

loadHotlines();
</script>
</body>
</html>
