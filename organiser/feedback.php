<?php
session_start();
require_once '../middlewares/requireOrganiser.php';
require_once '../controllers/organiserController.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if (!$request_id) die("Invalid request");

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    $res = submitOrganiserFeedback($request_id, $rating, $comment);

    if ($res === true) {
        $success = "Feedback submitted successfully";
    } else {
        $errors[] = $res;
    }
}

$clientFeedback = getClientFeedback($request_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Organiser Feedback</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Submit Feedback</h1>
        <p><a href="dashboard.php" class="button">Back to dashboard</a></p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($clientFeedback): ?>
            <div class="alert alert-info">
                <strong>Client Feedback:</strong><br>
                Rating: <?= (int)$clientFeedback['rating'] ?><br>
                Comment: <?= nl2br(htmlspecialchars($clientFeedback['comment'])) ?>
            </div>
        <?php else: ?>
            <p>No feedback from the client yet.</p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="rating">Your Rating (1-5)</label>
                <input type="number" name="rating" id="rating" min="1" max="5" value="<?= htmlspecialchars($_POST['rating'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="comment">Your Comment</label>
                <textarea name="comment" id="comment" placeholder="Write your feedback here..."><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="button">Submit Feedback</button>
        </form>
    </div>
</div>
</body>
</html>