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
    
    // Make sure we're using the kpi_ prefixed table
    if (strpos($project, 'kpi_') !== 0) {
        $project = 'kpi_' . $project;
    }
    
    // First check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE '{$project}'");
    if ($checkTable->rowCount() === 0) {
        throw new Exception("Table '{$project}' does not exist");
    }
    
    // Query to get KPI metrics for the project
    $sql = "SELECT DISTINCT kpi_metrics FROM `{$project}` ORDER BY kpi_metrics";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'metrics' => $metrics
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