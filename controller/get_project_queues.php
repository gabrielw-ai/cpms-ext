<?php
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Clear any previous output
ob_clean();
header('Content-Type: application/json');

try {
    if (!isset($_GET['project']) || !isset($_GET['kpi'])) {
        throw new Exception('Project and KPI parameters are required');
    }

    $project = $_GET['project'];
    $kpiMetrics = json_decode($_GET['kpi']);
    
    // Make sure we're using the kpi_ prefixed table
    if (strpos($project, 'kpi_') !== 0) {
        $project = 'kpi_' . $project;
    }
    
    // Query to get queues for the selected KPI metrics
    $placeholders = str_repeat('?,', count($kpiMetrics) - 1) . '?';
    $sql = "SELECT DISTINCT queue FROM `{$project}` 
            WHERE kpi_metrics IN ($placeholders) 
            ORDER BY queue";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute($kpiMetrics);
    
    $queues = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'queues' => $queues
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