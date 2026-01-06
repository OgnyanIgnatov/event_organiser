<?php

session_start();

require_once 'config/db.php';
require_once 'middlewares/require_client.php';

$errors = [];
$success = '';

$organiser_id = '';
$event_type = '';
$requested_date = '';
$participants = '';

$organisers = [];

$res = $connection ->query("SELECT id, username FROM users WHERE role='organiser' AND is_active = 1 ORDER BY username");
if($res){
    while($row = $res->fetch_assoc()){
        $organisers[] = $row;
    }
}

$allowed_types = ['private_party', 'corporate party', 'team_building', 'birthday', 'other'];
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $organiser_id = (int)($_POST['organiser_id'] ?? 0);
    $event_type = $_POST['event_type'] ?? '';
    $requested_date = $_POST['requested_date'] ?? '';
    $participants = (int)($_POST['participants'] ?? 0);

    if($organiser_id <= 0) $errors[] = "Select an organiser";
    if(!in_array($event_type, $allowed_types, true)) $errors[] = "Select a valid event";

    if(!$requested_date){
        $errors[] = "Select a date";
    }
    else if($requested_date < date("Y-m-d")){
        $errors = "Date cannot be in the past";
    }

    if($participants < 1 || $participants > 500) $errors[] = "Participants must be between 1 and 500";

    if(!$errors){
        $stmt = $connection->prepare("SELECT id FROM users WHERE id=? AND role='organiser' AND is_active=1 LIMIT 1");
        $stmt->bind_param('i', $organiser_id);
        $stmt->execute();
        $ok = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if(!$ok) $errors[] = 'Organiser not available';
    }

    if(!$errors){
        $client_id = (int)$_SESSION['id'];
        $stmt = $connection->prepare("
            INSERT INTO event_requests (client_id, organiser_id, event_type, requested_date, participants)
            VALUES (?,?,?,?,?)
        ");

        $stmt->bind_param("iissi", $client_id, $organiser_id, $event_type, $requested_date, $participants);

        if($stmt->execute()){
            $success = "Request created";

            $organiser_id = '';
            $event_type = '';
            $requested_date = '';
            $participants = '';
        }
        else{
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
</head>
<body>
  <h1>Create Event Request (Step 1)</h1>
  <p><a href="client_dashboard.php">Back to dashboard</a></p>

  <?php if ($success): ?>
    <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($success); ?></p>
  <?php endif; ?>

  <?php if ($errors): ?>
    <ul style="color: red;">
      <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e); ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="client_create_request.php">
    <label>Organiser</label><br>
    <select name="organiser_id" required>
      <option value="">-- select organiser --</option>
      <?php foreach ($organisers as $o): ?>
        <option value="<?php echo (int)$o['id']; ?>"
          <?php echo ((string)$organiser_id === (string)$o['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($o['username']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <br><br>

    <label>Date</label><br>
    <input type="date" name="requested_date" value="<?php echo htmlspecialchars($requested_date); ?>" required>

    <br><br>

    <label>Event type</label><br>
    <select name="event_type" required>
      <option value="">-- select type --</option>
      <?php foreach ($allowed_types as $t): ?>
        <option value="<?php echo $t; ?>" <?php echo ($event_type === $t) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($t); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <br><br>

    <label>Participants</label><br>
    <input type="number" name="participants" min="1" max="5000" value="<?php echo htmlspecialchars((string)$participants); ?>" required>

    <br><br>

    <button type="submit">Submit</button>
  </form>
</body>
</html>