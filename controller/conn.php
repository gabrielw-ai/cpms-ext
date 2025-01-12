<?php
require_once __DIR__ . '/../site_config.php';

// Create global connection variable
global $conn;

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set charset to utf8mb4
    $conn->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to safely close the connection
function closeConnection() {
    global $conn;
    $conn = null;
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('closeConnection');
?>
