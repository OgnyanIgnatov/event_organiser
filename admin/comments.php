<?php
session_start();
require_once '../config/db.php';
require_once '../middlewares/requireAdmin.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if (!$request_id) die("Invalid request");

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

$errors = [];
$success = "";

if ($user_role === 'client') {
    $stmt = $connection->prepare("SELECT id FROM event_requests WHERE id=? AND client_id=? LIMIT 1");
    $stmt->bind_param("ii", $request_id, $user_id);
} elseif ($user_role === 'organiser') {
    $stmt = $connection->prepare("SELECT id FROM event_requests WHERE id=? AND organiser_id=? LIMIT 1");
    $stmt->bind_param("ii", $request_id, $user_id);
} else {
    $stmt = $connection->prepare("SELECT id FROM event_requests WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $request_id);
}

$stmt->execute();
$requestData = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$requestData) die("Access denied");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');
    if ($comment === '') {
        $errors[] = "Comment cannot be empty.";
    } else {
        $stmt = $connection->prepare("INSERT INTO comments (request_id, user_id, user_role, comment) VALUES (?,?,?,?)");
        $stmt->bind_param("iiss", $request_id, $user_id, $user_role, $comment);
        if ($stmt->execute()) {
            $success = "Comment added successfully!";
        } else {
            $errors[] = "Database error while adding comment.";
        }
        $stmt->close();
    }
}

$stmt = $connection->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.request_id=? ORDER BY c.created_at ASC");
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
        <p><a href="dashboard.php" class="button">Back to Dashboard</a></p>

        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
            </div>
        <?php endif; ?>
        <h2 style="margin-top:20px;">All Comments</h2>
        <?php if (!$comments): ?>
            <p>No comments yet.</p>
        <?php else: ?>
            <?php foreach($comments as $c): ?>
                <div class="comment-box">
                    <div class="comment-header"><?= htmlspecialchars($c['username']) ?> (<?= ucfirst($c['user_role']) ?>)</div>
                    <div class="comment-time"><?= htmlspecialchars($c['created_at']) ?></div>
                    <div class="comment-content"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <form method="post">
            <label for="comment">Add a Comment:</label>
            <textarea name="comment" id="comment" required><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
            <button type="submit" class="button">Submit Comment</button>
        </form>
    </div>
</div>
</body>
</html>
