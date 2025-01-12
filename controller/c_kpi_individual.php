<?php
// Prevent any output before JSON
ob_clean();

require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting c_kpi_individual.php");

// Ensure clean headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    $required = ['project', 'nik', 'name', 'kpi_metrics', 'queue', 'month', 'value'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $project = $_POST['project'];
    $nik = trim($_POST['nik']);
    $name = trim($_POST['name']);
    $kpiMetrics = $_POST['kpi_metrics'];
    $queue = $_POST['queue'];
    $month = strtolower($_POST['month']);
    $value = floatval($_POST['value']);

    // Debug the received data
    error_log("Received data - NIK: $nik, Name: $name, Project: $project");

    // Generate table name
    $tableName = $project . "_individual_mon";
    error_log("Table name for insert: " . $tableName);

    // Check if record exists
    $checkSql = "SELECT id FROM `$tableName` 
                 WHERE nik = :nik 
                 AND kpi_metrics = :metrics 
                 AND queue = :queue";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindValue(':nik', $nik);
    $checkStmt->bindValue(':metrics', $kpiMetrics);
    $checkStmt->bindValue(':queue', $queue);
    $checkStmt->execute();
    $exists = $checkStmt->fetch();

    if ($exists) {
        // Update existing record
        $sql = "UPDATE `$tableName` 
                SET `$month` = :value,
                    employee_name = :name
                WHERE nik = :nik 
                AND kpi_metrics = :metrics 
                AND queue = :queue";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':nik', $nik);
        $stmt->bindValue(':metrics', $kpiMetrics);
        $stmt->bindValue(':queue', $queue);
        $stmt->execute();
    } else {
        // Insert new record
        $sql = "INSERT INTO `$tableName` 
                (nik, employee_name, kpi_metrics, queue, `$month`) 
                VALUES (:nik, :name, :metrics, :queue, :value)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':nik', $nik);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':metrics', $kpiMetrics);
        $stmt->bindValue(':queue', $queue);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
    }

    // Verify the insert/update
    $verifySql = "SELECT * FROM `$tableName` 
                  WHERE nik = :nik 
                  AND kpi_metrics = :metrics 
                  AND queue = :queue";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->bindValue(':nik', $nik);
    $verifyStmt->bindValue(':metrics', $kpiMetrics);
    $verifyStmt->bindValue(':queue', $queue);
    $verifyStmt->execute();
    $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Verification result: " . print_r($result, true));

    echo json_encode([
        'success' => true,
        'message' => 'KPI added successfully',
        'data' => $result
    ]);
    exit;

} catch (Exception $e) {
    error_log("Error in c_kpi_individual.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
