<?php
require_once 'conn.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['project']) || !isset($_GET['id'])) {
        throw new Exception('Project and ID parameters are required');
    }

    $project = $_GET['project'];
    $id = $_GET['id'];
    $tableName = "kpi_" . strtolower(str_replace(" ", "_", $project));

    $stmt = $conn->prepare("
        SELECT 
            id,
            queue,
            kpi_metrics,
            target,
            target_type
        FROM `$tableName`
        WHERE id = ?
    ");
    
    $stmt->execute([$id]);
    $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kpi) {
        throw new Exception('KPI not found');
    }

    echo json_encode([
        'success' => true,
        'kpi' => $kpi
    ]);

} catch (Exception $e) {
    error_log("Error in get_kpi_details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 