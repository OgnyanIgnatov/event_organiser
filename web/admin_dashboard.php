<?php

session_start();
require_once 'config/db.php';
require_once 'middlewares/require_admin.php';

$errors = [];
$success = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $user_id = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if($user_id <= 0){
        $errors = "Invalid user";
    }
    else{
        if($action === 'activate') $is_active = 1;
        elseif($action === 'deactivate') $is_active = 0;
        else $is_active = null;

        if($is_active === null){
            $errors = 'Invalid action';
        }
        else{
            $stmt = $connection->prepare("
                UPDATE users SET is_active = ?
                WHERE id = ? AND role != 'admin'
            ");
            $stmt->bind_param('ii', $is_active, $user_id);
            $stmt->execute();

            if($stmt->affected_rows === 1){
                $success = "User status updated";
            }
            else{
                $errors[] = 'User not found';
            }
            $stmt->close();
        }
    }
}

$res = $connection->query("
    SELECT id, username, email, role, is_active
    FROM users
    ORDER BY id ASC
");
$users = $res->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
</head>
<body>

<h1>Admin Dashboard</h1>

<p>
  Logged as: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b> |
  <a href="login.php?logout=1">Logout</a>
</p>

<p>
  <a href="calendar.php">System Calendar</a>
</p>

<?php if ($success): ?>
  <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<?php if ($errors): ?>
  <ul style="color:red;">
    <?php foreach ($errors as $e): ?>
      <li><?php echo htmlspecialchars($e); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<table border="1" cellpadding="6">
  <tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Role</th>
    <th>Status</th>
    <th>Action</th>
  </tr>

  <?php foreach ($users as $u): ?>
    <tr>
      <td><?php echo $u['id']; ?></td>
      <td><?php echo htmlspecialchars($u['username']); ?></td>
      <td><?php echo htmlspecialchars($u['email']); ?></td>
      <td><?php echo htmlspecialchars($u['role']); ?></td>
      <td><?php echo $u['is_active'] ? 'Active' : 'Blocked'; ?></td>
      <td>
        <?php if ($u['role'] !== 'admin'): ?>
          <form method="post" style="display:inline;">
            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
            <?php if ($u['is_active']): ?>
              <input type="hidden" name="action" value="deactivate">
              <button type="submit">Deactivate</button>
            <?php else: ?>
              <input type="hidden" name="action" value="activate">
              <button type="submit">Activate</button>
            <?php endif; ?>
          </form>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

</body>
</html>
