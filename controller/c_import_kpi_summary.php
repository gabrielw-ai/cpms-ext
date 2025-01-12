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
function sendJsonResponse($success, $data) {
    // Don't clear all output buffers, just clean the current one
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Set headers to prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Content-Type: application/json');
    
    // Keep session alive
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

try {
    // Initialize UAC and validate session first
    if (!isset($_SESSION['user_nik']) || empty($_SESSION['user_nik'])) {
        sendJsonResponse(false, ['error' => 'Session expired. Please login again.']);
    }

    // Initialize UAC
    $uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

    // Check if user can import
    if (!$uac->canImport()) {
        sendJsonResponse(false, ['error' => 'You do not have permission to import data']);
    }

    // Validate request parameters first
    if (!isset($_POST['table_name']) || empty($_POST['table_name'])) {
        sendJsonResponse(false, ['error' => 'Project table name is required']);
    }

    if (!isset($_FILES['file']) || empty($_FILES['file'])) {
        sendJsonResponse(false, ['error' => 'No file uploaded']);
    }

    // For privilege level 3, verify the user has access to this project
    if ($uac->userPrivilege === 3) {
        $userProject = $uac->getUserProject($conn, $_SESSION['user_nik']);
        // Convert table name (e.g., 'kpi_project_name') to project name format
        $requestedProject = preg_replace('/^kpi_/', '', $_POST['table_name']); // Remove 'kpi_' prefix
        
        // Normalize both strings for comparison (convert to lowercase and standardize separators)
        $normalizedUserProject = strtolower(str_replace(['_', ' '], '', $userProject));
        $normalizedRequestedProject = strtolower(str_replace(['_', ' '], '', $requestedProject));
        
        error_log("Debug - Import - Normalized User Project: '$normalizedUserProject', Normalized Requested Project: '$normalizedRequestedProject'");
        
        if (!$userProject || $normalizedUserProject !== $normalizedRequestedProject) {
            error_log("Import access denied - Original User Project: '$userProject', Original Requested Project: '$requestedProject'");
            sendJsonResponse(false, ['error' => 'You do not have access to this project']);
        }
    }

    // Log debugging information
    error_log("Processing import for table: " . $_POST['table_name'] . " by user: " . $_SESSION['user_nik']);

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
        sendJsonResponse(false, ['error' => $errorMessage]);
    }

    // Get table names
    $baseTableName = $_POST['table_name'];
    $monthlyTableName = $baseTableName . "_mon";

    // Log table names
    error_log("Base table name: " . $baseTableName);
    error_log("Monthly table name: " . $monthlyTableName);

    // Validate file exists
    if (!file_exists($_FILES['file']['tmp_name'])) {
        sendJsonResponse(false, ['error' => 'Uploaded file not found']);
    }

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    } catch (Exception $e) {
        error_log("Excel load error: " . $e->getMessage());
        sendJsonResponse(false, ['error' => 'Failed to load Excel file: ' . $e->getMessage()]);
    }

    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Log row count
    error_log("Number of rows: " . count($rows));

    // Validate we have data
    if (count($rows) < 2) {
        sendJsonResponse(false, ['error' => 'File contains no data']);
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

            // Log row data
            error_log("Processing row " . ($index + 2) . ": " . implode(", ", $row));

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
                // Check if KPI exists
                $stmt = $conn->prepare("SELECT id FROM `$table` WHERE queue = ? AND kpi_metrics = ?");
                $stmt->execute([$queue, $kpiMetrics]);
                $exists = $stmt->fetch();

                if ($exists) {
                    $stmt = $conn->prepare("
                        UPDATE `$table` 
                        SET target = ?, target_type = ?
                        WHERE queue = ? AND kpi_metrics = ?
                    ");
                    $stmt->execute([$target, $targetType, $queue, $kpiMetrics]);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO `$table` 
                        (queue, kpi_metrics, target, target_type)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$queue, $kpiMetrics, $target, $targetType]);
                }
            }

            $processed++;

        } catch (Exception $e) {
            error_log("Row error: " . $e->getMessage());
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $conn->commit();
        error_log("Import successful - Processed $processed records for table: " . $_POST['table_name']);
        
        // Ensure session is preserved before sending response
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true); // Regenerate session ID for security
            session_write_close();
        }
        
        sendJsonResponse(true, [
            'message' => "Successfully processed $processed records",
            'processed' => $processed
        ]);
    } else {
        $conn->rollBack();
        error_log("Import failed with errors: " . implode(", ", $errors));
        sendJsonResponse(false, ['error' => implode(", ", $errors)]);
    }

} catch (Exception $e) {
    error_log("Import error in " . __FILE__ . ": " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Ensure session is preserved
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        session_write_close();
    }
    
    sendJsonResponse(false, [
        'error' => 'Import failed: ' . $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => $e->getLine()
    ]);
} 