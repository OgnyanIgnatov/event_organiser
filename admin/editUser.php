<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';

$user_id = (int)($_GET['user_id'] ?? 0);
if (!$user_id) die("Invalid user");

$user = getUserById($user_id);
if (!$user) die("User not found");

$errors = [];
$username = $user['username'];
$email = $user['email'];
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$role) {
        $errors[] = "Username, email, and role are required.";
    } else {
        $res = editUser($user_id, $username, $email, $role, $password ?: null);
        if ($res === true) {
            header("Location: dashboard.php?success=User updated successfully");
            exit;
        } else {
            $errors[] = $res;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <form method="post">
            <h1>Edit User</h1>
            <p><a href="dashboard.php">Back to dashboard</a></p>
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label>Role</label>
            <select name="role" required>
                <option value="admin" <?php echo $role=='admin'?'selected':''; ?>>Admin</option>
                <option value="organiser" <?php echo $role=='organiser'?'selected':''; ?>>Organiser</option>
                <option value="client" <?php echo $role=='client'?'selected':''; ?>>Client</option>
            </select>

            <label>Password (leave blank to keep current)</label>
            <input type="password" name="password">

            <button type="submit">Update User</button>
        </form>
    </div>
</div>
</body>
</html>