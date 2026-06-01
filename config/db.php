<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/~u202301457/BookVerse');

$host     = 'localhost';
$user     = 'u202301457';
$password = 'Faisal2005@@';
$database = 'db202301457';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed.");
}
$conn->set_charset("utf8");
