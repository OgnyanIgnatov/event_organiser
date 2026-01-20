<?php
session_start();
require_once 'config/db.php';
require_once 'middlewares/require_auth.php';

$stmt = $connection->prepare("
        SELECT er.id, er.requested_date, er.event_type, er.participants,
        c.username AS client_name,
        o.username AS organiser_name
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        JOIN users o ON o.id = er.organiser_id
        ORDER BY er.requested_date ASC, er.id ASC
");

$stmt->execute();
$res = $stmt->get_result();
$events = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>System Calendar</title>
</head>
<body>
  <h1>System Calendar</h1>

  <p>
    Logged as: <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>) |
    <a href="login.php?logout=1">Logout</a>
  </p>

  <p>
    <?php if ($_SESSION['role'] === 'client'): ?>
      <a href="client_dashboard.php">Dashboard</a>
      <a href="client_calendar.php">My Calendar</a>
    <?php elseif ($_SESSION['role'] === 'organiser'): ?>
      <a href="organiser_dashboard.php">Dashboard</a>
      <a href="organiser_calendar.php">My Calendar</a>
    <?php elseif ($_SESSION['role'] === 'admin'): ?>
      <a href="admin_dashboard.php">Dashboard</a>
    <?php endif; ?>
    <a href="calendar.php">System Calendar</a>
  </p>

  <?php if (!$events): ?>
    <p>No accepted events yet.</p>
  <?php else: ?>
    <table border="1" cellpadding="6">
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th>People</th>
        <th>Client</th>
        <th>Organiser</th>
        <th>Request ID</th>
      </tr>
      <?php foreach ($events as $e): ?>
        <tr>
          <td><?php echo htmlspecialchars($e['requested_date']); ?></td>
          <td><?php echo htmlspecialchars($e['event_type']); ?></td>
          <td><?php echo (int)$e['participants']; ?></td>
          <td><?php echo htmlspecialchars($e['client_name']); ?></td>
          <td><?php echo htmlspecialchars($e['organiser_name']); ?></td>
          <td><?php echo (int)$e['id']; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>