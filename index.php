<?php
session_start();
require_once 'config/db.php';

$events = [];
$errors = [];

$stmt = $connection->prepare("
    SELECT er.id, er.event_type, er.requested_date, er.participants, er.created_at,
    c.username AS client_name, o.username AS organiser_name
    FROM event_requests er
    JOIN users c ON c.id = er.client_id
    JOIN users o ON o.id = er.organiser_id
    WHERE er.is_public = 1
    AND er.status = 'accepted_by_client'
    ORDER BY er.requested_date DESC, er.id DESC
");

if($stmt && $stmt->execute()){
    $res = $stmt->get_result();
    $events = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
else{
    $errors[] = "Database errors while loading events";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Organiser</title>
    <link rel="stylesheet" href="css/style.css"> <!-- ако е ../css/style.css, смени -->
</head>
<body>

<div class="container">

    <div class="card">
        <h1>Event Organiser</h1>

        <p style="margin-top:10px;">
            <?php if (!isset($_SESSION['id'])): ?>
                <a href="auth/register.php" class="button">Register</a>
                |
                <a href="auth/login.php" class="button">Log In</a>
            <?php else: ?>
                Logged in as: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b>
                (<?php echo htmlspecialchars($_SESSION['role']); ?>)
                |
                <?php if ($_SESSION['role'] === 'client'): ?>
                    <a href="client/dashboard.php" class="button">Dashboard</a>
                <?php elseif ($_SESSION['role'] === 'organiser'): ?>
                    <a href="organiser/dashboard.php" class="button">Dashboard</a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="button">Dashboard</a>
                <?php endif; ?>
                |
                <a href="auth/login.php?logout=1" class="button">Logout</a>
            <?php endif; ?>
        </p>
    </div>

    <div class="card">
        <h2>Public Events</h2>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$events): ?>
            <p>No public events yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>People</th>
                    <th>Client</th>
                    <th>Organiser</th>
                    <th>Comments</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['requested_date']); ?></td>
                        <td><?php echo htmlspecialchars($e['event_type']); ?></td>
                        <td><?php echo (int)$e['participants']; ?></td>
                        <td><?php echo htmlspecialchars($e['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($e['organiser_name']); ?></td>
                        <td style="white-space:nowrap;">
                            <a class="button" href="request_view.php?id=<?php echo (int)$e['id']; ?>">
                                View Comments
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>