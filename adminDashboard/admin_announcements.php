<?php
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pagination setup
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $perPage;

// Count total announcements
$totalStmt = $pdo->query("SELECT COUNT(*) FROM web_announcements");
$totalAnnouncements = $totalStmt->fetchColumn();
$totalPages = ceil($totalAnnouncements / $perPage);

// Fetch announcements with acknowledgement info (paginated)
$stmt = $pdo->prepare("
    SELECT a.id, a.message, a.priority, a.created_at, 
           u.first_name, u.last_name, ack.acknowledged_at
    FROM web_announcements a
    LEFT JOIN announcements_acknowledged ack ON a.id = ack.announcement_id
    LEFT JOIN users u ON ack.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT :start, :perPage
");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Announcements</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-white min-h-screen p-6">

<div class="w-full max-w-full mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <a href="admin_dashboard.php" class="flex items-center text-red-600 hover:text-red-800 font-semibold gap-1">
            <i data-feather="arrow-left" class="w-5 h-5"></i>
            Back
        </a>
        <h1 class="text-3xl font-bold text-red-600 flex items-center gap-2">
            <i data-feather="bell" class="w-6 h-6"></i>
            Announcements Acknowledgements
        </h1>
        <div></div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto w-full shadow-md rounded-lg border border-red-200">
        <table class="min-w-full divide-y divide-red-200">
            <thead class="bg-red-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Message</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Priority</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Acknowledged By</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Acknowledged At</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Created At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-red-200">
                <?php if($announcements): ?>
                    <?php foreach($announcements as $ann): ?>
                    <tr class="hover:bg-red-50 transition-colors">
                        <td class="px-4 py-3"><?= htmlspecialchars($ann['message']) ?></td>
                        <td class="px-4 py-3">
                            <?php if($ann['priority'] === 'High'): ?>
                                <span class="text-white bg-red-600 px-2 py-1 rounded-full text-xs"><?= htmlspecialchars($ann['priority']) ?></span>
                            <?php elseif($ann['priority'] === 'Medium'): ?>
                                <span class="text-white bg-yellow-500 px-2 py-1 rounded-full text-xs"><?= htmlspecialchars($ann['priority']) ?></span>
                            <?php else: ?>
                                <span class="text-white bg-green-500 px-2 py-1 rounded-full text-xs"><?= htmlspecialchars($ann['priority']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= $ann['first_name'] ? htmlspecialchars($ann['first_name'].' '.$ann['last_name']) : '-' ?></td>
                        <td class="px-4 py-3"><?= $ann['acknowledged_at'] ?? '-' ?></td>
                        <td class="px-4 py-3"><?= $ann['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-red-600 font-semibold">No announcements found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 flex justify-center space-x-2">
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="px-3 py-1 rounded-md border border-red-600 <?= $i === $page ? 'bg-red-600 text-white' : 'bg-white text-red-600' ?> hover:bg-red-500 hover:text-white transition">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<script>
    feather.replace()
</script>
</body>
</html>
