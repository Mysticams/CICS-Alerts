<?php
require_once '../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$pdo = pdo();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found!";
    exit;
}

$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';
$birthday = $user['birthday'] ?? '';
$gender = $user['gender'] ?? '';
$profile_pic = $user['profile_pic'] ?? '';
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | CICS AlertSOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* ---------------- Global ---------------- */
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: #fff;
            color: #374151;
        }

        /* ---------------- Main Layout ---------------- */
        main {
            margin-top: 60px;
            margin-left: 250px;
            padding: 1rem;
            transition: margin 0.3s ease;
            min-height: calc(100vh - 60px);
        }

        /* ---------------- Card ---------------- */
        .card {
            background: #fff;
            border-radius: 1rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.15);
        }

        /* ---------------- Profile Header ---------------- */
        .profile-header {
            padding: 3rem 2rem 2rem 2rem;
            background: #ffffffff;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            text-align: center;
        }

        .profile-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-top: 1rem;
        }

        /* ---------------- Profile Picture ---------------- */
        .profile-pic {
            border-radius: 9999px;
            background: #ef4444;
            border: 4px solid #fff;
            width: 140px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            font-weight: bold;
            color: #ffffffff;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-pic:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3);
        }

        /* Camera Icon */
        .profile-pic-label {
            position: absolute;
            bottom: 0.5rem;
            right: calc(50% - 60px);
            background: #fff;
            color: #ef4444;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            border: 2px solid #ef4444;
            transition: all 0.2s ease-in-out;
        }

        .profile-pic-label:hover {
            background: #ef4444;
            color: #fff;
        }

        /* ---------------- Profile Form ---------------- */
        .profile-form {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .profile-form div {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        input,
        select {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            font-size: 0.875rem;
            transition: all 0.2s ease-in-out;
        }

        input:focus,
        select:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
            outline: none;
        }

        input[readonly],
        select[disabled] {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        /* Buttons */
        .form-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .form-buttons button {
            flex: 1;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            transition: all 0.2s ease-in-out;
        }

        #editBtn {
            background: #ef4444;
            color: #fff;
        }

        #editBtn:hover {
            background: #dc2626;
        }

        #saveBtn {
            background: #10b981;
            color: #fff;
        }

        #saveBtn:hover {
            background: #059669;
        }

        /* Toast Messages */
        #toastContainer>div {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        /* ---------------- Responsive ---------------- */
        @media (max-width: 1024px) {
            main {
                margin-left: 0;
                padding: 1rem;
            }
        }

        @media (max-width: 640px) {
            .profile-header {
                padding: 2rem 1rem 1.5rem 1rem;
            }

            .profile-pic {
                width: 120px;
                height: 120px;
                font-size: 2rem;
            }

            .profile-pic-label {
                bottom: 0.5rem;
                right: calc(50% - 60px);
                width: 28px;
                height: 28px;
            }
        }
    </style>
</head>

<custom-navbar></custom-navbar>
<custom-sidebar></custom-sidebar>

<body class="theme-light">

    <main>
        <div class="card">
            <!-- Header / Profile Picture -->
            <h1 class="text-3xl sm:text-4xl font-bold text-red-600 mb-6 flex items-center gap-3 ml-8">
                <i data-feather="user" class="w-6 h-6 text-red-600"></i>
                Profile Settings
            </h1>

            <div class="profile-header">
                <div id="profilePicPreview" class="profile-pic">
                    <?php if ($profile_pic): ?>
                        <img src="../uploads/profile_pics/<?= htmlspecialchars($profile_pic) ?>" class="w-full h-full object-cover rounded-full">
                    <?php else: ?>
                        <?= $initials ?: 'NN' ?>
                    <?php endif; ?>
                </div>
                <label for="profilePicInput" class="profile-pic-label"><i data-feather="camera"></i></label>
                <input type="file" id="profilePicInput" accept="image/*" class="hidden">
            </div>

            <!-- Form -->
            <div class="profile-form">
                <form id="profileForm">
                    <div>
                        <label>Full Name</label>
                        <input type="text" id="userName" value="<?= htmlspecialchars(trim("$first_name $last_name")) ?>" readonly>
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" id="userEmail" value="<?= htmlspecialchars($email) ?>" readonly>
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="tel" id="userPhone" value="<?= htmlspecialchars($phone) ?>" readonly>
                    </div>
                    <div>
                        <label>Address</label>
                        <input type="text" id="userAddress" value="<?= htmlspecialchars($address) ?>" readonly>
                    </div>
                    <div>
                        <label>Birthday</label>
                        <input type="date" id="userBirthday" value="<?= htmlspecialchars($birthday) ?>" readonly>
                    </div>
                    <div>
                        <label>Gender</label>
                        <select id="userGender" disabled>
                            <option value="" <?= empty($gender) ? 'selected' : '' ?>>Select Gender</option>
                            <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-buttons">
                        <button type="button" id="editBtn">Edit Profile</button>
                        <button type="submit" id="saveBtn" class="hidden">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <div id="toastContainer" class="fixed bottom-5 right-5 flex flex-col gap-2"></div>

    <script src="components/navbar.js"></script>
    <script src="components/sidebar.js"></script>
    <script>
        feather.replace();

        function showToast(msg, type = 'success') {
            const t = document.createElement('div');
            t.className = `px-4 py-2 rounded text-white ${type==='success'?'bg-green-600':'bg-red-600'}`;
            t.innerText = msg;
            document.getElementById('toastContainer').appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

        document.getElementById('editBtn').addEventListener('click', () => {
            document.getElementById('profileForm').querySelectorAll('input,select').forEach(i => {
                i.readOnly = false;
                i.disabled = false
            });
            document.getElementById('saveBtn').classList.remove('hidden');
        });

        document.getElementById('profileForm').addEventListener('submit', async e => {
            e.preventDefault();
            const [first_name, ...lastParts] = document.getElementById('userName').value.trim().split(' ');
            const last_name = lastParts.join(' ');
            const data = {
                first_name,
                last_name,
                phone: document.getElementById('userPhone').value,
                address: document.getElementById('userAddress').value,
                birthday: document.getElementById('userBirthday').value,
                gender: document.getElementById('userGender').value
            };
            try {
                const res = await fetch('profile_save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) {
                    document.getElementById('profileForm').querySelectorAll('input,select').forEach(i => {
                        i.readOnly = true;
                        i.disabled = true
                    });
                    document.getElementById('saveBtn').classList.add('hidden');
                }
            } catch (err) {
                showToast('Error updating profile', 'error');
            }
        });

        const profileInput = document.getElementById('profilePicInput');
        const profilePreview = document.getElementById('profilePicPreview');
        profileInput.addEventListener('change', async () => {
            const file = profileInput.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                profilePreview.innerHTML = '';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full h-full object-cover rounded-full';
                profilePreview.appendChild(img);
            };
            reader.readAsDataURL(file);
            const formData = new FormData();
            formData.append('profile_pic', file);
            try {
                const res = await fetch('profile_save.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                showToast(result.message, result.success ? 'success' : 'error');
            } catch (err) {
                showToast('Error uploading profile picture', 'error');
            }
        });
    </script>
</body>

</html>