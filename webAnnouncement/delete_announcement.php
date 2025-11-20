<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=bsu_auth', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_POST['id']) || empty($_POST['id'])) {
       echo 'fail';
       exit;
    }

    $id = (int) $_POST['id'];

    // Start transaction to safely delete linked data
    $pdo->beginTransaction();

    // Delete linked acknowledgements first
    $stmt = $pdo->prepare("DELETE FROM announcement_acknowledgements WHERE announcement_id = :id");
    $stmt->execute(['id' => $id]);

    // Delete the announcement itself
    $stmt = $pdo->prepare("DELETE FROM web_announcements WHERE id = :id");
    $stmt->execute(['id' => $id]);

    // Commit deletion
    $pdo->commit();
    echo 'success';

} catch (Exception $e) {
    $pdo->rollBack();
    echo 'fail';
}
?>
