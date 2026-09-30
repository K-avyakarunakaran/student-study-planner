<?php
/**
 * Index/Home Page - Student Study Planner
 * Redirects logged-in users to dashboard, guests to login
 */

session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Redirect to login
header("Location: login.php");
exit();
?>
