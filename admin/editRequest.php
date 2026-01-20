<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';
require_once '../config/db.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if (!$request_id) die("Invalid request");

$request = getRequestById($request_id);
if (!$request) die("Request not found");

$errors = [];
$clients = getUsersByRole('client');
$organisers = getUsersByRole('organiser');

$client_id = $request['client_id'];
$organiser_id = $request['organiser_id'];
$event_type = $request['event_type'];
$requested_date = $request['requested_date'];
$participants = $request['participants'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id     = (int)($_POST['client_id'] ?? 0);
    $organiser_id  = (int)($_POST['organiser_id'] ?? 0);
    $event_type    = trim($_POST['event_type'] ?? '');
    $requested_date = $_POST['requested_date'] ?? '';
    $participants  = (int)($_POST['participants'] ?? 0);

    if (!$client_id || !$organiser_id || !$event_type || !$requested_date || !$participants) {
        $errors[] = "All fields are required.";
    } else {
        $res = editRequest($request_id, $client_id, $organiser_id, $event_type, $requested_date, $participants);
        if ($res === true) {
            header("Location: dashboard.php?success=Request updated successfully");
            exit;
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
    <title>Edit Event Request</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Edit Event Request</h1>
        <p><a href="dashboard.php">Back to dashboard</a></p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="editRequest.php?request_id=<?php echo $request_id; ?>">
            <label for="client">Client</label>
            <select name="client_id" id="client" required>
                <?php foreach ($clients as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($client_id==$c['id'])?'selected':''; ?>>
                        <?php echo htmlspecialchars($c['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="organiser">Organiser</label>
            <select name="organiser_id" id="organiser" required>
                <?php foreach ($organisers as $o): ?>
                    <option value="<?php echo $o['id']; ?>" <?php echo ($organiser_id==$o['id'])?'selected':''; ?>>
                        <?php echo htmlspecialchars($o['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="event_type">Event Type</label>
            <input type="text" name="event_type" id="event_type" value="<?php echo htmlspecialchars($event_type); ?>" required>

            <label for="requested_date">Requested Date</label>
            <input type="date" name="requested_date" id="requested_date" value="<?php echo htmlspecialchars($requested_date); ?>" required>

            <label for="participants">Participants</label>
            <input type="number" name="participants" id="participants" min="1" value="<?php echo htmlspecialchars($participants); ?>" required>

            <button type="submit">Update Request</button>
        </form>
    </div>
</div>
</body>
</html>