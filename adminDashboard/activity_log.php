<?php
require '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Only allow admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../loginSignup/login.php");
    exit;
}

$pdo = pdo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<style>
    body {
        background-color: #fff6f6;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #b91c1c;
    }
    .main-container {
        margin-top: 70px;
        padding: 15px;
        margin-left: 250px; /* sidebar width for desktop */
    }
    h1 {
        font-weight: 700;
        margin-bottom: 20px;
        color: #b91c1c;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .card {
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border-radius: 12px;
        padding: 20px;
    }
    .table thead {
        background-color: #f87171;
        color: #fff;
    }
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #ffe4e4;
    }
    .table-hover tbody tr:hover {
        background-color: #ffd2d2;
        transition: 0.3s;
    }
    td, th {
        vertical-align: middle !important;
    }
    .btn-red {
        background-color: #b91c1c;
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
        transition: 0.3s;
    }
    .btn-red:hover { background-color: #991b1b; }
    .export-btn {
        border: 1px solid #b91c1c;
        color: #b91c1c;
        font-weight: 600;
        border-radius: 8px;
        transition: 0.3s;
    }
    .export-btn:hover {
        background-color: #b91c1c;
        color: #fff;
    }
    .user-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
        margin-right: 10px;
        color: #fff;
    }
    .active-sos { background-color: #b91c1c; animation: blink 1s infinite; }
    .safe-user { background-color: #f87171; }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

    /* Responsive adjustments */
    @media (max-width: 1200px) { /* Large screens */
        .main-container { margin-left: 0; padding: 15px; }
    }
    @media (max-width: 992px) { /* Medium devices / tablets */
        .main-container { margin-left: 0; padding: 10px; }
        h1 { font-size: 1.8rem; }
        .btn-red, .export-btn { font-size: 0.85rem; padding: 6px 10px; }
    }
    @media (max-width: 768px) { /* Small tablets / large phones */
        h1 { font-size: 1.6rem; }
        .user-icon { width: 25px; height: 25px; font-size: 12px; }
    }
    @media (max-width: 576px) { /* Mobile phones */
        .table-responsive { overflow-x: auto; }
        .d-flex { flex-direction: column !important; gap: 10px; }
        .btn-red, .export-btn { width: 100%; }
        h1 { font-size: 1.4rem; text-align: center; }
        .user-icon { width: 22px; height: 22px; font-size: 11px; }
    }
</style>
</head>
<body>

    <!-- Navbar -->
    <custom-navbar class="fixed-top z-50"></custom-navbar>

    <!-- Sidebar -->
    <custom-sidebar class="position-fixed z-40" style="top:0; left:0; height:100%; width:250px;"></custom-sidebar> 

<div class="main-container">
    <div class="card">
        <h1><i data-feather="activity"></i> User Activity Logs</h1>
        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
            <button id="clearLogsBtn" class="btn btn-red">Clear Logs</button>
            <button id="exportCsvBtn" class="btn export-btn">Export CSV</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover" id="logsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody id="logsBody">
                    <tr><td colspan="4" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
feather.replace();

// Fetch logs
async function fetchLogs() {
    try {
        const res = await fetch('fetch_logs.php');
        if (!res.ok) throw new Error('Failed to fetch logs');
        const logs = await res.json();
        const tbody = document.getElementById('logsBody');
        tbody.innerHTML = '';
        if(logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No logs found.</td></tr>';
        } else {
            logs.forEach(log => {
                const tr = document.createElement('tr');
                const initials = (log.first_name?.charAt(0) || 'U') + (log.last_name?.charAt(0) || 'L');
                const iconClass = log.action.toLowerCase().includes('login') ? 'active-sos' : 'safe-user';
                tr.innerHTML = `
                    <td>${log.id}</td>
                    <td><span class="user-icon ${iconClass}">${initials.toUpperCase()}</span> ${log.first_name} ${log.last_name}</td>
                    <td>${log.action}</td>
                    <td>${log.created_at}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (err) { console.error(err); }
}

// Refresh & Clear Logs
fetchLogs();
setInterval(fetchLogs, 5000);

document.getElementById('clearLogsBtn').addEventListener('click', async () => {
    if (!confirm('Are you sure you want to clear all activity logs?')) return;
    try {
        const res = await fetch('clear_logs.php', { method: 'POST' });
        const data = await res.json();
        if (data.success) { alert(data.message); fetchLogs(); }
        else alert('Failed: ' + data.message);
    } catch (err) { console.error(err); alert('Error clearing logs.'); }
});

// Export CSV
document.getElementById('exportCsvBtn').addEventListener('click', () => {
    const rows = document.querySelectorAll('#logsTable tr');
    let csv = [];
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => rowData.push('"' + col.innerText.replace(/"/g, '""') + '"'));
        csv.push(rowData.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'activity_logs.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
});
</script>
</body>
</html>
