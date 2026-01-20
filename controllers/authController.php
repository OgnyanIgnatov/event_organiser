<?php
session_start();

require_once '../config/db.php';

$errors = [];
$username = "";
$email = "";

if (isset($_POST['signup-btn'])) {

    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $passwordConf = $_POST['passwordConf'] ?? '';
    $role         = $_POST['role'] ?? '';

    if (!in_array($role, ['client', 'organiser'], true)) {
        $errors[] = "Invalid role";
    }

    if ($username === '') {
        $errors[] = "Username required";
    }

    if ($email === '') {
        $errors[] = "Email required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email address is invalid";
    }

    if ($password === '') {
        $errors[] = "Password required";
    }

    if ($password !== $passwordConf) {
        $errors[] = "The two passwords do not match";
    }

    $stmt = $connection->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    $stmt->close();

    $stmt = $connection->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    $stmt->close();

    if (!$errors) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $connection->prepare(
            "INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)"
        );
        $stmt->bind_param('ssss', $username, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            $_SESSION['id']       = $connection->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['email']    = $email;
            $_SESSION['role']     = $role;

            switch ($role) {
                case 'client':
                    header('Location: ../client/dashboard.php');
                    break;
                case 'organiser':
                    header('Location: ../organiser/dashboard.php');
                    break;
                case 'admin':
                    header('Location: ../admin/dashboard.php');
                    break;
            }
            exit;
        } else {
            $errors[] = "Database error during registration";
        }
        $stmt->close();
    }
}

if (isset($_POST['login-btn'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $errors[] = "Username or email required";
    }

    if ($password === '') {
        $errors[] = "Password required";
    }

    if (!$errors) {
        $stmt = $connection->prepare(
            "SELECT * FROM users WHERE email=? OR username=? LIMIT 1"
        );
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors[] = "Wrong credentials";
        } elseif ((int)$user['is_active'] === 0) {
            $errors[] = "Account is disabled";
        } elseif (!password_verify($password, $user['password'])) {
            $errors[] = "Wrong credentials";
        } else {
            $_SESSION['id']       = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];

            switch ($user['role']) {
                case 'client':
                    header('Location: ../client/dashboard.php');
                    break;
                case 'organiser':
                    header('Location: ../organiser/dashboard.php');
                    break;
                case 'admin':
                    header('Location: ../admin/dashboard.php');
                    break;
            }
            exit;
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}