<?php
// Database configuration for Student Study Planner
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'student_study_planner';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8');

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}
?>
