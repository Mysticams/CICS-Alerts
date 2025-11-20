<?php
// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// iProg SMS API
$apiUrl = 'https://sms.iprogtech.com/api/v1/sms_messages';
$apiToken = '9a1eb56a366fc14a3d3086727eec0501272a325f';
$baseAckUrl = 'https://mysite.com/acknowledge.php?token=';

// Retrieve and validate POST data
$message = trim($_POST['message'] ?? '');
$recipients = $_POST['recipients'] ?? [];

if (empty($message)) {
    die("Error: Message is required.");
}

if (empty($recipients)) {
    die("Error: At least one recipient must be selected.");
}

// Helper function to generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

$results = [];

foreach ($recipients as $phone) {
    // Ensure phone is string
    $phone = is_array($phone) ? implode(',', $phone) : (string)$phone;

    $token = generateToken();
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    try {
        // Store in database
        $stmt = $pdo->prepare("
            INSERT INTO alert_acknowledgement 
            (alert_message, phone_number, token_hash, token_expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$message, $phone, $tokenHash, $expiresAt]);

        // Build acknowledgment link
        $ackUrl = $baseAckUrl . urlencode($token);
        $smsMessage = $message . "\nAcknowledge here: " . $ackUrl;

        // Send SMS via iProg API
        $data = [
            'api_token' => $apiToken,
            'message' => $smsMessage,
            'phone_number' => $phone
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $response = curl_exec($ch);
        curl_close($ch);

        $respDecoded = json_decode($response, true);
        $success = isset($respDecoded['status']) && $respDecoded['status'] == 200;

        $results[] = [
            'phone' => $phone,
            'acknowledged' => 0,
            'status' => $success ? 'Sent' : 'Failed',
            'message' => $respDecoded['message'] ?? 'No response',
            'ackUrl' => $ackUrl // store the link for clickable table
        ];

    } catch (Exception $e) {
        $results[] = [
            'phone' => $phone,
            'acknowledged' => 0,
            'status' => 'Failed',
            'message' => $e->getMessage(),
            'ackUrl' => '' // empty link on failure
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcement Results</title>
<style>
:root {
    --red: #c8102e;
    --white: #fff;
    --gray: #f8f8f8;
    --dark: #222;
    --radius: 12px;
}
body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background-color: var(--gray);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
}
.container {
    background-color: var(--white);
    border-radius: var(--radius);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    width: 90%;
    max-width: 700px;
    margin-top: 50px;
    padding: 30px 40px;
}
h2 {
    text-align: center;
    color: var(--red);
    margin-bottom: 10px;
}
.summary {
    text-align: center;
    margin-bottom: 20px;
    color: var(--dark);
}
table {
    width: 100%;
    border-collapse: collapse;
    border-radius: var(--radius);
    overflow: hidden;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
th {
    background-color: var(--red);
    color: var(--white);
}
tr:last-child td {
    border-bottom: none;
}
.success {
    color: green;
    font-weight: bold;
}
.failed {
    color: var(--red);
    font-weight: bold;
}
.pending {
    color: #555;
}
.back-btn {
    display: inline-block;
    margin-top: 25px;
    background-color: var(--red);
    color: var(--white);
    text-decoration: none;
    padding: 10px 20px;
    border-radius: var(--radius);
    font-weight: 600;
    transition: background-color 0.3s;
}
.back-btn:hover {
    background-color: #a10c24;
}
footer {
    text-align: center;
    margin-top: 20px;
    color: #777;
    font-size: 13px;
}
</style>
</head>
<body>
<div class="container">
<h2>Announcement Results</h2>
<p class="summary">Message sent to <?= count($results) ?> recipient(s)</p>

<table id="results-table">
<tr>
    <th>Phone Number</th>
    <th>SMS Status</th>
    <th>Acknowledgment</th>
</tr>
<?php foreach ($results as $r): ?>
<tr data-phone="<?= htmlspecialchars($r['phone']) ?>">
    <td><?= htmlspecialchars($r['phone']) ?></td>
    <td class="<?= strtolower($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></td>
    <td class="pending">
        <?php if (!empty($r['ackUrl'])): ?>
            <a href="<?= htmlspecialchars($r['ackUrl']) ?>" target="_blank">Click to acknowledge</a>
        <?php else: ?>
            N/A
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<div style="text-align:center;">
    <a href="announcement_form.php" class="back-btn">⬅ Back to Form</a>
</div>
<footer>Broadcast System &middot; Red & White Theme</footer>
</div>
</body>
</html>
