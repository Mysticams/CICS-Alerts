<?php
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch announcements sorted newest first
$stmt = $pdo->query("SELECT * FROM web_announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: '#E53E3E',
                secondary: '#FEE2E2',
                accent: '#F87171'
            }
        }
    }
}
</script>

<style>
body {
    font-family: 'Roboto', sans-serif;
    background: #f9fafb;
    margin: 0;
    padding: 0;
    display: flex;
    min-height: 100vh;
}

main {
    flex: 1;
    margin-left: 16rem;
    margin-top: 4rem;
    padding: 1.5rem 2rem;
    transition: margin-left 0.3s ease;
}

@media(max-width:1024px){ main {margin-left:0;padding:1rem;} }

.container { max-width: 3xl; margin: 0 auto; }

h1.section-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #E53E3E;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.controls {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.controls input,
.controls select {
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid #E53E3E;
    min-width: 160px;
    max-width: 240px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Scrollable notifications panel */
#notificationsList {
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 0.25rem;
}

.notification {
    position: relative;
    border-left-width: 6px;
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    margin-bottom: 0.85rem;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.25s, box-shadow 0.25s;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.notification:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.notification-time { font-size: 0.75rem; color: #888; }

.notification-message { font-size: 0.95rem; line-height: 1.5; color: #333; }

.priority-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 3px 7px;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Priority Colors */
.high { background: #E53E3E; }
.medium { background: #c2bf16ff; }
.low { background: #28a745; }

.ack-btn {
    background: #E53E3E;
    color: #fff;
    border: none;
    border-radius: 0.5rem;
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.5rem;
    align-self: flex-start;
    transition: all 0.2s;
}

.ack-btn:hover { background: #F87171; }

@media(max-width:768px){
    .controls { flex-direction: column; align-items: center; gap: 0.5rem; }
    .notification { padding: 0.85rem 1rem; }
    .priority-badge { top: 0.25rem; right: 0.25rem; font-size: 0.65rem; padding: 2px 6px; }
    .ack-btn { padding: 0.35rem 0.7rem; font-size: 0.75rem; }
}
</style>
</head>

<body>
<custom-navbar class="relative z-50"></custom-navbar>
<custom-sidebar class="relative z-40"></custom-sidebar>

<main>
<div class="container">
    <h1 class="section-title">
        <i data-feather="message-circle" class="w-6 h-6 text-red-600"></i>
        Announcements
    </h1>

    <div class="controls">
        <input type="text" id="searchInput" placeholder="Search notifications...">
        <select id="filterSelect">
            <option value="all">All Priorities</option>
            <option value="High">High</option>
            <option value="Medium">Medium</option>
            <option value="Low">Low</option>
        </select>
        <select id="sortSelect">
            <option value="newest">Sort by Newest</option>
            <option value="oldest">Sort by Oldest</option>
        </select>
    </div>

    <div id="notificationsList">
        <?php foreach ($announcements as $ann):
            $priorityClass = match ($ann['priority'] ?? 'Medium') {
                'High'=>'high',
                'Medium'=>'medium',
                'Low'=>'low',
                default=>'medium',
            };
            $priorityIcon = match ($ann['priority'] ?? 'Medium') {
                'High'=>'<span>🔴</span>',
                'Medium'=>'<span>🟡</span>',
                'Low'=>'<span>🟢</span>',
                default=>'<span>🟡</span>',
            };
        ?>
        <div class="notification" data-priority="<?= htmlspecialchars($ann['priority'] ?? 'Medium') ?>" data-time="<?= strtotime($ann['created_at']) ?>">
            <div class="notification-time"><?= date('M d, Y H:i', strtotime($ann['created_at'])) ?></div>
            <div class="notification-message"><?= htmlspecialchars($ann['message']) ?></div>
            <div class="priority-badge <?= $priorityClass ?>"><?= $priorityIcon ?> <?= htmlspecialchars($ann['priority'] ?? 'Medium') ?></div>
            <form method="POST" action="acknowledge.php">
                <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                <button type="submit" class="ack-btn">Acknowledge</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</main>

<script src="components/navbar.js"></script>
<script src="components/sidebar.js"></script>
<script>feather.replace();</script>

<script>
const filterSelect = document.getElementById('filterSelect');
const searchInput = document.getElementById('searchInput');
const sortSelect = document.getElementById('sortSelect');
const notificationsList = document.getElementById('notificationsList');

function filterAndSortNotifications(){
    const priority = filterSelect.value.toLowerCase();
    const keyword = searchInput.value.toLowerCase();
    const sort = sortSelect.value;

    let cards = Array.from(notificationsList.querySelectorAll('.notification'));

    // Filter
    cards.forEach(card=>{
        const cardPriority = card.dataset.priority.toLowerCase();
        const message = card.querySelector('.notification-message').textContent.toLowerCase();
        card.style.display = ((priority==='all'||priority===cardPriority) && message.includes(keyword))?'block':'none';
    });

    // Sort
    cards = cards.filter(c=>c.style.display==='block');
    cards.sort((a,b)=>{
        const timeA = parseInt(a.dataset.time);
        const timeB = parseInt(b.dataset.time);
        return sort==='newest'? timeB-timeA : timeA-timeB;
    });

    cards.forEach(card=>notificationsList.appendChild(card));
}

filterSelect.addEventListener('change', filterAndSortNotifications);
searchInput.addEventListener('input', filterAndSortNotifications);
sortSelect.addEventListener('change', filterAndSortNotifications);
</script>

</body>
</html>
