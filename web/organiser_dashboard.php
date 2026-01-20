<?php
session_start();
require_once 'config/db.php';
require_once 'middlewares/require_organiser.php';

$organiser_id = (int)$_SESSION['id'];
$errors = [];
$success = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $organiser_note = $_POST['organiser_note'] ?? '';


    if($request_id <= 0){
        $errors[] = "Invalid request";
    }

    $newStatus = null;
    $allowed_from_status = 'pending';
    
    if($action === 'accept') $newStatus = "accepted_by_organiser";
    if($action === 'reject') $newStatus = 'rejected_by_organiser';
    if($action === 'reaccept') {$newStatus = 'accepted_by_organiser'; $allowed_from_status = 'needs_correction';}
    if($action === 'needs_correction') {
      $newStatus = 'accepted_by_organiser';
      $allowed_from_status = 'needs_correction';

      if($organiser_note === ''){
        $errors[] = "Write correction note";
      }
    }



    if($newStatus === null){
        $errors[] = 'Invalid action';
    }

    if(!$errors){
        if($action === 'needs_correction'){
          $stmt = $connection->prepare("
            UPDATE event_requests
            SET status = ?, organiser_note = ?
            WHERE id = ? AND organiser_id = ? AND status = ?
          ");
          $stmt->bind_param('ssiis', $newStatus, $organiser_note, $request_id, $organiser_id, $allowed_from_status);
        }
        else{
          $stmt = $connection->prepare("
            UPDATE event_requests
            SET status = ?
            WHERE id = ? AND organiser_id = ? AND status = ?
          ");
          $stmt->bind_param('siis', $newStatus, $request_id, $organiser_id, $allowed_from_status);
        }

        if($stmt->execute()){
            if($stmt->affected_rows === 1){
                header("Location: organiser_dashboard.php?success=1");
                exit();
            }
            else{
                $errors[] = "Request not found.";
            }
        }
        else{
            $errors[] = "Database error";
        }

        $stmt->close();
    }
}

if(isset($_GET['success'])){
    $success = "Request updated successfully";
}

$stmt = $connection->prepare("
        SELECT er.id, er.event_type, er.requested_date, er.participants, er.created_at, er.is_public,
        u.username AS client_name, u.email AS client_email
        FROM event_requests er
        JOIN users u ON u.id = er.client_id
        WHERE er.organiser_id = ? AND er.status = 'pending'
        ORDER BY er.created_at DESC
");

$stmt->bind_param('i', $organiser_id);
$stmt->execute();
$res = $stmt->get_result();
$requests = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $connection->prepare("
        SELECT er.id, er.event_type, er.requested_date, er.participants, er.created_at,
        er.correction_note, er.organiser_note, er.is_public,
        u.username AS client_name, u.email AS client_email
        FROM event_requests er
        JOIN users u ON u.id = er.client_id
        WHERE er.organiser_id = ? AND er.status = 'needs_correction'
        ORDER BY er.created_at DESC
");

$stmt->bind_param('i', $organiser_id);
$stmt->execute();
$res = $stmt->get_result();
$correctionRequests = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Organiser Dashboard</title>
</head>
<body>
  <h1>Organiser Dashboard</h1>
  <p>
    Logged as: <?php echo htmlspecialchars($_SESSION['username']); ?> |
    <a href="login.php?logout=1">Logout</a>
  </p>

  <p>
    <a href="organiser_calendar.php">My Calendar</a>
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

  <h2>Pending Requests</h2>

  <?php if (empty($requests)): ?>
    <p>No pending requests.</p>
  <?php else: ?>
    <table class="table">
      <tr>
        <th>ID</th>
        <th>Client</th>
        <th>Email</th>
        <th>Type</th>
        <th>Date</th>
        <th>People</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>

      <?php foreach ($requests as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['client_name']); ?></td>
          <td><?php echo htmlspecialchars($r['client_email']); ?></td>
          <td><?php echo htmlspecialchars($r['event_type']); ?></td>
          <td><?php echo htmlspecialchars($r['requested_date']); ?></td>
          <td><?php echo (int)$r['participants']; ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          <td>
            <form method="post" action="organiser_dashboard.php" style="display:inline;">
              <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
              <input type="hidden" name="action" value="accept">
              <button type="submit">Accept</button>
            </form>

            <form method="post" action="organiser_dashboard.php" style="display:inline;">
              <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
              <input type="hidden" name="action" value="reject">
              <button type="submit" onclick="return confirm('Reject this request?')">Reject</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <h2>Needs correction</h2>

    <?php if (empty($correctionRequests)): ?>
    <p>No requests needing correction.</p>
    <?php else: ?>
    <table class="table">
        <tr>
        <th>ID</th>
        <th>Client</th>
        <th>Email</th>
        <th>Type</th>
        <th>Date</th>
        <th>People</th>
        <th>Client note</th>
        <th>Organiser Note</th>
        <th>Action</th>
        </tr>

        <?php foreach ($correctionRequests as $r): ?>
        <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['client_name']); ?></td>
            <td><?php echo htmlspecialchars($r['client_email']); ?></td>
            <td><?php echo htmlspecialchars($r['event_type']); ?></td>
            <td><?php echo htmlspecialchars($r['requested_date']); ?></td>
            <td><?php echo (int)$r['participants']; ?></td>
            <td><?php echo nl2br(htmlspecialchars($r['correction_note'] ?? '')); ?></td>
            <td><?php echo nl2br(htmlspecialchars($r['organiser_note']?? ''));?></td>
            <td>
            <form method="post" action="organiser_dashboard.php" style="display:inline;">
                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                <input type="hidden" name="action" value="reaccept">
                <button type="submit">Mark as accepted</button>
            </form>

            <form method = "POST" action="organiser_dashboard.php" style="display:inline;">
                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                <input type="hidden" name="action" value="needs_correction">
                <button type="submit">Needs correction</button>
                <input type="text" name="organiser_note" placeholder="Write correction note..." required>
            </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</body>
</html>
