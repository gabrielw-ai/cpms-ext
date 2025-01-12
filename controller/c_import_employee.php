<?php
session_start();
require_once 'conn.php';
require_once 'vendor/autoload.php';
require_once dirname(__DIR__) . '/routing.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    if (!isset($_FILES['file']['tmp_name'])) {
        throw new Exception('No file uploaded');
    }

    $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove header row
    array_shift($rows);
    
    // Begin transaction
    $conn->beginTransaction();
    
    $defaultPassword = "CPMS2025!!";
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO employee_active (
        nik, employee_name, employee_email, role, project, join_date, password
    ) VALUES (
        :nik, :name, :email, :role, :project, :join_date, :password
    ) ON DUPLICATE KEY UPDATE 
        employee_name = VALUES(employee_name),
        employee_email = VALUES(employee_email),
        role = VALUES(role),
        project = VALUES(project),
        join_date = VALUES(join_date)
        -- password is not updated on duplicate
    ";
    
    $stmt = $conn->prepare($sql);
    
    $success = 0;
    $errors = [];
    
    foreach ($rows as $i => $row) {
        if (empty($row[0])) continue; // Skip empty rows
        
        try {
            $result = $stmt->execute([
                ':nik' => $row[0],
                ':name' => $row[1],
                ':email' => $row[2],
                ':role' => $row[3],
                ':project' => $row[4],
                ':join_date' => $row[5],
                ':password' => $hashedPassword
            ]);
            if ($result) $success++;
        } catch (PDOException $e) {
            $errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        $conn->commit();
        $_SESSION['success'] = $success . " data imported";
    } else {
        $conn->rollBack();
        $_SESSION['error'] = "Failed to import data";
    }
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Import failed: " . $e->getMessage();
}

// Redirect back with status
header('Location: ' . Router::url('employees'));
exit;
?> 