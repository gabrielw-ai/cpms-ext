<?php
require_once dirname(__DIR__) . '/controller/conn.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // Validate table parameter
    if (!isset($_GET['table']) || empty($_GET['table'])) {
        throw new Exception('Table parameter is required');
    }

    // Validate view parameter
    $view = $_GET['view'] ?? 'weekly';
    if (!in_array($view, ['weekly', 'monthly'])) {
        throw new Exception('Invalid view parameter');
    }

    $tableName = $_GET['table'];
    
    // Validate table name format
    if (!preg_match('/^kpi_[a-zA-Z0-9_]+$/', $tableName)) {
        throw new Exception('Invalid table name format');
    }

    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("Table '$tableName' does not exist");
    }

    // Set table names based on view type - same as DataTables
    if ($view === 'monthly') {
        $kpiTable = $tableName . "_mon";  // Use _mon table for KPI definitions
        $valuesTable = $tableName . "_mon_values";
        $periodColumn = 'month';
    } else {
        $kpiTable = $tableName;  // Use base table for KPI definitions
        $valuesTable = $tableName . "_values";
        $periodColumn = 'week';
    }
    
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set basic headers
    $sheet->setCellValue('A1', 'Queue');
    $sheet->setCellValue('B1', 'KPI Metrics');
    $sheet->setCellValue('C1', 'Target');
    $sheet->setCellValue('D1', 'Target Type');
    
    // Set period headers based on view type
    if ($view === 'monthly') {
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                  'July', 'August', 'September', 'October', 'November', 'December'];
        foreach ($months as $i => $month) {
            $col = getColumnLetter($i + 5); // Start from column E
            $sheet->setCellValue($col . '1', substr($month, 0, 3));
        }
        $periodCount = 12;
    } else {
        for ($i = 1; $i <= 52; $i++) {
            $weekNum = str_pad($i, 2, '0', STR_PAD_LEFT);
            $col = getColumnLetter($i + 4);
            $sheet->setCellValue($col . '1', "WK$weekNum");
        }
        $periodCount = 52;
    }
    
    // Get data with JOIN - using same query as DataTables
    $sql = "SELECT k.queue, k.kpi_metrics, k.target, k.target_type, 
                   v.{$periodColumn}, v.value
            FROM `$kpiTable` k
            LEFT JOIN `{$valuesTable}` v ON k.id = v.kpi_id
            ORDER BY k.queue, k.kpi_metrics, v.{$periodColumn}";
    
    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process data into rows - same logic as DataTables
    $rowData = [];
    
    foreach ($data as $row) {
        $kpiKey = $row['queue'] . '|' . $row['kpi_metrics'];
        
        if (!isset($rowData[$kpiKey])) {
            $rowData[$kpiKey] = [
                'queue' => $row['queue'],
                'kpi_metrics' => $row['kpi_metrics'],
                'target' => $row['target'],
                'target_type' => $row['target_type'],
                'values' => array_fill(1, $periodCount, null)
            ];
        }
        
        // Store value using the correct period
        $period = $row[$periodColumn];
        if ($period !== null) {
            $rowData[$kpiKey]['values'][$period] = $row['value'];
        }
    }
    
    // Write data to sheet
    $currentRow = 2;
    foreach ($rowData as $row) {
        $sheet->setCellValue('A' . $currentRow, $row['queue']);
        $sheet->setCellValue('B' . $currentRow, $row['kpi_metrics']);
        $sheet->setCellValue('C' . $currentRow, $row['target']);
        $sheet->setCellValue('D' . $currentRow, $row['target_type']);
        
        // Write period values
        for ($i = 1; $i <= $periodCount; $i++) {
            $col = getColumnLetter($i + 4);
            $value = $row['values'][$i];
            if ($value !== null) {
                if ($row['target_type'] === 'percentage') {
                    $value .= '%';
                }
                $sheet->setCellValue($col . $currentRow, $value);
            }
        }
        $currentRow++;
    }
    
    // Style headers
    $lastCol = $view === 'monthly' ? 'P' : 'BA'; // P for 12 months (E to P), BA for 52 weeks
    $headerRange = 'A1:' . $lastCol . '1';
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E2EFDA']
        ]
    ]);
    
    // Auto-size columns
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create writer and output file
    $writer = new Xlsx($spreadsheet);
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $tableName . '_export.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Before creating the Excel file
    $rowCount = count($rowData);
    $_SESSION['success'] = "Success to export $rowCount rows";
    
    // Save to output
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

// Helper function to convert number to Excel column letter
function getColumnLetter($n) {
    $letter = '';
    while ($n > 0) {
        $n--;
        $letter = chr(65 + ($n % 26)) . $letter;
        $n = intdiv($n, 26);
    }
    return $letter;
}
?>
