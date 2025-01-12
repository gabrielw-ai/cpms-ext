<?php
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Clear any previous output
ob_clean();
header('Content-Type: application/json');

try {
    if (!isset($_GET['nik'])) {
        throw new Exception('NIK parameter is required');
    }

    $nik = $_GET['nik'];
    
    // Query to get employee details from employee_active table
    $sql = "SELECT 
                role,
                TIMESTAMPDIFF(YEAR, join_date, CURDATE()) as tenure
            FROM employee_active 
            WHERE NIK = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nik]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        // Format tenure
        $tenure = $employee['tenure'] . ' year(s)';
        
        echo json_encode([
            'success' => true,
            'role' => $employee['role'],
            'tenure' => $tenure
        ]);
    } else {
        throw new Exception('Employee not found');
    }
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} 