<?php
// acknowledgment_modal.php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Only allow logged-in users
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thank You!</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
    --main-red: #d32f2f;
    --light-red: #ffefef;
    --dark-red: #b71c1c;
}

body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: var(--light-red);
}

/* Modal container */
#thankYouModal {
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.4);
    display:flex;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.container {
    background: #fff;
    padding: 40px 30px;
    border-radius: 20px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    position: relative;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    z-index: 2;
}

.container i {
    font-size: 50px;
    color: var(--main-red);
    margin-bottom: 15px;
    animation: bounce 1s ease infinite;
}

@keyframes bounce {
    0%,100%{ transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.container h1 {
    font-size: 24px;
    color: var(--dark-red);
    margin-bottom: 10px;
}

.container p {
    font-size: 14px;
    color: #555;
    margin-bottom: 20px;
}

.container button {
    background: var(--main-red);
    color: #fff;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.container button:hover {
    background: var(--dark-red);
}

/* Confetti */
.confetti {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    opacity: 0.9;
    animation: fall 3s linear infinite;
}

@keyframes fall {
    0% { transform: translateY(-10px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(400px) rotate(360deg); opacity: 0; }
}
</style>
</head>
<body>

<div id="thankYouModal">
    <div class="container">
        <i class="fa-solid fa-heart-circle-check"></i>
        <h1>Thank You!</h1>
        <p>Your acknowledgment has been received successfully.</p>
        <button id="exitBtn">Exit</button>
    </div>
</div>

<script>
// User ID from PHP session
const USER_ID = <?php echo json_encode($user_id); ?>;

// Generate colorful confetti
const colors = ['#d32f2f','#b71c1c','#ff5252','#ff7961','#ff8a80','#ffb74d','#fdd835','#4fc3f7','#81c784','#ba68c8','#f06292'];
const container = document.querySelector('.container');

for(let i=0;i<80;i++){
    const confetti = document.createElement('div');
    confetti.classList.add('confetti');
    confetti.style.left = Math.random()*100+'%';
    confetti.style.top = Math.random()*-100+'px';
    confetti.style.background = colors[Math.floor(Math.random()*colors.length)];
    confetti.style.width = Math.random()*6 + 4 + 'px';
    confetti.style.height = confetti.style.width;
    confetti.style.animationDuration = (2 + Math.random()*3) + 's';
    confetti.style.animationDelay = Math.random()*2 + 's';
    document.getElementById('thankYouModal').appendChild(confetti);
}

// Handle Exit click
document.getElementById('exitBtn').addEventListener('click', () => {
    fetch('store_acknowledgment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: USER_ID })
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);
        // Redirect to exit page after storing acknowledgment
        window.location.href = '../index.php'; // Change to your desired exit page
    })
    .catch(err => {
        console.error(err);
        window.location.href = '../index.php';
    });
});
</script>

</body>
</html>
