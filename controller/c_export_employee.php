<?php
session_start();
require_once 'conn.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers - Remove Employee No
    $headers = ['NIK', 'Name', 'Email', 'Role', 'Project', 'Join Date'];
    foreach (range('A', 'F') as $i => $col) {
        $sheet->setCellValue($col . '1', $headers[$i]);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
    }

    // If template is requested, just output the headers
    if (isset($_GET['template'])) {
        // Nothing else to add for template
    } else {
        // Get employee data - Remove employee_no field
        $sql = "SELECT 
                NIK, 
                employee_name, 
                employee_email, 
                role, 
                project, 
                DATE_FORMAT(join_date, '%Y-%m-%d') as join_date 
                FROM employee_active 
                WHERE role != 'Super_User'
                ORDER BY employee_name";
        $stmt = $conn->query($sql);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add data
        $row = 2;
        foreach ($employees as $employee) {
            $sheet->setCellValue('A' . $row, $employee['NIK']);
            $sheet->setCellValue('B' . $row, $employee['employee_name']);
            $sheet->setCellValue('C' . $row, $employee['employee_email']);
            $sheet->setCellValue('D' . $row, $employee['role']);
            $sheet->setCellValue('E' . $row, $employee['project']);
            $sheet->setCellValue('F' . $row, $employee['join_date']);
            $row++;
        }
    }

    // Auto size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set filename
    $filename = isset($_GET['template']) ? 'employee_template.xlsx' : 'employee_data_' . date('Y-m-d') . '.xlsx';

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Save to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // Set success message in session
    $_SESSION['success'] = "Data successfully exported";

} catch (Exception $e) {
    $_SESSION['error'] = "Failed to export data";
    header('Location: ../view/employee_list.php');
}
exit;
?> 