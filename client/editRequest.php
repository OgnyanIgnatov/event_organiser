<?php
session_start();
require_once '../middlewares/requireClient.php';
require_once '../controllers/requestController.php';

$client_id = $_SESSION['id'];
$request_id = (int)($_GET['request_id'] ?? 0);

$request = getRequestById($request_id);
if (!$request || $request['client_id'] != $client_id) die("Access denied");

$allowed = ['pending','rejected_by_organiser','needs_correction'];
if (!in_array($request['status'], $allowed)) die("Request cannot be edited");

$errors = [];

$allowed_types = [
    'private_party'   => 'Private Party',
    'corporate_party' => 'Corporate Party',
    'team_building'   => 'Team Building',
    'birthday'        => 'Birthday',
    'other'           => 'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_type     = trim($_POST['event_type']);
    $requested_date = $_POST['requested_date'];
    $participants   = (int)$_POST['participants'];
    $is_public      = (int)($_POST['is_public'] ?? 0);

    $res = updateRequestByClient(
        $request_id,
        $client_id,
        $event_type,
        $requested_date,
        $participants,
        $is_public
    );

    if ($res === true) {
        header("Location: dashboard.php?success=Request updated");
        exit;
    } else {
        $errors[] = $res;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event Request</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Edit Event Request</h1>
        <p><a href="dashboard.php" class="button">Back to dashboard</a></p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <label for="event_type">Event Type</label>
            <select name="event_type" id="event_type" required>
                <option value="">Select type</option>
                <?php foreach ($allowed_types as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($request['event_type'] === $key) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="requested_date">Requested Date</label>
            <input type="date" name="requested_date" id="requested_date" value="<?= htmlspecialchars($request['requested_date']) ?>" required>

            <label for="participants">Participants</label>
            <input type="number" name="participants" id="participants" min="1" value="<?= (int)$request['participants'] ?>" required>

            <label for="is_public">Visibility</label>
            <select name="is_public" id="is_public" required>
                <option value="0" <?= $request['is_public'] == 0 ? 'selected' : '' ?>>Private</option>
                <option value="1" <?= $request['is_public'] == 1 ? 'selected' : '' ?>>Public</option>
            </select>

            <button type="submit" class="button">Update Request</button>
        </form>
    </div>
</div>
</body>
</html>