<?php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../auth/login.php");
    exit;
}