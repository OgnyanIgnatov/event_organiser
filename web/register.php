<?php
    require_once 'controllers/authController.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <div  style="border-radius:10px"  id="main">
        <form  style="border-radius:10px"  action="register.php" method="post">
            <h1>Registration </h1>
            <?php if (count($errors) > 0) : ?>
                <div class="alert alert-danger" style="color: red">
                    <?php foreach ($errors as $error) : ?>
                        <li style="margin-left: 20px; margin-top:5px; list-style-type: none; font-weight: bold"><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <br>
            <p style="margin-left:15%">Username:</p>
            <input type="text" name="username" value="<?php echo $username; ?>" placeholder="Enter username">
            <p style="margin-left:15%">E-mail:</p>
            <input type="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your e-mail">
            <p style="margin-left:15%">Password:</p>
            <input type="password" name="password" placeholder="Enter password">
            <p style="margin-left:15%">Repeat your password:</p>
            <input type="password" name="passwordConf" placeholder="Enter password again">
            <p style="margin-left:15%">Select your role</p>
            <select name="role">
                <option value="client">Client</option>
                <option value="organiser">Organiser</option>
            </select>
            <button type="submit" style="margin: auto; margin-top:20px; margin-bottom:20px; display:block"  class="button" name="signup-btn">Register</button>
            <p style="color: black; text-align: center;">Already a member? <a href="login.php" style="color:#1fab35;">Login</a></p>
        </form>
    </div>
</body>
</html>