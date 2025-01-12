<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'conn.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Function to safely encode JSON response
function sendJsonResponse($success, $data) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

if (isset($_POST['importKPI']) && isset($_FILES['file'])) {
    try {
        $viewType = $_POST['view_type'] ?? 'weekly';
        $tableName = strtolower($_POST['table_name']);
        $baseTableName = preg_replace('/_mon$/', '', $tableName); // Remove _mon if exists
        
        // Always define both tables for KPI definitions
        $kpiTables = [
            $baseTableName,              // Weekly KPI definitions
            $baseTableName . '_mon'      // Monthly KPI definitions
        ];
        
        // Set values table based on view type
        if ($viewType === 'monthly') {
            $valuesTable = $baseTableName . "_mon_values";
            $periodColumn = 'month';
            $periodCount = 12;
        } else {
            $valuesTable = $baseTableName . "_values";
            $periodColumn = 'week';
            $periodCount = 52;
        }
        
        $conn->beginTransaction();
        
        try {
            // Prepare statements for both tables
            $insertStatements = [];
            foreach ($kpiTables as $table) {
                $insertStatements[$table] = $conn->prepare(
                    "INSERT INTO `$table` (queue, kpi_metrics, target, target_type) 
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     target = VALUES(target),
                     target_type = VALUES(target_type)"
                );
            }

            // Prepare statement for values (only for current view)
            $insertValue = $conn->prepare(
                "INSERT INTO `{$valuesTable}` (kpi_id, {$periodColumn}, value) 
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE value = VALUES(value)"
            );

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
                $_SESSION['error'] = $errorMessage;
                header("Location: ../kpi/viewer?table=" . urlencode($tableName) . "&view=" . $viewType);
                exit;
            }

            $inputFileName = $_FILES['file']['tmp_name'];
            $spreadsheet = IOFactory::load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            array_shift($rows);
            
            // Process Excel rows
            foreach ($rows as $row) {
                if (empty($row[0])) continue;

                $queue = $row[0];
                $kpi_metrics = $row[1];
                $target = $row[2];
                $target_type = $row[3];

                // Insert/Update KPI definitions in BOTH tables
                foreach ($insertStatements as $stmt) {
                    $stmt->execute([$queue, $kpi_metrics, $target, $target_type]);
                }

                // Handle values only for current view type
                if ($viewType === 'monthly') {
                    $getKPIId = $conn->prepare("SELECT id FROM `{$baseTableName}_mon` WHERE queue = ? AND kpi_metrics = ?");
                } else {
                    $getKPIId = $conn->prepare("SELECT id FROM `{$baseTableName}` WHERE queue = ? AND kpi_metrics = ?");
                }

                $getKPIId->execute([$queue, $kpi_metrics]);
                $kpiId = $getKPIId->fetchColumn();

                // Insert values for current view type
                for ($period = 1; $period <= $periodCount; $period++) {
                    $columnIndex = $period + 3;
                    $value = isset($row[$columnIndex]) ? $row[$columnIndex] : null;
                    
                    if ($value !== null && $value !== '') {
                        if ($target_type === 'percentage') {
                            $value = str_replace('%', '', $value);
                        }
                        $insertValue->execute([$kpiId, $period, $value]);
                    }
                }
            }

            if (empty($errors)) {
                $conn->commit();
                $rowCount = count($rows);
                $_SESSION['success'] = "Success to import $rowCount rows";
            } else {
                $conn->rollBack();
                $_SESSION['error'] = "Import failed";
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Import failed: " . $e->getMessage();
    }

    header("Location: ../kpi/viewer?table=" . urlencode($tableName) . "&view=" . $viewType);
    exit;
}
?>
