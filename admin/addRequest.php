<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';
require_once '../config/db.php';

$errors = [];
$success = '';

$client_id = '';
$organiser_id = '';
$event_type = '';
$requested_date = '';
$participants = '';
$is_public = 0;

$allowed_types = [
    'private_party'   => 'Private Party',
    'corporate_party' => 'Corporate Party',
    'team_building'   => 'Team Building',
    'birthday'        => 'Birthday',
    'other'           => 'Other'
];

$clients = getUsersByRole('client');
$organisers = getUsersByRole('organiser');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id     = (int)($_POST['client_id'] ?? 0);
    $organiser_id  = (int)($_POST['organiser_id'] ?? 0);
    $event_type    = trim($_POST['event_type'] ?? '');
    $requested_date = $_POST['requested_date'] ?? '';
    $participants  = (int)($_POST['participants'] ?? 0);
    $is_public      = (int)($_POST['is_public'] ?? 0);

    if (!$client_id || !$organiser_id || !$event_type || !$requested_date || !$participants) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $connection->prepare("SELECT id FROM users WHERE id=? AND role='organiser' AND is_active=1 LIMIT 1");
        $stmt->bind_param('i', $organiser_id);
        $stmt->execute();
        $ok = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$ok) $errors[] = "Selected organiser is not available.";
    }

    if (!$errors) {
        $res = addRequest($client_id, $organiser_id, $event_type, $requested_date, $participants);
        if ($res === true) {
            $success = "Request added successfully!";
            $client_id = $organiser_id = $event_type = $requested_date = $participants = '';
        } else {
            $errors[] = $res;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Event Request</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">
    <div class="card">
        <h1>Create Event Request</h1>
        <p><a href="dashboard.php">Back to dashboard</a></p>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="createRequest.php">
            <label for="organiser">Organiser</label>
            <select name="organiser_id" id="organiser" required>
                <option value="">Select Organiser</option>
                <?php foreach ($organisers as $o): ?>
                    <option value="<?= (int)$o['id']; ?>" <?= ((string)$organiser_id === (string)$o['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($o['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="requested_date">Date</label>
            <input type="date" name="requested_date" id="requested_date" value="<?= htmlspecialchars($requested_date); ?>" required>
            <label for="event_type">Event type</label>
            <select name="event_type" id="event_type" required>
                <option value="">Select type</option>
                <?php foreach ($allowed_types as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key); ?>" <?= ($event_type === $key) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="participants">Participants</label>
            <input type="number" name="participants" id="participants" min="1" max="500"
                   value="<?= htmlspecialchars((string)$participants); ?>" required>
            <label for="is_public">Visibility</label>
            <select name="is_public" id="is_public" required>
                <option value="0" <?= $is_public === 0 ? 'selected' : '' ?>>Private</option>
                <option value="1" <?= $is_public === 1 ? 'selected' : '' ?>>Public</option>
            </select>

            <button type="submit">Submit</button>
        </form>
    </div>
</div>
</body>
</html>