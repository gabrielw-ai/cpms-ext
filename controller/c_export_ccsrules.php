<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'conn.php';
require_once 'vendor/autoload.php';
require_once 'c_uac.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check user access
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);
if (!$uac->canViewRules()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

try {
    global $conn;
    
    // Get access filter based on user privilege
    $accessFilter = $uac->getRuleAccessFilter($conn, $_SESSION['user_nik'] ?? '');
    
    // Base query
    $sql = "
        SELECT 
            cr.project,
            cr.nik,
            ea.employee_name,
            cr.role,
            cr.tenure,
            cr.case_chronology,
            cr.consequences,
            DATE_FORMAT(cr.effective_date, '%Y-%m-%d') as effective_date,
            DATE_FORMAT(cr.end_date, '%Y-%m-%d') as end_date,
            CASE 
                WHEN cr.end_date < CURDATE() OR cr.status = 'expired' THEN 'expired'
                ELSE 'active'
            END as status
        FROM ccs_rules cr
        LEFT JOIN employee_active ea ON cr.nik = ea.nik
        WHERE 1=1 " . $accessFilter . "
        ORDER BY cr.effective_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()->setTitle("CCS Rules Export");
    
    // Add header row
    $headers = ['Project', 'NIK', 'Name', 'Role', 'Tenure', 'Case Chronology', 
                'Consequences', 'Effective Date', 'End Date', 'Status'];
    
    foreach (range('A', 'J') as $index => $column) {
        $sheet->setCellValue($column . '1', $headers[$index]);
    }

    // Style header row
    $sheet->getStyle('A1:J1')->applyFromArray([
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

    // Add data rows
    $row = 2;
    foreach ($rules as $rule) {
        $sheet->setCellValue('A' . $row, $rule['project'])
              ->setCellValue('B' . $row, $rule['nik'])
              ->setCellValue('C' . $row, $rule['employee_name'])
              ->setCellValue('D' . $row, $rule['role'])
              ->setCellValue('E' . $row, $rule['tenure'])
              ->setCellValue('F' . $row, $rule['case_chronology'])
              ->setCellValue('G' . $row, $rule['consequences'])
              ->setCellValue('H' . $row, $rule['effective_date'])
              ->setCellValue('I' . $row, $rule['end_date'])
              ->setCellValue('J' . $row, $rule['status']);
        
        // Color the status cell based on value
        $statusColor = $rule['status'] === 'active' ? '92D050' : 'FF0000';
        $sheet->getStyle('J' . $row)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => $statusColor
                ]
            ],
            'font' => [
                'color' => [
                    'rgb' => 'FFFFFF'
                ]
            ]
        ]);
        
        $row++;
    }

    // Auto size columns
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set the filename
    $filename = "ccs_rules_export_" . date('Y-m-d') . ".xlsx";

    // Redirect output to client's web browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
