<?php
    require_once 'controllers/authController.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link type="text/css" rel="stylesheet" href="log.css">
    <link type="text/css" rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Sofia">
    <title>Login</title>
</head>
<body>
    <div id="header">
        <ul>
             <li>
                <a href="register.php">Register</a>
            </li>
        </ul>
    </div>
    <div id="under">
        <img class="logo" src="boeing-1595-cropped.svg" alt="logo">
    </div>
    <div style="border-radius:10px" id="main">
        <form  style="border-radius:10px"  action="login.php" method="post">
        <h1>Login</h1>
        <?php if(count($errors) > 0): ?>
        <div class="alert alert-danger" style="color: red">
            <?php foreach($errors as $error): ?>
                <li style="margin-left: 20px; margin-top:5px; list-style-type: none; font-weight: bold"><?php echo $error; ?></li>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
            <p style="margin-left:15%">E-mail or Username:</p>
            <input type="text" name="username" value="<?php echo $username; ?>" placeholder="Enter e-mail or username">
            <p style="margin-left:15%">Password:</p>
            <input type="password" name="password" placeholder="Enter password">
            <button type="submit" class="button" name="login-btn">Log in</button>
            <p style="text-align: center;">Do not have an account? <a href="register.php" style="color: #1fab35;">Sign Up</a></p>
        </form>
    </div>
</body>
</html>