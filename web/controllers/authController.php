<?php
    session_start();
    require 'config/db.php';

    $errors = array();
    $username = "";
    $email = "";

    if(isset($_POST['signup-btn'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $passwordConf = $_POST['passwordConf'];
        $role = $_POST['role'];

        if(empty($username)) {
         $errors['username'] = "Username required"; 
        }

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email address is invalid";
        }

        if(empty($email)) {
            $errors['email'] = "Email required"; 
        }

        if(empty($password)) {
            $errors['password'] = "Password required"; 
        }

        if($password !== $passwordConf) {
            $errors['password'] = "The two passwords do not match";
        }

        if(!isset($_POST['role'])){
            $errors['role'] = "You have not set a role";
        }

        $emailQuery = "SELECT * FROM  users WHERE email=? LIMIT 1"; 
    
        $stmt = $connection->prepare($emailQuery);
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $userCount = $result->num_rows;
        $stmt->close();

        if($userCount > 0) {
            $errors['email'] = "Email already exists";
        }

        $usernameQuery = "SELECT * FROM users WHERE username=? LIMIT 1";
        $stmt = $connection->prepare($usernameQuery);
        $stmt->bind_param('s', $username);
        $stmt->execute();

        $resultUsername = $stmt->get_result();
        $usernameCount = $resultUsername->num_rows;
        $stmt->close();

        if($usernameCount > 0) {
            $errors['username'] = "Username already exists";
        }

        if(count($errors) === 0) {
            $password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param('ssss', $username, $email, $password, $role);

            if($stmt->execute()){
                $user_id = $connection->insert_id;
                $_SESSION['id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                $_SESSION['message'] = "You are now logged in!";
                $_SESSION['alert-class'] = "alert-success";
                header('location: index.php');
                exit();
            } else {
                $errors['db_error'] = "Database error: failed to register";
            }
        }
    }

    if(isset($_POST['login-btn'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if(empty($username)) {
            $errors['username'] = "Username required"; 
        }

        if(empty($password)) {
            $errors['password'] = "Password required"; 
        }

        if(count($errors) === 0) {
            $sql = "SELECT * FROM users WHERE email=? OR username=? LIMIT 1";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
    
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
    
            if (password_verify($password, $user['password'])) {
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
    
                $_SESSION['message'] = "You are now logged in!";
                $_SESSION['alert-class'] = "alert-success";
                switch($user['role']){
                    case 'client':
                        header('Location: client_dashboard.php');
                        break;
                    case 'organiser':
                        header('Location: organiser_dashboard.php');
                        break;
                    case 'admin':
                        header('Location: admin_dashboard.php');
                        break;
                    default:
                        header('Location: index.php');
                }
                exit();
            } else {
                $errors['login_fail'] = "Wrong credentials";
            }
        }
    }

    if (isset($_GET['logout'])) {
        session_destroy();
        unset($_SESSION['id']);
        unset($_SESSION['username']);
        unset($_SESSION['email']);
        unset($_SESSION['role']);
        header('location: login.php');
        exit();
    }