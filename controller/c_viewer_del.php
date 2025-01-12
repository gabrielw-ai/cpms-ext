<?php
session_start();
require_once dirname(__DIR__) . '/controller/conn.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required parameters
    if (!isset($_POST['table_name']) || !isset($_POST['queue']) || !isset($_POST['kpi_metrics'])) {
        throw new Exception('Missing required parameters');
    }

    $tableName = $_POST['table_name'];
    $queue = $_POST['queue'];
    $kpiMetrics = $_POST['kpi_metrics'];

    // Define both weekly and monthly table names
    $weeklyTable = $tableName;
    $weeklyValuesTable = $tableName . "_values";
    $monthlyTable = $tableName . "_mon";
    $monthlyValuesTable = $tableName . "_mon_values";

    // Start transaction
    $conn->beginTransaction();

    try {
        // Delete Weekly KPI
        $weeklyKpiId = null;
        $sqlGetWeeklyId = "SELECT id FROM `$weeklyTable` WHERE queue = ? AND kpi_metrics = ?";
        $stmtWeeklyId = $conn->prepare($sqlGetWeeklyId);
        $stmtWeeklyId->execute([$queue, $kpiMetrics]);
        $weeklyKpiId = $stmtWeeklyId->fetch(PDO::FETCH_COLUMN);

        if ($weeklyKpiId) {
            // Delete weekly values first
            $sqlWeeklyValues = "DELETE FROM `$weeklyValuesTable` WHERE kpi_id = ?";
            $stmtWeeklyValues = $conn->prepare($sqlWeeklyValues);
            $stmtWeeklyValues->execute([$weeklyKpiId]);

            // Then delete weekly KPI
            $sqlWeekly = "DELETE FROM `$weeklyTable` WHERE id = ?";
            $stmtWeekly = $conn->prepare($sqlWeekly);
            $stmtWeekly->execute([$weeklyKpiId]);
        }

        // Delete Monthly KPI
        $monthlyKpiId = null;
        $sqlGetMonthlyId = "SELECT id FROM `$monthlyTable` WHERE queue = ? AND kpi_metrics = ?";
        $stmtMonthlyId = $conn->prepare($sqlGetMonthlyId);
        $stmtMonthlyId->execute([$queue, $kpiMetrics]);
        $monthlyKpiId = $stmtMonthlyId->fetch(PDO::FETCH_COLUMN);

        if ($monthlyKpiId) {
            // Delete monthly values first
            $sqlMonthlyValues = "DELETE FROM `$monthlyValuesTable` WHERE kpi_id = ?";
            $stmtMonthlyValues = $conn->prepare($sqlMonthlyValues);
            $stmtMonthlyValues->execute([$monthlyKpiId]);

            // Then delete monthly KPI
            $sqlMonthly = "DELETE FROM `$monthlyTable` WHERE id = ?";
            $stmtMonthly = $conn->prepare($sqlMonthly);
            $stmtMonthly->execute([$monthlyKpiId]);
        }

        // If neither weekly nor monthly KPI was found
        if (!$weeklyKpiId && !$monthlyKpiId) {
            throw new Exception('KPI not found in either weekly or monthly tables');
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'KPI deleted successfully from both weekly and monthly tables',
            'redirect' => "kpi/viewer?table=" . urlencode($tableName) . "&view=" . urlencode($_POST['view_type'] ?? 'weekly')
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in c_viewer_del.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'redirect' => "kpi/viewer"
    ]);
}
?>