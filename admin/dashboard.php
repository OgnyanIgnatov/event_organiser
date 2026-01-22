<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';

$users = getAllUsers();
$requests = getAllRequests();

$success = $_GET['success'] ?? '';
$errors  = $_GET['errors'] ?? '';
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
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> |
            <a href="../index.php">Home</a> | 
            <a href="../auth/logout.php">Logout</a>
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <li><?php echo htmlspecialchars($errors); ?></li>
                </ul>
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
                    <td><?php echo (int)$u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['role']); ?></td>
                    <td><?php echo $u['is_active'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a href="editUser.php?user_id=<?php echo $u['id']; ?>">Edit</a>
                        <?php if ($u['id'] != $_SESSION['id']): ?>
                        | <a href="deleteUser.php?user_id=<?php echo $u['id']; ?>"
                             onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        <?php endif; ?>
                        <?php if ($u['id'] != $_SESSION['id']): ?>
                            <?php if ($u['is_active']): ?>
                                | <a href="blockUser.php?user_id=<?php echo $u['id']; ?>"
                                    onclick="return confirm('Block this user?')"
                                >
                                    Block
                                </a>
                            <?php else: ?>
                                | <a href="unblockUser.php?user_id=<?php echo $u['id']; ?>"
                                onclick="return confirm('Unblock this user?')"
                                >
                                    Unblock
                                </a>
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
                <th>Date</th><th>Participants</th><th>Status</th><th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?php echo (int)$r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['organiser_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['event_type']); ?></td>
                    <td><?php echo htmlspecialchars($r['requested_date']); ?></td>
                    <td><?php echo (int)$r['participants']; ?></td>
                    <td><?php echo htmlspecialchars($r['status']); ?></td>
                    <td>
                        <a href="editRequest.php?request_id=<?php echo $r['id']; ?>">Edit</a>
                        | <a href="deleteRequest.php?request_id=<?php echo $r['id']; ?>"
                             onclick="return confirm('Are you sure you want to delete this request?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>