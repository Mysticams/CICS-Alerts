<?php
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pagination settings
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Fetch total announcements
$totalStmt = $pdo->query("SELECT COUNT(*) FROM web_announcements");
$totalAnnouncements = $totalStmt->fetchColumn();
$totalPages = ceil($totalAnnouncements / $perPage);

// Fetch announcements with recipients
$stmt = $pdo->prepare("
    SELECT a.id AS announcement_id, a.message, a.priority,
           u.first_name, u.last_name, u.role, ack.web_status
    FROM web_announcements a
    LEFT JOIN announcement_acknowledgements ack ON a.id = ack.announcement_id
    LEFT JOIN users u ON ack.user_id = u.id
    ORDER BY a.id DESC
    LIMIT :offset, :perPage
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group results by announcement_id
$groupedAnnouncements = [];
foreach ($results as $row) {
    $id = $row['announcement_id'];
    if (!isset($groupedAnnouncements[$id])) {
        $groupedAnnouncements[$id] = [
            'message' => $row['message'],
            'priority' => $row['priority'],
            'recipients' => []
        ];
    }
    if ($row['first_name']) {
        $groupedAnnouncements[$id]['recipients'][] = [
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'role' => $row['role'],
            'web_status' => $row['web_status'] ?? 'N/A'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Announcement Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-100 font-sans p-6">
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-2xl shadow-lg">
        <h2 class="text-2xl font-bold text-red-700 mb-4 text-center">Announcement Results</h2>

        <a href="announcement_form.php" class="inline-flex items-center mb-4 px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-700">
            <i data-feather="arrow-left" class="mr-2"></i> Back to Form
        </a>

        <table class="w-full table-auto border-collapse text-center">
            <thead>
                <tr class="bg-red-700 text-white">
                    <th class="p-2 border">Message</th>
                    <th class="p-2 border">Priority</th>
                    <th class="p-2 border">Recipients & Status</th>
                    <th class="p-2 border">Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedAnnouncements as $id => $announcement):
                    $priorityColor = match ($announcement['priority']) {
                        'High' => 'bg-red-600 text-white',
                        'Medium' => 'bg-yellow-400 text-black',
                        'Low' => 'bg-green-600 text-white',
                        default => 'bg-gray-300 text-black'
                    };
                ?>
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="p-2 border text-left"><?= htmlspecialchars($announcement['message']) ?></td>
                        <td class="p-2 border">
                            <span class="px-3 py-1 rounded-full <?= $priorityColor ?> font-semibold"><?= htmlspecialchars($announcement['priority']) ?></span>
                        </td>
                        <td class="p-2 border text-left">
                            <?php if ($announcement['recipients']): ?>
                                <ul class="space-y-1">
                                    <?php foreach ($announcement['recipients'] as $r):
                                        $status = $r['web_status'] ?? 'N/A';
                                        // Green if sent or acknowledged, red otherwise
                                        $statusColor = in_array(strtolower($status), ['sent', 'acknowledged']) ? 'bg-green-600 text-white' : 'bg-red-700 text-white';
                                    ?>
                                        <li class="flex justify-between items-center px-2 py-1 border rounded">
                                            <span><?= htmlspecialchars($r['name'] . ' (' . $r['role'] . ')') ?></span>
                                            <span class="px-2 py-0.5 rounded-full <?= $statusColor ?> text-xs"><?= htmlspecialchars(ucfirst($status)) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded-full bg-gray-300 text-black">No recipients</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2 border">
                            <button class="delete-btn px-3 py-1 bg-red-700 text-white rounded hover:bg-red-800 inline-flex items-center" data-id="<?= $id ?>">
                                <i data-feather="trash-2" class="mr-1"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex justify-center mt-6 gap-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i == $page ? 'bg-red-700 text-white' : 'bg-gray-200 text-black hover:bg-gray-300' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <script>
        feather.replace(); // Render icons

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const row = this.closest('tr');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the announcement permanently!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#c8102e',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('delete_announcement.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'id=' + encodeURIComponent(id)
                            })
                            .then(res => res.text())
                            .then(data => {
                                if (data.trim() === 'success') {
                                    row.remove();
                                    Swal.fire('Deleted!', 'The announcement has been deleted.', 'success');
                                } else {
                                    Swal.fire('Error!', 'Failed to delete announcement.', 'error');
                                }
                            }).catch(() => Swal.fire('Error!', 'Failed to delete announcement.', 'error'));
                    }
                });
            });
        });
    </script>
</body>

</html>