<?php
session_start();
require_once '../middlewares/requireOrganiser.php';
require_once '../controllers/organiserController.php';

$organiser_id = (int)$_SESSION['id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $note = trim($_POST['organiser_note'] ?? '');

    $result = updateRequestStatus($request_id, $organiser_id, $action, $note);

    if ($result === true) {
        header("Location: dashboard.php?success=1");
        exit;
    } else {
        $errors[] = $result;
    }
}

if (isset($_GET['success'])) {
    $success = "Request updated successfully";
}

$pendingRequests    = getPendingRequests($organiser_id);
$correctionRequests = getCorrectionRequests($organiser_id);
$completedRequests  = getCompletedRequests($organiser_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organiser Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">

    <div class="card">
        <h1>Organiser Dashboard</h1>
        <p>
            Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> |
            <a href="../profile/editProfile.php">Edit Profile</a> |
            <a href="../auth/login.php?logout=1">Logout</a>
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:30px;">
        <h2>Pending Requests</h2>

        <?php if (!$pendingRequests): ?>
            <p>No pending requests.</p>
        <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th><th>Client</th><th>Email</th><th>Type</th>
                <th>Date</th><th>People</th><th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingRequests as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['client_name']) ?></td>
                    <td><?= htmlspecialchars($r['client_email']) ?></td>
                    <td><?= htmlspecialchars($r['event_type']) ?></td>
                    <td><?= $r['requested_date'] ?></td>
                    <td><?= $r['participants'] ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="accept">
                            <button>Accept</button>
                        </form>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button onclick="return confirm('Reject request?')">Reject</button>
                        </form>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="needs_correction">
                            <button>Needs correction</button>
                            <input type="text" name="organiser_note" placeholder="Write a note" required>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:30px;">
        <h2>Needs Correction</h2>

        <?php if (!$correctionRequests): ?>
            <p>No correction requests.</p>
        <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th><th>Client</th><th>Note</th><th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($correctionRequests as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['client_name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['organiser_note'] ?? '')) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="reaccept">
                            <button>Mark Accepted</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:30px;">
        <h2>Completed Events</h2>

        <?php if (!$completedRequests): ?>
            <p>No completed events yet.</p>
        <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>ID</th><th>Client</th><th>Type</th><th>Date</th><th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($completedRequests as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['client_name']) ?></td>
                    <td><?= htmlspecialchars($r['event_type']) ?></td>
                    <td><?= $r['requested_date'] ?></td>
                    <td>
                        <a href="feedback.php?request_id=<?= $r['id'] ?>" class="button">Give Feedback</a><br>
                        <a href="uploadGallery.php?request_id=<?= $r['id'] ?>" class="button">
                            Upload Gallery
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