<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';

$errors = [];
$username = '';
$email = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$role || !$password) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $res = addUser($username, $email, $role, $password);
        if ($res === true) {
            header("Location: dashboard.php?success=User added successfully");
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
    <title>Add User</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <form method="post">
            <h1>Add User</h1>
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

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Add User</button>
        </form>
    </div>
</div>
</body>
</html>