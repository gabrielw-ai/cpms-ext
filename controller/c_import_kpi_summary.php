<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

require_once 'conn.php';
require_once 'c_uac.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to safely encode JSON response
function sendJsonResponse($success, $message = '', $data = []) {
    // Clean all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers to prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Initialize UAC and validate session first
    if (!isset($_SESSION['user_nik']) || empty($_SESSION['user_nik'])) {
        sendJsonResponse(false, 'Session expired. Please login again.');
    }

    // Initialize UAC
    $uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

    // Check if user can import
    if (!$uac->canImport()) {
        sendJsonResponse(false, 'You do not have permission to import data');
    }

    // Validate request parameters
    if (!isset($_POST['table_name']) || empty($_POST['table_name'])) {
        sendJsonResponse(false, 'Project table name is required');
    }

    if (!isset($_FILES['file']) || empty($_FILES['file'])) {
        sendJsonResponse(false, 'No file uploaded');
    }

    // Validate file upload
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errorMessage = isset($uploadErrors[$_FILES['file']['error']]) 
            ? $uploadErrors[$_FILES['file']['error']] 
            : 'Unknown upload error';
        sendJsonResponse(false, $errorMessage);
    }

    // Get table names
    $baseTableName = $_POST['table_name'];
    $monthlyTableName = $baseTableName . "_mon";

    // Validate file exists
    if (!file_exists($_FILES['file']['tmp_name'])) {
        sendJsonResponse(false, 'Uploaded file not found');
    }

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    } catch (Exception $e) {
        error_log("Excel load error: " . $e->getMessage());
        sendJsonResponse(false, 'Failed to load Excel file: ' . $e->getMessage());
    }

    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Validate we have data
    if (count($rows) < 2) {
        sendJsonResponse(false, 'File contains no data');
    }

    // Remove header row
    array_shift($rows);

    // Start transaction
    $conn->beginTransaction();

    $processed = 0;
    $errors = [];

    foreach ($rows as $index => $row) {
        if (empty($row[0])) continue;

        try {
            $queue = trim($row[0]);
            $kpiMetrics = trim($row[1]);
            $target = trim($row[2]);
            $targetType = strtolower(trim($row[3]));

            // Validate data
            if (empty($queue) || empty($kpiMetrics) || $target === '') {
                throw new Exception("Missing required data in row " . ($index + 2));
            }

            // Validate target type
            if (!in_array($targetType, ['percentage', 'number'])) {
                throw new Exception("Invalid target type '$targetType' in row " . ($index + 2));
            }

            // Update both weekly and monthly tables
            $tables = [$baseTableName, $monthlyTableName];
            
            foreach ($tables as $table) {
                $stmt = $conn->prepare("
                    INSERT INTO `$table` (queue, kpi_metrics, target, target_type)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    target = VALUES(target),
                    target_type = VALUES(target_type)
                ");
                $stmt->execute([$queue, $kpiMetrics, $target, $targetType]);
            }

            $processed++;

        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $conn->commit();
        sendJsonResponse(true, "Successfully processed $processed records", [
            'processed' => $processed
        ]);
    } else {
        $conn->rollBack();
        sendJsonResponse(false, implode(", ", $errors));
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Import error: " . $e->getMessage());
    sendJsonResponse(false, 'Import failed: ' . $e->getMessage());
} 