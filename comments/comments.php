<?php
session_start();
require_once '../config/db.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if ($request_id <= 0) die("Invalid request");

// Only allow public & accepted events
$stmt = $connection->prepare("
    SELECT id FROM event_requests
    WHERE id=? AND is_public=1 AND status='accepted_by_client'
    LIMIT 1
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) die("Comments not available");

// Fetch all comments
$stmt = $connection->prepare("
    SELECT c.comment, c.created_at, u.username, c.user_role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.request_id=?
    ORDER BY c.created_at ASC
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Comments</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Comments</h1>
        <p><a href="../index.php" class="button">Back to Events</a></p>

        <?php if (!$comments): ?>
            <p>No comments yet.</p>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="comment-box">
                    <div class="comment-header"><?= htmlspecialchars($c['username']); ?> (<?= ucfirst($c['user_role']); ?>)</div>
                    <div class="comment-time"><?= htmlspecialchars($c['created_at']); ?></div>
                    <div class="comment-content"><?= nl2br(htmlspecialchars($c['comment'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
