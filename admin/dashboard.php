<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';
require_once '../controllers/clientController.php';

$users = getAllUsers();
$requests = getAllRequests();

$success = $_GET['success'] ?? '';
$errors  = $_GET['errors'] ?? '';

$eventTypeLabels = [
    'private_party'=>'Private Party',
    'corporate_party'=>'Corporate Party',
    'team_building'=>'Team Building',
    'birthday'=>'Birthday',
    'other'=>'Other'
];

function readableStatus(string $status): string {
    return match($status) {
        'pending'=>'Pending',
        'rejected_by_client'=>'Rejected by Client',
        'accepted_by_client'=>'Accepted by Client',
        'needs_correction'=>'Needs Correction',
        'accepted_by_organiser'=>'Accepted by Organiser',
        'rejected_by_organiser'=>'Rejected by Organiser',
        default=>ucfirst(str_replace('_',' ',$status))
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Admin Dashboard</h1>
        <p>
            Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> |
            <a href="../index.php">Home</a> | 
            <a href="../auth/logout.php">Logout</a>
        </p>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><li><?= htmlspecialchars($errors) ?></li></ul>
            </div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Users Management</h2>
        <p><a href="addUser.php" class="button">+ Add User</a></p>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role']) ?></td>
                        <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <a href="editUser.php?user_id=<?= $u['id'] ?>" class="button">Edit</a>
                            <?php if ($u['id'] != $_SESSION['id']): ?>
                                <a href="deleteUser.php?user_id=<?= $u['id'] ?>" onclick="return confirm('Delete this user?')" class="button">Delete</a>
                                <?php if ($u['is_active']): ?>
                                    <a href="blockUser.php?user_id=<?= $u['id'] ?>" onclick="return confirm('Block this user?')" class="button">Block</a>
                                <?php else: ?>
                                    <a href="unblockUser.php?user_id=<?= $u['id'] ?>" onclick="return confirm('Unblock this user?')" class="button">Unblock</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card" style="margin-top: 30px;">
        <h2>Event Requests Management</h2>
        <p><a href="addRequest.php" class="button">+ Add Request</a></p>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th><th>Client</th><th>Organiser</th><th>Event Type</th>
                    <th>Date</th><th>Participants</th><th>Status</th>
                    <th>Oragniser Feedback</th><th>Client Feedback</th><th>Gallery</th><th>Comments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['client_name']) ?></td>
                        <td><?= htmlspecialchars($r['organiser_name']) ?></td>
                        <td><?= $eventTypeLabels[$r['event_type']] ?? htmlspecialchars($r['event_type']) ?></td>
                        <td><?= htmlspecialchars($r['requested_date']) ?></td>
                        <td><?= (int)$r['participants'] ?></td>
                        <td><?= readableStatus($r['status']) ?></td>
                        <td>
                            <?php
                            $organiserFeedback = getOrganiserFeedback($r['id']); 
                            if ($organiserFeedback):
                                echo "Rating: " . (int)$organiserFeedback['rating'] . "<br>";
                                echo nl2br(htmlspecialchars($organiserFeedback['comment']));
                            else:
                                echo "<span>No feedback</span>";
                            endif;
                            ?>
                        </td>
                        <td>
                            <?php
                            $clientFeedback = getClientFeedback($r['id']); 
                            if ($clientFeedback):
                                echo "Rating: " . (int)$clientFeedback['rating'] . "<br>";
                                echo nl2br(htmlspecialchars($clientFeedback['comment']));
                            else:
                                echo "<span>No feedback</span>";
                            endif;
                            ?>
                        </td>
                        <td>
                            <a href="uploadGallery.php?request_id=<?= $r['id'] ?>" class="button">View Photos</a>
                        </td>
                        <td>
                            <a href="comments.php?request_id=<?= $r['id'] ?>" class="button">View Comments</a>
                        </td>
                        <td>
                            <a href="editRequest.php?request_id=<?= $r['id'] ?>" class="button">Edit</a>
                            <a href="deleteRequest.php?request_id=<?= $r['id'] ?>" class="button" onclick="return confirm('Delete this request?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>