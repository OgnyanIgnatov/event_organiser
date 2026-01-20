<?php
session_start();
require_once '../middlewares/requireOrganiser.php';
require_once '../controllers/clientController.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if(!$request_id) die("Invalid request");

$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    $res = submitFeedback($request_id, $rating, $comment);

    if($res === true) {
        $success = "Feedback submitted successfully";
    } else {
        $errors[] = $res;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Feedback</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Submit Feedback</h1>
        <p>
            <a href="dashboard.php" class="button">Back to dashboard</a>
        </p>

        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="rating">Rating (1-5)</label>
            <input type="number" name="rating" id="rating" min="1" max="5" value="<?= $_POST['rating'] ?? '' ?>" required>

            <label for="comment">Comment</label>
            <textarea name="comment" id="comment"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>

            <button type="submit" class="button">Submit Feedback</button>
        </form>
    </div>
</div>
</body>
</html>