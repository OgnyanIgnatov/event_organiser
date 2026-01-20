<?php
require_once '../config/db.php';
require_once '../middlewares/requireClient.php';

$client_id = (int)$_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $gallery_public = isset($_POST['gallery_public']) ? 1 : 0;

    if ($request_id <= 0) {
        die("Invalid request");
    }

    $stmt = $connection->prepare("SELECT id FROM event_requests WHERE id=? AND client_id=? LIMIT 1");
    $stmt->bind_param("ii", $request_id, $client_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        die("Access denied");
    }

    $stmt = $connection->prepare("UPDATE event_requests SET gallery_public=? WHERE id=?");
    $stmt->bind_param("ii", $gallery_public, $request_id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php");
    exit();
}
?>