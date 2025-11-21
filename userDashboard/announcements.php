<?php
session_start();

// Ensure user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}
$userId = $_SESSION['user_id'];

$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pagination setup
$perPage = 6;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$start = ($page-1)*$perPage;

// Total announcements
$totalStmt = $pdo->query("SELECT COUNT(*) FROM web_announcements");
$totalAnnouncements = $totalStmt->fetchColumn();
$totalPages = ceil($totalAnnouncements / $perPage);

// Fetch announcements
$stmt = $pdo->prepare("SELECT * FROM web_announcements ORDER BY created_at DESC LIMIT :start,:perPage");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch acknowledged announcements
$ackStmt = $pdo->prepare("SELECT announcement_id FROM announcements_acknowledged WHERE user_id=:user_id");
$ackStmt->execute([':user_id'=>$userId]);
$acknowledged = $ackStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<style>
body{font-family:'Roboto',sans-serif;background:#f9fafb;margin:0;padding:0;}
main{flex:1;padding:2rem 3rem;margin-left:16rem;margin-top:4rem;transition:margin 0.3s ease;}
@media(max-width:1024px){main{margin-left:0;padding:1.5rem 1rem;}}

h1.section-title{font-size:2rem;font-weight:800;color:#E53E3E;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.75rem;}
.controls{display:flex;flex-wrap:wrap;justify-content:flex-start;gap:0.5rem;margin-bottom:1.5rem;}
.controls input,.controls select{padding:0.5rem 0.75rem;border-radius:0.5rem;border:1px solid #E53E3E;font-size:0.9rem;font-weight:500;}

.notifications-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem;}
.notification{position:relative;border-left-width:6px;border-radius:1rem;padding:1rem 1.25rem;background:#fff;box-shadow:0 4px 15px rgba(0,0,0,0.08);display:flex;flex-direction:column;gap:0.25rem;transition:transform 0.25s,box-shadow 0.25s;}
.notification:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(0,0,0,0.15);}
.notification-time{font-size:0.75rem;color:#888;}
.notification-message{font-size:0.95rem;line-height:1.5;color:#333;}
.priority-badge{position:absolute;top:0.5rem;right:0.5rem;padding:3px 7px;border-radius:9999px;font-size:0.7rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:0.25rem;}
.high{background:#E53E3E;}
.medium{background:#c2bf16ff;}
.low{background:#28a745;}
.ack-btn{border:none;border-radius:0.5rem;padding:0.4rem 0.8rem;font-size:0.85rem;font-weight:600;margin-top:0.5rem;align-self:flex-start;transition:all 0.2s;}
.ack-btn.red{background:#E53E3E;color:#fff;cursor:pointer;}
.ack-btn.red:hover{background:#F87171;}
.ack-btn.green{background:#28a745;color:#fff;cursor:default;}
.pagination{display:flex;justify-content:center;margin-top:2rem;gap:0.5rem;}
.page-link{padding:0.5rem 0.75rem;border-radius:0.5rem;border:1px solid #E53E3E;color:#E53E3E;font-weight:600;transition:all 0.2s;}
.page-link:hover{background:#E53E3E;color:#fff;}
.page-link.active{background:#E53E3E;color:#fff;}
</style>
</head>
<body>
<custom-navbar class="fixed top-0 left-0 w-full z-50"></custom-navbar>
<custom-sidebar class="fixed top-0 left-0 h-full z-40"></custom-sidebar>

<main>
<h1 class="section-title"><i data-feather="message-circle" class="w-6 h-6 text-red-600"></i>Announcements</h1>

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

<div class="notifications-grid" id="notificationsList">
<?php foreach($announcements as $ann):
    $priorityClass = match($ann['priority'] ?? 'Medium') {'High'=>'high','Medium'=>'medium','Low'=>'low',default=>'medium'};
    $priorityIcon = match($ann['priority'] ?? 'Medium') {'High'=>'<span>🔴</span>','Medium'=>'<span>🟡</span>','Low'=>'<span>🟢</span>',default=>'<span>🟡</span>'};
    $alreadyAck = in_array($ann['id'], $acknowledged);
?>
<div class="notification" data-priority="<?= htmlspecialchars($ann['priority'] ?? 'Medium') ?>" data-time="<?= strtotime($ann['created_at']) ?>">
    <div class="notification-time"><?= date('M d, Y H:i', strtotime($ann['created_at'])) ?></div>
    <div class="notification-message"><?= htmlspecialchars($ann['message']) ?></div>
    <div class="priority-badge <?= $priorityClass ?>"><?= $priorityIcon ?> <?= htmlspecialchars($ann['priority'] ?? 'Medium') ?></div>
    <form method="POST" action="acknowledge_announcement.php">
        <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
        <button type="submit" class="ack-btn <?= $alreadyAck ? 'green' : 'red' ?>" <?= $alreadyAck ? 'disabled' : '' ?>>
            <?= $alreadyAck ? 'Acknowledged' : 'Acknowledge' ?>
        </button>
    </form>
</div>
<?php endforeach; ?>
</div>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="?page=<?= $i ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
<?php endfor; ?>
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
    cards.forEach(card=>{
        const cardPriority = card.dataset.priority.toLowerCase();
        const message = card.querySelector('.notification-message').textContent.toLowerCase();
        card.style.display = ((priority==='all'||priority===cardPriority) && message.includes(keyword))?'block':'none';
    });
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
