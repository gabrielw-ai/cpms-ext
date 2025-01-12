<?php
session_start();
require_once dirname(__DIR__) . '/controller/conn.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT 
                r.*,
                CASE 
                    WHEN end_date < CURRENT_DATE THEN 'expired'
                    ELSE 'active'
                END as status
            FROM ccs_rules r
            ORDER BY effective_date DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $rules = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format dates
        $row['effective_date'] = date('Y-m-d', strtotime($row['effective_date']));
        $row['end_date'] = date('Y-m-d', strtotime($row['end_date']));
        
        // Calculate tenure if needed
        // ... your existing tenure calculation ...
        
        $rules[] = $row;
    }
    
    echo json_encode([
        'data' => $rules
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_rules.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Failed to fetch rules'
    ]);
}
?> 