<?php
session_start();
require_once 'config/db.php';
require_once 'middlewares/require_auth.php';

$user_id = (int)$_SESSION['id'];
$request_id = (int)($_GET['id'] ?? 0);

$errors = [];
$success = '';

$stmt = $connection->prepare("
    SELECT er.id, er.client_id, er.organiser_id, er.event_type, er.requested_date,
    er.status, er.is_public, er.created_at,
    er.correction_note, er.organiser_note,
    c.username AS client_name,
    o.username AS organiser_name
    FROM event_requests er
    JOIN users c ON c.id = er.client_id
    JOIN users o ON o.id = er.organiser_id
    WHERE er.id = ?
    LIMIT 1
");

$stmt->bind_param('i', $request_id);
$stmt->execute();
$res = $stmt->get_result();
$event = $res->fetch_assoc();
$stmt->close();

if(!$event){
    die('Event not found');
}

$isClient = ((int)$event['client_id'] === $user_id);
$isOrganiser = ((int)$event['organiser_id'] === $user_id);
$isAdmin = ($_SESSION['role'] === 'admin');

$isPublicEvent = ((int)$event['is_public'] === 1 && $event['status'] === 'accepted_by_client');
$canComment = $isAdmin || $isClient || $isOrganiser || $isPublicEvent;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $text = trim($_POST['comment_text'] ?? '');

    if($text === ''){
        $errors[] = "Comment cannot be empty";
    }

    if(!$canComment){
        $errors[] = "You cannot comment on this event.";
    }

    if(!$errors){
        $stmt = $connection->prepare("
            INSERT INTO event_comments (request_id, user_id, comment_text)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iis', $request_id, $user_id, $text);

        if($stmt->execute()){
            header('Location: request_view.php?id='.$request_id."&success=1");
            exit();
        }
        else{
            $errors[] = "Database error.";
        }
        $stmt->close();
    }
}

if(isset($_GET['success'])){
    $success = "Comment added";
}

$stmt = $connection->prepare("
    SELECT ec.comment_text, ec.created_at, u.username, u.role
    FROM event_comments ec
    JOIN users u ON u.id = ec.user_id
    WHERE ec.request_id = ?
    ORDER BY ec.created_at ASC
");

$stmt->bind_param('i', $request_id);
$stmt->execute();
$res = $stmt->get_result();
$comments = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Event View</title>
</head>
<body>

  <h1>Event</h1>

  <p>
    Logged as: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b> |
    <a href="login.php?logout=1">Logout</a>
  </p>

  <p>
    <?php if ($_SESSION['role'] === 'client'): ?>
      <a href="client_dashboard.php">← Back to Dashboard</a>
    <?php elseif ($_SESSION['role'] === 'organiser'): ?>
      <a href="organiser_dashboard.php">← Back to Dashboard</a>
    <?php elseif ($_SESSION['role'] === 'admin'): ?>
      <a href="admin_dashboard.php">← Back to Admin</a>
    <?php else: ?>
      <a href="index.php">← Back</a>
    <?php endif; ?>
  </p>

  <div style="border:1px solid #ccc; padding:12px; max-width:720px;">
    <h2 style="margin-top:0;">
      <?php echo htmlspecialchars($event['event_type']); ?>
      <small>(#<?php echo (int)$event['id']; ?>)</small>
    </h2>

    <p><b>Date:</b> <?php echo htmlspecialchars($event['requested_date']); ?></p>
    <p><b>People:</b> <?php echo (int)$event['participants']; ?></p>
    <p><b>Client:</b> <?php echo htmlspecialchars($event['client_name']); ?></p>
    <p><b>Organiser:</b> <?php echo htmlspecialchars($event['organiser_name']); ?></p>
    <p><b>Status:</b> <?php echo htmlspecialchars($event['status']); ?></p>
    <p><b>Visibility:</b> <?php echo ((int)$event['is_public'] === 1) ? 'Public' : 'Private'; ?></p>

    <?php if (!empty($event['organiser_note'])): ?>
      <p><b>Organiser note:</b><br><?php echo nl2br(htmlspecialchars($event['organiser_note'])); ?></p>
    <?php endif; ?>

    <?php if (!empty($event['correction_note'])): ?>
      <p><b>Client note:</b><br><?php echo nl2br(htmlspecialchars($event['correction_note'])); ?></p>
    <?php endif; ?>

    <?php if ($isPublicEvent): ?>
      <p><em>This is a public event – anyone can comment.</em></p>
    <?php endif; ?>
  </div>

  <?php if ($success): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
  <?php endif; ?>

  <?php if ($errors): ?>
    <ul style="color:red;">
      <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e); ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <h2>Comments</h2>

  <?php if (!$comments): ?>
    <p>No comments yet.</p>
  <?php else: ?>
    <div style="max-width:720px;">
      <?php foreach ($comments as $c): ?>
        <div style="border:1px solid #eee; padding:10px; margin-bottom:8px;">
          <b><?php echo htmlspecialchars($c['username']); ?></b>
          <small>(<?php echo htmlspecialchars($c['role']); ?>)</small>
          <small>— <?php echo htmlspecialchars($c['created_at']); ?></small>
          <div><?php echo nl2br(htmlspecialchars($c['comment_text'])); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($canComment): ?>
    <h3>Add comment</h3>
    <form method="post" style="max-width:720px;">
      <textarea name="comment_text" rows="4" style="width:100%;" placeholder="Write a comment..." required></textarea>
      <button type="submit">Post</button>
    </form>
  <?php else: ?>
    <p><em>You cannot comment on this event.</em></p>
  <?php endif; ?>

</body>
</html>