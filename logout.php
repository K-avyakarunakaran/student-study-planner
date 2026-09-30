<?php
/**
 * Logout Page - Student Study Planner
 * Destroys user session and redirects to login
 */

session_start();

// Destroy all session variables
session_destroy();

// Redirect to login page
header("Location: login.php?logout=success");
exit();
?>
