<?php
require_once 'conn.php';

header('Content-Type: application/json');

if (isset($_GET['nik'])) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                role,
                join_date,
                TIMESTAMPDIFF(MONTH, join_date, CURRENT_DATE()) as months_diff,
                DATEDIFF(CURRENT_DATE(), join_date) as days_diff
            FROM employee_active 
            WHERE nik = ?
        ");
        $stmt->execute([$_GET['nik']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Calculate tenure
            $months = $result['months_diff'];
            $days = $result['days_diff'];
            $years = floor($months / 12);
            $remaining_months = $months % 12;
            
            // Format tenure string
            if ($months < 1) {
                // Show days if less than a month
                $tenure = $days . " days";
            } else if ($years < 1) {
                // Show only months if less than a year
                $tenure = $months . " months";
            } else {
                // Show years and months
                $tenure = $years . " years";
                if ($remaining_months > 0) {
                    $tenure .= " " . $remaining_months . " months";
                }
            }

            echo json_encode([
                'success' => true, 
                'role' => $result['role'],
                'tenure' => $tenure
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Employee not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'NIK not provided']);
} 