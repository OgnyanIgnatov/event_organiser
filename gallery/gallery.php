<?php
session_start();
require_once '../config/db.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if ($request_id <= 0) die("Invalid request");

$stmt = $connection->prepare("
    SELECT id, client_id, is_public FROM event_requests 
    WHERE id=? AND is_public=1 AND status='accepted_by_client' LIMIT 1
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) die("Gallery not available");

$stmt = $connection->prepare("SELECT * FROM galleries WHERE request_id=? AND is_public=1 ORDER BY created_at ASC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gallery</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.gallery { display:flex; flex-wrap:wrap; gap:10px; }
.gallery img { width:200px; border-radius:5px; border:1px solid #ccc; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Gallery</h1>
        <p><a href="../index.php" class="button">Back to Events</a></p>

        <?php if (!$images): ?>
            <p>No public images yet.</p>
        <?php else: ?>
            <div class="gallery">
                <?php foreach ($images as $img): ?>
                    <img src="../uploads/<?= htmlspecialchars($img['image_path']); ?>" alt="Event Image">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
