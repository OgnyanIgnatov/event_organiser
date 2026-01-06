<?php
if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit();
}

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'organiser'){
    header("Location: login.php");
    exit();
}