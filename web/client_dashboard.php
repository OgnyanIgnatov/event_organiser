<?php
session_start();
require_once 'config/db.php';
require_once 'middlewares/require_client.php';

$client_id = (int)$_SESSION['id'];
$errors = [];
$success = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $request_id = (int)($_POST['request_id'] ?? 0);
  $action = $_POST['action'] ?? '';
  $note = trim($_POST['correction_note'] ?? '');

  if($request_id <= 0) $errors[] = 'Invalid request';

  $newStatus = null;
  if($action === 'accept') $newStatus = "accepted_by_client";
  if($action === 'decline') $newStatus = 'declined_by_client';
  if($action === 'needs_correction') $newStatus = "needs_correction";

  if(($action ?? '') === 'toggle_public'){
    $request_id = (int)($_POST['request_id'] ?? 0);
    $make_public = (int)($_POST['make_public'] ?? 0);

    $stmt = $connection->prepare("
      UPDATE event_requests
      SET is_public = ?
      WHERE id = ? AND client_id = ? AND status = 'accepted_by_client'
    ");

    $stmt->bind_param('iii', $make_public, $request_id, $client_id);

    if($stmt->execute() && $stmt->affected_rows === 1){
      header('Location: client_dashboard.php?success=1');
      exit();
    }
    else{
      $errors[] = "Cannot change visibility";
    }
    $stmt->close();
  }

  if($newStatus === null) $errors[] = 'Invalid action.';

  if($newStatus === "needs_correction" && $note === ''){
    $errors[] = "Please write what should be corrected";
  } 

  if(!$errors){
    $allowed_status = "('accepted_by_organiser', 'needs_correction')";

    if($newStatus === 'needs_correction'){
      $stmt = $connection->prepare("
              UPDATE event_requests
              SET status = ?, correction_note = ?
              WHERE id = ? AND client_id = ? AND status IN $allowed_status
      ");
      $stmt->bind_param('ssii', $newStatus, $note, $request_id, $client_id);
    }
    else{
       $stmt = $connection->prepare("
              UPDATE event_requests
              SET status = ?
              WHERE id = ? AND client_id = ? AND status IN $allowed_status
      ");
      $stmt->bind_param('sii', $newStatus, $request_id, $client_id);
    }

    if($stmt->execute() && $stmt->affected_rows === 1){
      header("Location: client_dashboard.php?success=1");
      exit();
    }
    else{
      $errors[] = "Request not found";
    }
    $stmt->close();
  }
}

if(isset($_GET['success'])){
  $success = "Request updated successfully";
}

$stmt = $connection->prepare("
    SELECT er.id, er.event_type, er.requested_date, er.participants, er.status, er.created_at,
    er.correction_note, er.organiser_note, er.is_public,
    u.username AS organiser_name
    FROM event_requests er
    JOIN users u ON u.id = er.organiser_id
    WHERE er.client_id = ?
    ORDER BY er.created_at DESC
");

$stmt->bind_param('i', $client_id);
$stmt->execute();
$res = $stmt->get_result();
$requests = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Client Dashboard</title>
</head>
<body>
  <h1>Client Dashboard</h1>
  <p>
    Logged as: <?php echo htmlspecialchars($_SESSION['username']); ?> |
    <a href="login.php?logout=1">Logout</a>
  </p>

  <p>
    <a href="client_calendar.php">My Calendar</a>
    <a href="calendar.php">System Calendar</a>
  </p>

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

  <p><a href="client_create_request.php">+ New request</a></p>

  <h2>My Requests</h2>

  <?php if (!$requests): ?>
    <p>No requests yet.</p>
  <?php else: ?>
    <table class="table">
      <tr>
        <th>ID</th>
        <th>Organiser</th>
        <th>Type</th>
        <th>Date</th>
        <th>People</th>
        <th>Status</th>
        <th>Visibility</th>
        <th>Comments</th>
        <th>Organiser Note</th>
        <th>Client Note</th>
        <th>Actions</th>
      </tr>

      <?php foreach ($requests as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['organiser_name']); ?></td>
          <td><?php echo htmlspecialchars($r['event_type']); ?></td>
          <td><?php echo htmlspecialchars($r['requested_date']); ?></td>
          <td><?php echo (int)$r['participants']; ?></td>
          <td><?php echo htmlspecialchars($r['status']); ?></td>
          <td>
            <?php if ($r['status'] === 'accepted_by_client'): ?>
              <?php if ((int)$r['is_public'] === 1): ?>
                <b>Public</b>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="action" value="toggle_public">
                  <input type="hidden" name="make_public" value="0">
                  <button type="submit">Make Private</button>
                </form>
              <?php else: ?>
                <b>Private</b>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="action" value="toggle_public">
                  <input type="hidden" name="make_public" value="1">
                  <button type="submit">Make Public</button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <em>—</em>
            <?php endif; ?>
          </td>

<td>
  <a href="request_view.php?id=<?php echo (int)$r['id']; ?>">View comments</a>
</td>

          <td>
            <?php if (!empty($r['organiser_note'])): ?>
              <?php echo nl2br(htmlspecialchars($r['organiser_note']));?>
            <?php else: ?>
              <em>--</em>
            <?php endif;?>
          </td>

          <td>
            <?php if (!empty($r['correction_note'])): ?>
                <div><small><?php echo htmlspecialchars($r['correction_note']); ?></small></div>
            <?php endif; ?>
          </td>

          <td>
            <?php if ($r['status'] === 'accepted_by_organiser'): ?>
              <form method="post" action="client_dashboard.php" style="display:inline;">
                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                <input type="hidden" name="action" value="accept">
                <button type="submit">Accept</button>
              </form>

              <form method="post" action="client_dashboard.php" style="display:inline;">
                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                <input type="hidden" name="action" value="decline">
                <button type="submit" onclick="return confirm('Decline this offer?')">Decline</button>
              </form>

              <form method="post" action="client_dashboard.php" style="display:inline;">
                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                <input type="hidden" name="action" value="needs_correction">
                <input type="text" name="correction_note" placeholder="Write correction..." required>
                <button type="submit">Need correction</button>
              </form>

              <?php else: ?>
                <em> -- </em>
              <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>