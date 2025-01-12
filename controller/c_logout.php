<?php
session_start();
require_once 'conn.php';

// Clear all session data
session_unset();
session_destroy();

// Close database connection
$conn = null;

// Redirect to login page
header('Location: ../view/login.php');
exit;