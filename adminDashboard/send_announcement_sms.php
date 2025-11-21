<?php
// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle AJAX deletion
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM alert_acknowledgement WHERE id = ?");
    echo json_encode(['success' => $stmt->execute([$id])]);
    exit;
}

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Search/filter
$search = trim($_GET['search'] ?? '');
$searchSql = '';
$params = [];
if ($search !== '') {
    $searchSql = "WHERE phone_number LIKE :search OR alert_message LIKE :search";
    $params['search'] = "%$search%";
}

// Fetch total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM alert_acknowledgement $searchSql");
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Fetch paginated results
$stmt = $pdo->prepare("SELECT * FROM alert_acknowledgement $searchSql ORDER BY id DESC LIMIT :offset, :perpage");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perpage', $perPage, PDO::PARAM_INT);
if ($search !== '') $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcement History</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root {
    --red: #c8102e;
    --white: #fff;
    --gray: #f8f8f8;
    --light-gray: #f4f4f4;
    --radius: 8px;
    --shadow: 0 4px 12px rgba(0,0,0,0.08);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background-color: var(--gray);
    display: flex;
    justify-content: center;
    min-height: 100vh;
    padding: 10px;
}
.container {
    background-color: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    width: 100%;
    padding: 15px;
}
h2 {
    text-align: center;
    color: var(--red);
    margin-bottom: 12px;
    font-weight: 600;
}
.search-box {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.search-box input[type="text"] {
    padding: 8px 12px;
    border-radius: var(--radius);
    border: 1px solid #ccc;
    width: 200px;
    font-size: 14px;
}
.search-box button {
    background-color: var(--red);
    color: var(--white);
    border: none;
    padding: 8px 14px;
    margin-left: 5px;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 14px;
}
.search-box button:hover { background-color: #a10c24; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
    font-size: 14px;
}
th, td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--light-gray);
    text-align: left;
}
th {
    background-color: var(--red);
    color: var(--white);
    font-weight: 500;
    position: sticky;
    top: 0;
}
tr:nth-child(even) { background-color: var(--light-gray); }
.success { color: green; font-weight: bold; }
.failed { color: var(--red); font-weight: bold; }
.pending { color: #555; }

.action-btn {
    background-color: var(--red);
    color: var(--white);
    border: none;
    padding: 6px 10px;
    border-radius: var(--radius);
    font-size: 12px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: 0.2s;
}
.action-btn:hover { background-color: #a10c24; }

.pagination {
    margin-top: 12px;
    text-align: center;
}
.pagination a {
    margin: 3px 4px;
    padding: 5px 10px;
    border-radius: var(--radius);
    text-decoration: none;
    background-color: var(--red);
    color: var(--white);
    font-size: 13px;
}
.pagination a.active { background-color: #a10c24; font-weight: 500; }
.pagination a:hover { background-color: #a10c24; }

.back-btn {
    display: inline-block;
    margin-top: 10px;
    background-color: var(--red);
    color: var(--white);
    text-decoration: none;
    padding: 8px 15px;
    border-radius: var(--radius);
    font-weight: 500;
    transition: 0.2s;
}
.back-btn:hover { background-color: #a10c24; }

footer {
    text-align: center;
    margin-top: 15px;
    color: #777;
    font-size: 13px;
}

@media screen and (max-width: 650px) {
    .search-box { justify-content: center; }
    .search-box input[type="text"] { width: 100%; margin-bottom: 5px; }
    table th, table td { font-size: 12px; padding: 8px; }
}
</style>
</head>
<body>
<div class="container">

<div style="text-align:left;">
    <a href="announcement_form_sms.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Form</a>
</div>

<h2>Announcement History</h2>

<div class="search-box">
    <form method="get" action="">
        <input type="text" name="search" placeholder="Search phone or message" value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fa fa-search"></i> Search</button>
    </form>
</div>

<?php if (!empty($results)): ?>
<table id="announcement-table">
<tr>
    <th>ID</th>
    <th>Phone</th>
    <th>Message</th>
    <th>Status</th>
    <th>Ack URL</th>
    <th>Expires At</th>
    <th>Actions</th>
</tr>
<?php foreach ($results as $r): ?>
<tr id="row-<?= $r['id'] ?>">
    <td><?= htmlspecialchars($r['id']) ?></td>
    <td><?= htmlspecialchars($r['phone_number']) ?></td>
    <td><?= htmlspecialchars($r['alert_message']) ?></td>
    <td class="<?= strtolower($r['status'] ?? 'pending') ?>"><?= htmlspecialchars($r['status'] ?? 'Pending') ?></td>
    <td>
        <?php if (!empty($r['token_hash'])): ?>
            <a href="acknowledge.php?token=<?= htmlspecialchars($r['token_hash']) ?>" target="_blank"><i class="fa fa-link"></i></a>
        <?php else: ?>
            N/A
        <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($r['token_expires_at']) ?></td>
    <td>
        <button class="action-btn delete-btn" data-id="<?= $r['id'] ?>"><i class="fa fa-trash"></i> Delete</button>
    </td>
</tr>
<?php endforeach; ?>
</table>

<div class="pagination">
    <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php else: ?>
<p style="text-align:center; padding:10px;">No announcements found.</p>
<?php endif; ?>

</div>

<script>
// SweetAlert Delete Confirmation with AJAX
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        const id = this.getAttribute('data-id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This entry will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c8102e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        Swal.fire('Deleted!', 'Entry has been deleted.', 'success');
                        const row = document.getElementById('row-' + id);
                        if(row) row.remove();
                    } else {
                        Swal.fire('Error!', 'Could not delete entry.', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
