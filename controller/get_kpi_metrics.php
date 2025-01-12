<?php
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

header('Content-Type: application/json');

try {
    if (!isset($_GET['table'])) {
        throw new Exception('Table parameter is required');
    }

    $tableName = $_GET['table'];
    
    // Verify if table exists
    $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($stmt->rowCount() === 0) {
        throw new Exception('Invalid table name');
    }

    // Get distinct KPI metrics from the table
    $sql = "SELECT DISTINCT kpi_metrics FROM `$tableName` WHERE kpi_metrics IS NOT NULL ORDER BY kpi_metrics";
    $stmt = $conn->query($sql);
    $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($metrics);

} catch (Exception $e) {
    error_log("Error getting KPI metrics: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 