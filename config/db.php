<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'event_organiser');
define('DB_PORT', 3307);

$connection = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    DB_PORT
);

if ($connection->connect_error) {
    die('Database connection failed: ' . $connection->connect_error);
}