<?php
// Prevent any output before JSON
ob_clean();

require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting c_kpi_individual_process.php");

// Ensure clean headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get JSON input
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Decoded input: " . print_r($input, true));

    // Validate required fields
    if (!isset($input['project']) || !isset($input['metrics']) || !isset($input['queues'])) {
        throw new Exception('Missing required parameters: project, metrics, and queues are required');
    }

    // Ensure project has kpi_ prefix and _individual_mon suffix
    $tableName = $input['project'];
    if (strpos($tableName, 'kpi_') !== 0) {
        $tableName = 'kpi_' . $tableName;
    }
    $tableName .= '_individual_mon';
    
    error_log("Looking for data in table: " . $tableName);

    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
    if (!$tableExists) {
        throw new Exception("Table '$tableName' does not exist");
    }

    // Prepare placeholders for the IN clauses
    $metricPlaceholders = str_repeat('?,', count($input['metrics']) - 1) . '?';
    $queuePlaceholders = str_repeat('?,', count($input['queues']) - 1) . '?';

    // Build the query with explicit column selection
    $sql = "SELECT 
            k.nik,
            ea.employee_name,
            k.kpi_metrics,
            k.queue,
            k.january,
            k.february,
            k.march,
            k.april,
            k.may,
            k.june,
            k.july,
            k.august,
            k.september,
            k.october,
            k.november,
            k.december
        FROM `$tableName` k
        LEFT JOIN employee_active ea ON k.nik = ea.NIK
        WHERE k.kpi_metrics IN ($metricPlaceholders) 
        AND k.queue IN ($queuePlaceholders)
        ORDER BY k.nik, k.kpi_metrics, k.queue";

    $stmt = $conn->prepare($sql);
    $params = array_merge($input['metrics'], $input['queues']);
    $stmt->execute($params);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($data)) {
        echo json_encode([
            'success' => true,
            'message' => 'No data found for the selected criteria',
            'data' => []
        ]);
        exit;
    } else {
        // Normalize data keys to match DataTable columns
        $normalizedData = array_map(function($row) {
            return [
                'nik' => $row['nik'],
                'employee_name' => $row['employee_name'],
                'kpi_metrics' => $row['kpi_metrics'],
                'queue' => $row['queue'],
                'january' => $row['january'] ?? '-',
                'february' => $row['february'] ?? '-',
                'march' => $row['march'] ?? '-',
                'april' => $row['april'] ?? '-',
                'may' => $row['may'] ?? '-',
                'june' => $row['june'] ?? '-',
                'july' => $row['july'] ?? '-',
                'august' => $row['august'] ?? '-',
                'september' => $row['september'] ?? '-',
                'october' => $row['october'] ?? '-',
                'november' => $row['november'] ?? '-',
                'december' => $row['december'] ?? '-'
            ];
        }, $data);

        echo json_encode([
            'success' => true,
            'data' => $normalizedData
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log("Error processing data: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} 