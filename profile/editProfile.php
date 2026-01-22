<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id'], $_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['id'];
$errors = [];
$success = false;

$stmt = $connection->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found");
}

$username = $user['username'];
$email    = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '') {
        $errors[] = "Username and email are required.";
    } else {

        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $connection->prepare(
                "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?"
            );
            $stmt->bind_param("sssi", $username, $email, $hashed, $user_id);
        } else {
            $stmt = $connection->prepare(
                "UPDATE users SET username = ?, email = ? WHERE id = ?"
            );
            $stmt->bind_param("ssi", $username, $email, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['email']    = $email;

            $success = true;
        } else {
            $errors[] = "Failed to update profile.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">
    <div class="card">
        <h1>Edit Profile</h1>

        <p>
            <a href="../<?= htmlspecialchars($_SESSION['role']) ?>/dashboard.php">
                Back to dashboard
            </a>
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                Profile updated successfully.
            </div>
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

        <form method="post">

            <label>Username</label>
            <input type="text" name="username"
                   value="<?= htmlspecialchars($username) ?>" required>

            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($email) ?>" required>

            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password">

            <button type="submit">Save changes</button>

        </form>
    </div>
</div>

</body>
</html>