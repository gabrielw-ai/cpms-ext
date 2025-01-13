<?php
require_once 'conn.php';
require_once 'c_uac.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Initialize UAC
    $uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

    // Check if user can export
    if (!$uac->canExport()) {
        throw new Exception('You do not have permission to export data');
    }

    if (!isset($_GET['project']) || empty($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    // For privilege level 3, verify the user has access to this project
    if ($uac->userPrivilege === 3) {
        $userProject = $uac->getUserProject($conn, $_SESSION['user_nik']);
        // Convert table name (e.g., 'kpi_project_name') to project name format
        $requestedProject = preg_replace('/^kpi_/', '', $_GET['project']); // Remove 'kpi_' prefix
        
        // Normalize both strings for comparison (convert to lowercase and standardize separators)
        $normalizedUserProject = strtolower(str_replace(['_', ' '], '', $userProject));
        $normalizedRequestedProject = strtolower(str_replace(['_', ' '], '', $requestedProject));
        
        error_log("Debug - Export - Normalized User Project: '$normalizedUserProject', Normalized Requested Project: '$normalizedRequestedProject'");
        
        if (!$userProject || $normalizedUserProject !== $normalizedRequestedProject) {
            error_log("Export access denied - Original User Project: '$userProject', Original Requested Project: '$requestedProject'");
            throw new Exception('You do not have access to this project');
        }
    }

    // Get the table name - it's already in the correct format from the select dropdown
    $tableName = $_GET['project'];
    error_log("Exporting data from table: " . $tableName);

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()->setTitle("KPI Summary Export");
    
    // Add header row
    $sheet->setCellValue('A1', 'Queue')
          ->setCellValue('B1', 'KPI Metrics')
          ->setCellValue('C1', 'Target')
          ->setCellValue('D1', 'Target Type');

    // Style header row
    $sheet->getStyle('A1:D1')->applyFromArray([
        'font' => [
            'bold' => true
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'E2EFDA'
            ]
        ]
    ]);

    // Get data from database
    $stmt = $conn->prepare("
        SELECT 
            queue,
            kpi_metrics,
            target,
            target_type
        FROM `$tableName`
        ORDER BY queue, kpi_metrics
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add data to spreadsheet
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['queue'])
              ->setCellValue('B' . $row, $record['kpi_metrics'])
              ->setCellValue('C' . $row, $record['target'])
              ->setCellValue('D' . $row, $record['target_type']);
        $row++;
    }

    // Auto size columns
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set the filename - remove 'kpi_' from project name if it exists
    $projectName = preg_replace('/^kpi_/', '', $tableName);
    $filename = "kpi_summary_{$projectName}_" . date('Y-m-d') . ".xlsx";

    // Redirect output to a client's web browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Clear any output buffers
    while (ob_get_level()) ob_end_clean();

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 