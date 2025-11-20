<?php
// Fetch users from the database
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch users with roles
$stmt = $pdo->query("SELECT id, first_name, last_name, phone, role FROM users ORDER BY role, first_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group users by role
$groupedUsers = [];
foreach ($users as $user) {
    $role = $user['role'] ?? 'Others';
    $groupedUsers[$role][] = $user;
}
ksort($groupedUsers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Announcement</title>
<style>
:root {
    --primary-red: #c8102e;
    --secondary-white: #ffffff;
    --light-gray: #f8f8f8;
    --border-radius: 12px;
    --shadow: 0 6px 25px rgba(0,0,0,0.1);
}
body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background-color: var(--light-gray);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 30px;
}
.container {
    background-color: var(--secondary-white);
    padding: 35px 40px;
    border-radius: var(--border-radius);
    width: 100%;
    max-width: 800px;
    box-shadow: var(--shadow);
}
h2 { text-align:center; color: var(--primary-red); margin-bottom:30px; font-size:28px; }
label { font-weight:600; color:#333; display:block; margin-bottom:8px; }
textarea {
    width:100%; padding:12px; font-size:15px; border-radius:var(--border-radius);
    border:1px solid #ccc; margin-bottom:20px; resize: vertical;
}
textarea:focus { outline:none; border-color: var(--primary-red); box-shadow:0 0 8px rgba(200,16,46,0.2); }
.select-all-container { margin-bottom:15px; }
.select-all-container label { font-weight:600; cursor:pointer; user-select:none; }

/* Role Cards */
.role-card { border:1px solid #ccc; border-radius:var(--border-radius); margin-bottom:15px; overflow:hidden; background:#fafafa; transition: all 0.3s ease; }
.role-header { background:#f5f5f5; padding:12px 15px; cursor:pointer; font-weight:600; display:flex; justify-content:space-between; align-items:center; transition: background 0.3s; }
.role-header:hover { background:#eaeaea; }
.role-users { max-height:0; overflow:hidden; transition:max-height 0.4s ease; padding:0 15px; }
.role-users.open { max-height:300px; padding:10px 15px; overflow-y:auto; }
.role-users label { display:block; margin-bottom:5px; cursor:pointer; }

/* Button */
button.submit-btn { width:100%; background-color: var(--primary-red); color: var(--secondary-white); border:none; padding:14px; border-radius:var(--border-radius); font-size:16px; cursor:pointer; transition: background-color 0.3s, transform 0.2s; }
button.submit-btn:hover { background-color:#a10c24; transform:translateY(-2px); }
footer { text-align:center; margin-top:25px; color:#777; font-size:13px; }
</style>
</head>
<body>

<div class="container">
    <h2>Send New Announcement</h2>
    <form action="send_announcement.php" method="POST">
        <label>Announcement Message:</label>
        <textarea name="message" rows="4" required placeholder="Type your alert message here..."></textarea>

        <div class="select-all-container">
            <label><input type="checkbox" id="selectAllUsers"> Select All Users</label>
        </div>

        <label>Select Recipients by Role:</label>

        <?php foreach ($groupedUsers as $role => $group): ?>
            <div class="role-card">
                <div class="role-header"><?= htmlspecialchars($role) ?><span class="toggle-icon">▼</span></div>
                <div class="role-users">
                    <?php foreach ($group as $user):
                        $fullName = htmlspecialchars($user['first_name'].' '.$user['last_name']);
                        $phone = htmlspecialchars($user['phone']);
                    ?>
                        <label>
                            <input type="checkbox" name="recipients[<?= htmlspecialchars($role) ?>][]" value="<?= $phone ?>" checked>
                            <?= $fullName ?> (<?= $phone ?>)
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="submit-btn">🚀 Send Announcement</button>
    </form>
    <footer>Broadcast System &middot; Red & White Theme</footer>
</div>

<script>
// Toggle role user list
document.querySelectorAll('.role-header').forEach(header => {
    header.addEventListener('click', () => {
        const users = header.nextElementSibling;
        users.classList.toggle('open');
        header.querySelector('.toggle-icon').textContent = users.classList.contains('open') ? '▲' : '▼';
    });
});

// Select All Users functionality
document.getElementById('selectAllUsers').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.role-users input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

</body>
</html>
