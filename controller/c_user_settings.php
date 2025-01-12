<?php
session_start();
require_once 'conn.php';
require_once dirname(__DIR__) . '/routing.php';
global $conn;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_POST['new_password'])) {
        throw new Exception('New password is required');
    }

    // Get user NIK from session
    if (!isset($_SESSION['user_nik'])) {
        throw new Exception('User not logged in');
    }

    $nik = $_SESSION['user_nik'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Update password in employee_active table
    $stmt = $conn->prepare("UPDATE employee_active SET password = ? WHERE nik = ?");
    $stmt->execute([$newPassword, $nik]);

    if ($stmt->rowCount() > 0) {
        header('Location: ' . Router::url('user/settings') . '?success=1');
    } else {
        throw new Exception('Failed to update password');
    }

} catch (Exception $e) {
    error_log("Error in user settings: " . $e->getMessage());
    header('Location: ' . Router::url('user/settings') . '?error=' . urlencode($e->getMessage()));
}
