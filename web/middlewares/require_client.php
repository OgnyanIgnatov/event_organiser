<?php

if(!isset($_SESSION['id'])){
    header("Location: login.php");
    exit;
}

if($_SESSION['role']!=='client'){
    header("Location: login.php");
    exit;
}
