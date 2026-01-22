<?php
require_once '../middlewares/requireClient.php';
require_once '../controllers/clientController.php';

$client_id = (int)$_SESSION['id'];
$errors = [];
$success = '';

$eventTypeLabels = [
    'private_party'   => 'Private Party',
    'corporate_party' => 'Corporate Party',
    'team_building'   => 'Team Building',
    'birthday'        => 'Birthday',
    'other'           => 'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $note       = trim($_POST['correction_note'] ?? '');
    $gallery_public = isset($_POST['gallery_public']) ? 1 : null;

    if ($action) {
        $result = updateRequestStatusClient($request_id, $client_id, $action, $note);
        if ($result === true) {
            header("Location: dashboard.php?success=1");
            exit;
        } else {
            $errors[] = $result;
        }
    }

    if ($gallery_public !== null) {
        $stmt = $connection->prepare("UPDATE event_requests SET gallery_public=? WHERE id=? AND client_id=? AND status='accepted_by_client'");
        $stmt->bind_param('iii', $gallery_public, $request_id, $client_id);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?success=1");
        exit;
    }
}

if (isset($_GET['success'])) $success = "Request updated successfully";

$pendingRequests   = getClientPendingRequests($client_id);
$actionRequests    = getClientActionRequests($client_id);
$completedRequests = getClientCompletedRequests($client_id);

function readableStatus(string $status): string {
    return match($status) {
        'pending' => 'Pending',
        'rejected_by_organiser' => 'Rejected by Organiser',
        'accepted_by_organiser' => 'Accepted by Organiser',
        'needs_correction' => 'Needs Correction',
        'accepted_by_client' => 'Accepted by Client',
        'declined_by_client' => 'Declined by Client',
        default => ucfirst(str_replace('_',' ',$status))
    };
}
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
            Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> |
            <a href="../index.php">Home</a> |
            <a href="../profile/editProfile.php">Edit Profile</a> |
            <a href="../reports/reportRequest.php">Report Organiser</a> |
            <a href="../auth/login.php?logout=1">Logout</a>
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Pending / Rejected By Organiser Requests</h2>
        <p><a href="createRequest.php" class="button">+ New Request</a></p>

        <?php if (!$pendingRequests): ?>
            <p>No requests.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Organiser</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Participants</th>
                    <th>Visibility</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($pendingRequests as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['organiser_name']) ?></td>
                        <td><?= $eventTypeLabels[$r['event_type']] ?? htmlspecialchars($r['event_type']) ?></td>
                        <td><?= htmlspecialchars($r['requested_date']) ?></td>
                        <td><?= (int)$r['participants'] ?></td>
                        <td><?= $r['is_public'] ? 'Public' : 'Private' ?></td>
                        <td><?= readableStatus($r['status']) ?></td>
                        <td><a href="editRequest.php?request_id=<?= $r['id'] ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <div class="card">
    <h2>Completed Events</h2>
    <?php if (!$completedRequests): ?>
        <p>No completed events.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Organiser</th>
                <th>Type</th>
                <th>Date</th>
                <th>Participants</th>
                <th>Visibility</th>
                <th>Status</th>
                <th>Your Feedback</th>
                <th>Organiser Feedback</th>
                <th>Gallery</th>
                <th>Comments</th>
            </tr>

            <?php foreach ($completedRequests as $r): ?>
                <?php
                    $clientFeedback = getClientFeedback($r['id']);          
                    $organiserFeedback = getOrganiserFeedback($r['id']);
                ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['organiser_name']) ?></td>
                    <td><?= $eventTypeLabels[$r['event_type']] ?? htmlspecialchars($r['event_type']) ?></td>
                    <td><?= htmlspecialchars($r['requested_date']) ?></td>
                    <td><?= (int)$r['participants'] ?></td>
                    <td><?= $r['is_public'] ? 'Public' : 'Private' ?></td>
                    <td><?= readableStatus($r['status']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'accepted_by_client'): ?>
                            <?php if (!$clientFeedback): ?>
                                <a href="feedback.php?request_id=<?= $r['id'] ?>" class="button">Give Feedback</a>
                            <?php else: ?>
                                Rating: <?= (int)$clientFeedback['rating'] ?><br>
                                <?= nl2br(htmlspecialchars($clientFeedback['comment'])) ?><br>
                                <a href="feedback.php?request_id=<?= $r['id'] ?>" class="button">Update Feedback</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span>N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$organiserFeedback): ?>
                            <span>No feedback yet</span>
                        <?php else: ?>
                            Rating: <?= (int)$organiserFeedback['rating'] ?><br>
                            <?= nl2br(htmlspecialchars($organiserFeedback['comment'])) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="toggleGallery.php" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <label class="gallery-toggle-label">Public?
                            <input type="checkbox" name="gallery_public" value="1"
                                <?= $r['gallery_public'] ? 'checked' : '' ?>
                                onchange="this.form.submit()">
                        </label>
                        </form>
                        <a href="gallery.php?request_id=<?= $r['id'] ?>" class="button">View Photos</a>
                    </td>
                    <td>
                        <a href="comments.php?request_id=<?= $r['id'] ?>">View Comments</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</div>
</body>
</html>