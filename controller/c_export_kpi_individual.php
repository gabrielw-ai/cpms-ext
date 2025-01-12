<?php
require_once 'conn.php';
require 'vendor/autoload.php';
global $conn;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting c_export_kpi_individual.php");

try {
    if (!isset($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    $project = $_GET['project']; // Already contains 'kpi_' prefix
    $metrics = isset($_GET['kpi']) ? json_decode($_GET['kpi'], true) : [];
    $queues = isset($_GET['queue']) ? json_decode($_GET['queue'], true) : [];
    
    error_log("Exporting KPI for Project: $project");
    error_log("Metrics: " . print_r($metrics, true));
    error_log("Queues: " . print_r($queues, true));

    // Just append _individual_mon to the existing table name
    $tableName = $project . "_individual_mon";
    error_log("Using table name: " . $tableName);

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = [
        'NIK',
        'Employee Name',
        'KPI Metrics',
        'Queue',
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];

    // Write headers using column letters
    foreach ($headers as $col => $header) {
        $colLetter = Coordinate::stringFromColumnIndex($col + 1);
        $sheet->setCellValue($colLetter . '1', $header);
    }

    // Build query based on filters
    $sql = "SELECT 
        nik as NIK,
        employee_name,
        kpi_metrics,
        queue,
        january,
        february,
        march,
        april,
        may,
        june,
        july,
        august,
        september,
        october,
        november,
        december
    FROM `$tableName`";

    $params = [];
    
    if (!empty($metrics) && !empty($queues)) {
        $metricPlaceholders = str_repeat('?,', count($metrics) - 1) . '?';
        $queuePlaceholders = str_repeat('?,', count($queues) - 1) . '?';
        $sql .= " WHERE kpi_metrics IN ($metricPlaceholders) AND queue IN ($queuePlaceholders)";
        $params = array_merge($metrics, $queues);
    }
    
    $sql .= " ORDER BY nik, kpi_metrics, queue";
    
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r($params, true));

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add this after fetching the data
    error_log("Fetched data sample: " . print_r($data[0] ?? [], true));

    // Write data using column letters
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['NIK'] ?? $record['nik'] ?? '');
        $sheet->setCellValue('B' . $row, $record['employee_name']);
        $sheet->setCellValue('C' . $row, $record['kpi_metrics']);
        $sheet->setCellValue('D' . $row, $record['queue']);
        $sheet->setCellValue('E' . $row, $record['january']);
        $sheet->setCellValue('F' . $row, $record['february']);
        $sheet->setCellValue('G' . $row, $record['march']);
        $sheet->setCellValue('H' . $row, $record['april']);
        $sheet->setCellValue('I' . $row, $record['may']);
        $sheet->setCellValue('J' . $row, $record['june']);
        $sheet->setCellValue('K' . $row, $record['july']);
        $sheet->setCellValue('L' . $row, $record['august']);
        $sheet->setCellValue('M' . $row, $record['september']);
        $sheet->setCellValue('N' . $row, $record['october']);
        $sheet->setCellValue('O' . $row, $record['november']);
        $sheet->setCellValue('P' . $row, $record['december']);
        $row++;
    }

    // Auto-size columns
    foreach (range('A', 'P') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Style headers
    $headerRange = 'A1:P1';
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E2EFDA']
        ]
    ]);

    // Create writer and output file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $project . '_individual_kpi.xlsx"');
    header('Cache-Control: max-age=0');

    // Save to output
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log("Error in c_export_kpi_individual.php: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
} 