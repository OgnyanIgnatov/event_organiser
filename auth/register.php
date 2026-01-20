<?php
require_once '../controllers/authController.php';

$errors = $errors ?? [];
$username = $username ?? '';
$email = $email ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">
    <div class="card">
        <form action="register.php" method="post">
            <h1>Register</h1>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <label for="username">Username</label>
            <input type="text" name="username" id="username"
                   value="<?php echo htmlspecialchars($username); ?>" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email"
                   value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="passwordConf">Confirm password</label>
            <input type="password" name="passwordConf" id="passwordConf" required>

            <label for="role">Role</label>
            <select name="role" id="role" required>
                <option value="client">Client</option>
                <option value="organiser">Organiser</option>
            </select>

            <button type="submit" name="signup-btn">Register</button>

            <p style="margin-top:15px; text-align:center;">
                Already registered? <a href="login.php">Login</a>
            </p>
        </form>
    </div>
</div>

</body>
</html>
