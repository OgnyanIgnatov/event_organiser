<?php
session_start();

require_once '../config/db.php';
require_once '../middlewares/requireClient.php';
require_once '../controllers/clientController.php';

$errors = [];
$success = '';

$organiser_id = '';
$event_type = '';
$requested_date = '';
$participants = '';

$organisers = getActiveOrganisers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organiser_id   = (int)($_POST['organiser_id'] ?? 0);
    $event_type     = $_POST['event_type'] ?? '';
    $requested_date = $_POST['requested_date'] ?? '';
    $participants   = (int)($_POST['participants'] ?? 0);

    $errors = validateRequest($organiser_id, $event_type, $requested_date, $participants);

    if (!$errors) {
        $stmt = $connection->prepare("SELECT id FROM users WHERE id=? AND role='organiser' AND is_active=1 LIMIT 1");
        $stmt->bind_param('i', $organiser_id);
        $stmt->execute();
        $ok = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$ok) $errors[] = 'Organiser not available';
    }

    if (!$errors) {
        $client_id = (int)$_SESSION['id'];
        $stmt = $connection->prepare("
            INSERT INTO event_requests (client_id, organiser_id, event_type, requested_date, participants)
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param("iissi", $client_id, $organiser_id, $event_type, $requested_date, $participants);

        if ($stmt->execute()) {
            $success = "Request created successfully";

            $organiser_id = '';
            $event_type = '';
            $requested_date = '';
            $participants = '';
        } else {
            $errors[] = "Database error";
        }
        $stmt->close();
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
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="createRequest.php">
            <label for="organiser">Organiser</label>
            <select name="organiser_id" id="organiser" required>
                <option value="">-- select organiser --</option>
                <?php foreach ($organisers as $o): ?>
                    <option value="<?php echo (int)$o['id']; ?>" <?php echo ((string)$organiser_id === (string)$o['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($o['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="requested_date">Date</label>
            <input type="date" name="requested_date" id="requested_date" value="<?php echo htmlspecialchars($requested_date); ?>" required>

            <label for="event_type">Event type</label>
            <select name="event_type" id="event_type" required>
                <option value="">-- select type --</option>
                <?php
                $allowed_types = ['private_party', 'corporate party', 'team_building', 'birthday', 'other'];
                foreach ($allowed_types as $t): ?>
                    <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($event_type === $t) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="participants">Participants</label>
            <input type="number" name="participants" id="participants" min="1" max="500"
                   value="<?php echo htmlspecialchars((string)$participants); ?>" required>

            <button type="submit">Submit</button>
        </form>
    </div>
</div>

</body>
</html>