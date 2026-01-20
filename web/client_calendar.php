<?php
session_start();
require_once 'config/db.php';
require_once 'middlewares/require_client.php';

$client_id = (int)$_SESSION['id'];

$stmt = $connection->prepare("
        SELECT er.id, er.requested_date, er.event_type, er.participants, er.status, er.is_public,
        o.username AS organiser_name
        FROM event_requests er
        JOIN users o ON o.id = er.organiser_id
        WHERE er.client_id = ?
        AND er.status in ('accepted_by_client', 'accepted_by_organiser', 'needs_correction')
        ORDER BY er.requested_date ASC, er.id ASC
");
$stmt->bind_param('i', $client_id);
$stmt->execute();
$res = $stmt->get_result();
$events = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Calendar</title>
</head>
<body>
  <h1>My Calendar</h1>

  <p>
    Logged as: <?php echo htmlspecialchars($_SESSION['username']); ?> |
    <a href="login.php?logout=1">Logout</a>
  </p>

  <p>
    <a href="client_dashboard.php">Dashboard</a> |
    <a href="calendar.php">System Calendar</a> |
    <a href="client_calendar.php">My Calendar</a>
  </p>

  <?php if (!$events): ?>
    <p>No events to show.</p>
  <?php else: ?>
    <table border="1" cellpadding="6">
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th>People</th>
        <th>Organiser</th>
        <th>Status</th>
        <th>Request ID</th>
      </tr>
      <?php foreach ($events as $e): ?>
        <tr>
          <td><?php echo htmlspecialchars($e['requested_date']); ?></td>
          <td><?php echo htmlspecialchars($e['event_type']); ?></td>
          <td><?php echo (int)$e['participants']; ?></td>
          <td><?php echo htmlspecialchars($e['organiser_name']); ?></td>
          <td><?php echo htmlspecialchars($e['status']); ?></td>
          <td><?php echo (int)$e['id']; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>