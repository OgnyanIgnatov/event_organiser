<?php
require_once '../config/db.php';
require_once '../middlewares/requireClient.php';

$client_id = (int)$_SESSION['id'];
$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['correction_note'] ?? '');

    if ($request_id <= 0) $errors[] = 'Invalid request';

    $newStatus = null;
    if ($action === 'accept') $newStatus = "accepted_by_client";
    if ($action === 'decline') $newStatus = 'declined_by_client';
    if ($action === 'needs_correction') $newStatus = "needs_correction";

    if ($newStatus === null && !isset($_POST['gallery_public'])) $errors[] = 'Invalid action.';
    if ($newStatus === "needs_correction" && $note === '') $errors[] = "Please write what should be corrected";

    if (!$errors) {
        if ($newStatus) {
            if ($newStatus === 'needs_correction') {
                $stmt = $connection->prepare("
                    UPDATE event_requests
                    SET status = ?, correction_note = ?
                    WHERE id = ? AND client_id = ? AND status IN ('accepted_by_organiser', 'needs_correction')
                ");
                $stmt->bind_param('ssii', $newStatus, $note, $request_id, $client_id);
            } else {
                $stmt = $connection->prepare("
                    UPDATE event_requests
                    SET status = ?
                    WHERE id = ? AND client_id = ? AND status IN ('accepted_by_organiser', 'needs_correction')
                ");
                $stmt->bind_param('sii', $newStatus, $request_id, $client_id);
            }

            if ($stmt->execute() && $stmt->affected_rows === 1) {
                header("Location: dashboard.php?success=1");
                exit();
            } else {
                $errors[] = "Request not found or invalid action";
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['success'])) $success = "Request updated successfully";

$stmt = $connection->prepare("
    SELECT er.id, er.event_type, er.requested_date, er.participants, er.status, er.created_at, er.correction_note, er.organiser_note,
           er.gallery_public,
           (SELECT COUNT(*) FROM galleries g WHERE g.request_id = er.id) AS total_images,
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
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Client Dashboard</h1>
        <p>
            Logged in as: <?= htmlspecialchars($_SESSION['username']); ?> |
            <a href="../profile/editProfile.php" class="button">Edit Profile</a> |
            <a href="../auth/login.php?logout=1">Logout</a>
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top: 30px;">
        <h2>My Requests</h2>
        <p><a href="createRequest.php" class="button">+ New request</a></p>

        <?php if (!$requests): ?>
            <p>No requests yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Organiser</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>People</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= (int)$r['id']; ?></td>
                        <td><?= htmlspecialchars($r['organiser_name']); ?></td>
                        <td><?= htmlspecialchars($r['event_type']); ?></td>
                        <td><?= htmlspecialchars($r['requested_date']); ?></td>
                        <td><?= (int)$r['participants']; ?></td>
                        <td><?= htmlspecialchars($r['status']); ?></td>
                        <td>
                            <?php if (!empty($r['organiser_note'])): ?>
                                <div style="margin-bottom: 8px; padding: 6px; border: 1px solid #eee;">
                                    <small>
                                        <b>Organiser Note:</b><br>
                                        <?php echo nl2br(htmlspecialchars($r['organiser_note'])); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            <?php if (in_array($r['status'], ['accepted_by_organiser', 'needs_correction'], true)): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button>Accept</button>
                                </form>

                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button onclick="return confirm('Decline this offer?')">Decline</button>
                                </form>

                                <?php if (in_array($r['status'], ['pending','rejected_by_organiser','needs_correction'])): ?>
                                    <a href="editRequest.php?request_id=<?= $r['id'] ?>" class="button">Edit</a>
                                <?php endif; ?>

                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="needs_correction">
                                    <button>Need correction</button>
                                    <input type="text" name="correction_note" placeholder="Correction..." required>
                                </form>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'accepted_by_organiser'): ?>
                                <a href="feedback.php?request_id=<?= $r['id'] ?>" class="button">Give Feedback</a>
                                <form method="post" action="toggleGallery.php" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <label class="gallery-toggle-label">
                                    <input type="checkbox" name="gallery_public" value="1"
                                        <?= $r['gallery_public'] ? 'checked' : '' ?>
                                        onchange="this.form.submit()">
                                    Gallery Public
                                </label>
                                </form>
                                    <a href="gallery.php?request_id=<?= $r['id'] ?>" class="button">View Gallery</a>
                            <?php endif; ?>
                            <?php if (!empty($r['correction_note'])): ?>
                                <div><small>Note: <?= htmlspecialchars($r['correction_note']) ?></small></div>
                            <?php endif; ?>

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