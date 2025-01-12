<?php
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Clear any previous output
ob_clean();
header('Content-Type: application/json');

try {
    if (!isset($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    $project = $_GET['project'];
    
    // Get employees from employee_active table
    $sql = "SELECT NIK as nik, employee_name 
            FROM employee_active 
            WHERE project = ? 
            ORDER BY employee_name";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$project]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure only JSON is output
    echo json_encode([
        'success' => true,
        'data' => $employees
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
