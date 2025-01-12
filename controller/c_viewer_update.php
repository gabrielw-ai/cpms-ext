<?php
session_start();
require_once dirname(__DIR__) . '/controller/conn.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields for KPI update
    $required_fields = [
        'table_name', 
        'queue', 
        'kpi_metrics', 
        'target', 
        'target_type',
        'original_queue',
        'original_kpi_metrics'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Missing required field: $field");
        }
    }

    $tableName = trim($_POST['table_name']);
    
    // Define both weekly and monthly table names
    $weeklyTable = $tableName;
    $monthlyTable = $tableName . "_mon";

    // Start transaction
    $conn->beginTransaction();

    try {
        // Update Weekly KPI
        $weeklyKpiId = null;
        $sqlGetWeeklyId = "SELECT id FROM `$weeklyTable` 
                          WHERE queue = ? AND kpi_metrics = ?";
        $stmtWeeklyId = $conn->prepare($sqlGetWeeklyId);
        $stmtWeeklyId->execute([
            $_POST['original_queue'],
            $_POST['original_kpi_metrics']
        ]);
        $weeklyKpiId = $stmtWeeklyId->fetch(PDO::FETCH_COLUMN);

        if ($weeklyKpiId) {
            // Update weekly KPI
            $sqlWeekly = "UPDATE `$weeklyTable` SET 
                         queue = ?,
                         kpi_metrics = ?,
                         target = ?,
                         target_type = ?
                         WHERE id = ?";
            $stmtWeekly = $conn->prepare($sqlWeekly);
            $stmtWeekly->execute([
                $_POST['queue'],
                $_POST['kpi_metrics'],
                $_POST['target'],
                $_POST['target_type'],
                $weeklyKpiId
            ]);
        }

        // Update Monthly KPI
        $monthlyKpiId = null;
        $sqlGetMonthlyId = "SELECT id FROM `$monthlyTable` 
                           WHERE queue = ? AND kpi_metrics = ?";
        $stmtMonthlyId = $conn->prepare($sqlGetMonthlyId);
        $stmtMonthlyId->execute([
            $_POST['original_queue'],
            $_POST['original_kpi_metrics']
        ]);
        $monthlyKpiId = $stmtMonthlyId->fetch(PDO::FETCH_COLUMN);

        if ($monthlyKpiId) {
            // Update monthly KPI
            $sqlMonthly = "UPDATE `$monthlyTable` SET 
                          queue = ?,
                          kpi_metrics = ?,
                          target = ?,
                          target_type = ?
                          WHERE id = ?";
            $stmtMonthly = $conn->prepare($sqlMonthly);
            $stmtMonthly->execute([
                $_POST['queue'],
                $_POST['kpi_metrics'],
                $_POST['target'],
                $_POST['target_type'],
                $monthlyKpiId
            ]);
        }

        // If neither weekly nor monthly KPI was found
        if (!$weeklyKpiId && !$monthlyKpiId) {
            throw new Exception('KPI not found in either weekly or monthly tables');
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'KPI updated successfully in both weekly and monthly tables'
        ]);

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Error in c_viewer_update.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>