<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';

$user_id = (int)($_GET['user_id'] ?? 0);
if (!$user_id || $user_id == $_SESSION['id']) die("Invalid user");

$res = deleteUser($user_id);
if ($res === true) header("Location: dashboard.php?success=User deleted");
else header("Location: dashboard.php?errors=".urlencode($res));