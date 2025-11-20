<?php
require_once '../config.php';
$pdo = pdo();

// --- Reverse Geocoding Function ---
function getAddress($lat, $lng)
{
    if (!$lat || !$lng) return '';
    $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$lat&lon=$lng";
    $opts = ["http" => ["header" => "User-Agent: PHP UserManagement/1.0\r\n"]];
    $context = stream_context_create($opts);
    $json = @file_get_contents($url, false, $context);
    if (!$json) return '';
    $data = json_decode($json, true);
    return $data['display_name'] ?? '';
}

// --- Update user role via POST ---
$swalMessage = '';
$swalType = 'success';

if (isset($_POST['change_role']) && isset($_POST['user_id']) && isset($_POST['new_role'])) {
    $stmt = $pdo->prepare("UPDATE users SET role=:role WHERE id=:id");
    $stmt->execute([
        ':role' => $_POST['new_role'],
        ':id' => $_POST['user_id']
    ]);
    $swalMessage = 'Role updated successfully!';
    $swalType = 'success';
}

// --- Delete User (Instead of Edit) ---
if (isset($_POST['delete_user']) && isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=:id");
    $stmt->execute([':id' => $_POST['delete_id']]);
    $swalMessage = 'User deleted successfully!';
    $swalType = 'success';
}

// --- Handle Add Only ---
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, lat, lng, address, role) VALUES (:fname,:lname,:email,:phone,:lat,:lng,:address,:role)");
    $stmt->execute([
        ':fname' => $_POST['first_name'],
        ':lname' => $_POST['last_name'],
        ':email' => $_POST['email'],
        ':phone' => $_POST['phone'],
        ':lat' => $_POST['lat'] ?? null,
        ':lng' => $_POST['lng'] ?? null,
        ':address' => $_POST['address'] ?? null,
        ':role' => $_POST['role'] ?? "Student"
    ]);
    $swalMessage = 'User added successfully!';
    $swalType = 'success';
}

// --- Pagination ---
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY first_name ASC LIMIT :offset,:limit");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- AJAX Fetch User ---
if (isset($_GET['get_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
    $stmt->execute([':id' => (int)$_GET['get_user']]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: "Inter", sans-serif;
            background: #fff;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 100px 20px 20px 250px;
        }

        @media(max-width:1024px) {
            .container {
                padding-left: 200px;
            }
        }

        @media(max-width:768px) {
            .container {
                padding-left: 15px;
                padding-top: 80px;
            }
        }

        h1 {
            color: #dc2626;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }

        #searchContainer {
            position: relative;
            margin: 0 auto 1.5rem;
            max-width: 400px;
        }

        #searchInput {
            width: 100%;
            padding: 10px 40px 10px 35px;
            border: 1px solid #a10c24;
            border-radius: 25px;
            font-size: 14px;
            height: 42px;
        }

        .search-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #dc2626;
            pointer-events: none;
        }

        #searchClear {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 18px;
            color: #dc2626;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #B91C1C;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #fff8f8;
        }

        .action-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background: #B91C1C;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            transition: .2s;
        }

        .action-btn:hover {
            background: #dc2626;
        }

        .pagination a {
            padding: 6px 10px;
            border: 1px solid #a10c24;
            border-radius: 5px;
            margin: 0 3px;
            color: #B91C1C;
            text-decoration: none;
        }

        .pagination a.active {
            background: #B91C1C;
            color: #fff;
        }

        .mini-map {
            width: 150px;
            height: 100px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <custom-navbar class="fixed top-0 left-0 w-full z-50"></custom-navbar>
    <custom-sidebar class="fixed top-0 left-0 h-full z-40"></custom-sidebar>

    <div class="container">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-red-700 mb-8">User Management</h1>

        <div id="searchContainer">
            <i data-feather="search" class="search-icon"></i>
            <input type="text" id="searchInput" placeholder="Search user...">
            <button id="searchClear" onclick="clearSearch()">&times;</button>
        </div>

        <div class="overflow-x-auto shadow-lg rounded-lg border border-red-100">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Lat</th>
                        <th>Lng</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): $addr = getAddress($u['lat'], $u['lng']); ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone']) ?></td>
                            <td><?= htmlspecialchars($u['role'] ?: 'Student') ?></td>
                            <td><?= $u['lat'] ?></td>
                            <td><?= $u['lng'] ?></td>
                            <td><?= htmlspecialchars($addr) ?></td>
                            <td class="flex justify-center gap-2">
                                <button onclick="changeRole(<?= $u['id'] ?>)" class="action-btn" title="Change Role"><i data-feather="user-check"></i></button>
                                <button onclick="deleteUser(<?= $u['id'] ?>)" class="action-btn" title="Delete"><i data-feather="trash-2"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination mt-6 flex justify-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Change Role FORM (hidden submit) -->
    <form id="roleForm" method="post" style="display:none;">
        <input type="hidden" name="change_role" value="1">
        <input type="hidden" name="user_id" id="roleUserId">
        <input type="hidden" name="new_role" id="roleInput">
    </form>

    <!-- DELETE FORM -->
    <form id="deleteForm" method="post" style="display:none;">
        <input type="hidden" name="delete_user" value="1">
        <input type="hidden" name="delete_id" id="deleteId">
    </form>

    <script src="components/navbar.js"></script>
    <script src="components/sidebar.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        feather.replace();
    </script>

    <script>
        // Notification
        <?php if ($swalMessage): ?>
            Swal.fire({
                icon: '<?= $swalType ?>',
                title: '<?= $swalMessage ?>',
                timer: 1800,
                showConfirmButton: false
            });
        <?php endif; ?>

        // Search Filter
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('keyup', () => {
            let f = searchInput.value.toLowerCase().trim();
            document.querySelectorAll('#userTable tbody tr').forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(f) ? '' : 'none';
            });
        });

        function clearSearch() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('keyup'));
        }

        // SweetAlert Role Change
        function changeRole(id) {
            Swal.fire({
                title: 'Change User Role',
                input: 'select',
                inputOptions: {
                    'Admin': 'Admin',
                    'Student': 'Student',
                    'Staff': 'Staff',
                    'Faculty': 'Faculty'
                },
                inputPlaceholder: 'Select Role',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
            }).then((r) => {
                if (r.value) {
                    document.getElementById('roleUserId').value = id;
                    document.getElementById('roleInput').value = r.value;
                    document.getElementById('roleForm').submit();
                }
            });
        }

        // SweetAlert Delete
        function deleteUser(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This user will be permanently removed.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Delete'
            }).then((r) => {
                if (r.isConfirmed) {
                    document.getElementById('deleteId').value = id;
                    document.getElementById('deleteForm').submit();
                }
            });
        }
    </script>

</body>

</html>
