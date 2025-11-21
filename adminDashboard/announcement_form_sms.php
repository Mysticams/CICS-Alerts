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
<script src="https://unpkg.com/feather-icons"></script>
<style>
    :root {
        --primary-red: #dc2626;
        --secondary-white: #ffffff;
        --light-gray: #f8f8f8;
        --dark-gray: #111827;
        --border-radius: 12px;
    }

    body {
        font-family: "Segoe UI", Tahoma, sans-serif;
        background-color: var(--light-gray);
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        padding-top: 30px;
    }

    .container {
        background-color: var(--secondary-white);
        padding: 35px 40px;
        border-radius: var(--border-radius);
        width: 100%;
        max-width: 800px;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    h2 {
        text-align: center;
        color: var(--primary-red);
        margin-bottom: 30px;
        font-size: 28px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }

    label {
        font-weight: 600;
        display: block;
        margin-bottom: 8px;
    }

    textarea {
        width: 100%;
        padding: 12px;
        font-size: 15px;
        border-radius: var(--border-radius);
        border: 1px solid #ccc;
        margin-bottom: 20px;
        resize: vertical;
        color: #111827;
    }

    textarea:focus {
        outline: none;
        border-color: var(--primary-red);
        box-shadow: 0 0 8px rgba(220, 38, 38, 0.2);
    }

    .select-all-container {
        margin-bottom: 15px;
    }

    .select-all-container label {
        font-weight: 600;
        cursor: pointer;
    }

    /* Role cards */
    .role-card {
        border-radius: var(--border-radius);
        margin-bottom: 15px;
        overflow: hidden;
        background: var(--dark-gray);
        transition: all 0.3s ease;
    }

    .role-header {
        padding: 12px 15px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.3s;
        color: var(--secondary-white);
    }

    /* Admin and Student red headers, others gray */
    .role-header.admin, 
    .role-header.student {
        background-color: var(--primary-red);
    }

    .role-header.admin:hover, 
    .role-header.student:hover {
        background-color: #b91c1c;
    }

    .role-header.other {
        background-color: #374151; /* gray-700 */
    }

    .role-header.other:hover {
        background-color: #4b5563; /* gray-600 */
    }

    .role-users {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease;
        padding: 0 15px;
    }

    .role-users.open {
        max-height: 300px;
        padding: 10px 15px;
        overflow-y: auto;
    }

    .role-users label {
        display: block;
        margin-bottom: 5px;
        cursor: pointer;
        color: #e5e7eb; /* text-gray-200 */
    }

    /* Button */
    button.submit-btn {
        width: 100%;
        background-color: #22c55e;
        color: var(--secondary-white);
        border: none;
        padding: 14px;
        border-radius: var(--border-radius);
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.2s;
    }

    button.submit-btn:hover {
        background-color: #16a34a;
        transform: translateY(-2px);
    }

    .back-btn {
        position: absolute;
        top: 15px;
        left: 15px;
        color: var(--primary-red);
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .back-btn:hover {
        color: #b91c1c;
    }
</style>
</head>

<body>

<div class="container">
    <a href="announcement.php" class="back-btn"><i data-feather="arrow-left"></i> Back</a>

    <h2>
        Send New Announcement
        <i data-feather="smartphone"></i>
    </h2>

    <form action="send_announcement_sms.php" method="POST">
        <label>Announcement Message:</label>
        <textarea name="message" rows="4" required placeholder="Type your alert message here..."></textarea>

        <div class="select-all-container">
            <label><input type="checkbox" id="selectAllUsers"> Select All Users</label>
        </div>

        <label>Select Recipients by Role:</label>

        <?php foreach ($groupedUsers as $role => $group): 
            $roleClass = in_array(strtolower($role), ['admin', 'student']) ? strtolower($role) : 'other';
        ?>
        <div class="role-card">
            <div class="role-header <?= $roleClass ?>">
                <?= htmlspecialchars($role) ?><span class="toggle-icon">▼</span>
            </div>
            <div class="role-users">
                <?php foreach ($group as $user): ?>
                <label>
                    <input type="checkbox" name="recipients[<?= htmlspecialchars($role) ?>][]" value="<?= htmlspecialchars($user['phone']) ?>" checked>
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="submit-btn">Send Announcement</button>
    </form>
</div>

<script>
    feather.replace();

    // Toggle role users
    document.querySelectorAll('.role-header').forEach(header => {
        header.addEventListener('click', () => {
            const users = header.nextElementSibling;
            users.classList.toggle('open');
            header.querySelector('.toggle-icon').textContent = users.classList.contains('open') ? '▲' : '▼';
        });
    });

    // Select all users
    document.getElementById('selectAllUsers').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.role-users input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>

</body>
</html>
