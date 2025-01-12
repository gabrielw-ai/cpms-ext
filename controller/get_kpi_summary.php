<?php
require_once 'conn.php';
require_once 'c_uac.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Validate input
    if (!isset($_GET['project']) || empty($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    if (!isset($_GET['nik']) || empty($_GET['nik'])) {
        throw new Exception('NIK parameter is required');
    }

    // Initialize UAC
    $uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

    // For privilege level 3, verify the user has access to this project
    if ($uac->userPrivilege === 3) {
        $userProject = $uac->getUserProject($conn, $_GET['nik']);
        // Convert table name (e.g., 'kpi_project_name') to project name format
        $requestedProject = preg_replace('/^kpi_/', '', $_GET['project']); // Remove 'kpi_' prefix
        
        // Normalize both strings for comparison (convert to lowercase and standardize separators)
        $normalizedUserProject = strtolower(str_replace(['_', ' '], '', $userProject));
        $normalizedRequestedProject = strtolower(str_replace(['_', ' '], '', $requestedProject));
        
        error_log("Debug - Normalized User Project: '$normalizedUserProject', Normalized Requested Project: '$normalizedRequestedProject'");
        
        if (!$userProject || $normalizedUserProject !== $normalizedRequestedProject) {
            error_log("Access denied - Original User Project: '$userProject', Original Requested Project: '$requestedProject'");
            throw new Exception('You do not have access to this project');
        }
    }

    // Get the table name
    $tableName = $_GET['project'];

    // Fetch KPI data
    $sql = "SELECT id, queue, kpi_metrics, target, target_type FROM `$tableName` ORDER BY queue";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $kpis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format target values
    foreach ($kpis as &$kpi) {
        if ($kpi['target_type'] === 'percentage') {
            $kpi['target'] = $kpi['target'] . '%';
        }
    }

    echo json_encode([
        'success' => true,
        'kpis' => $kpis
    ]);

} catch (Exception $e) {
    error_log("Error in get_kpi_summary.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 