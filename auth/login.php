<?php
require_once '../controllers/authController.php';

$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">
    <div class="card">
        <form action="login.php" method="post">
            <h1>Login</h1>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <label for="username">Username or Email</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" name="login-btn">Login</button>

            <p style="margin-top:15px; text-align:center;">
                Not a member? <a href="register.php">Register</a>
            </p>
        </form>
    </div>
</div>

</body>
</html>