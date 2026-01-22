<?php
session_start();
require_once '../middlewares/requireAdmin.php';
require_once '../controllers/adminController.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if (!$request_id || $request_id == $_SESSION['id']) die("Invalid request");

$res = deleteRequest($request_id);
if ($res === true) header("Location: dashboard.php?success=Request deleted");
else header("Location: dashboard.php?errors=".urlencode($res));