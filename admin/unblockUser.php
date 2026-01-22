<?php
session_start();
require_once '../config/db.php';
require_once '../middlewares/requireAdmin.php';

$user_id = (int)($_GET['user_id'] ?? 0);

if($user_id > 0 && $user_id !== $_SESSION['id']){
    $stmt = $connection->prepare("
        UPDATE users
        SET is_active = 1
        WHERE id = ? AND role != 'admin'
    ");
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php");
exit;