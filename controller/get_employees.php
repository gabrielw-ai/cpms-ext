<?php
require_once dirname(__DIR__) . '/controller/conn.php';

$project = $_POST['project'] ?? '';

try {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT nik, employee_name, role 
        FROM employee_active 
        WHERE project = ?
        ORDER BY employee_name
    ");
    
    $stmt->execute([$project]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $employees
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 