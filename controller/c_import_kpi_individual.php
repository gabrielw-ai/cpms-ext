<?php
require_once 'conn.php';
require 'vendor/autoload.php';
global $conn;

use PhpOffice\PhpSpreadsheet\IOFactory;

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    if (!isset($_FILES['file']) || !isset($_POST['project'])) {
        throw new Exception('File and project parameters are required');
    }

    $file = $_FILES['file'];
    $project = $_POST['project'];
    $tableName = $project . "_individual_mon";

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed with error code: ' . $file['error']);
    }

    // Load the Excel file
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Remove header row
    array_shift($rows);

    // Begin transaction
    $conn->beginTransaction();

    // Prepare the INSERT ... ON DUPLICATE KEY UPDATE statement
    $sql = "INSERT INTO `$tableName` (
        nik, 
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
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    ) ON DUPLICATE KEY UPDATE 
        employee_name = VALUES(employee_name),
        queue = VALUES(queue),
        january = VALUES(january),
        february = VALUES(february),
        march = VALUES(march),
        april = VALUES(april),
        may = VALUES(may),
        june = VALUES(june),
        july = VALUES(july),
        august = VALUES(august),
        september = VALUES(september),
        october = VALUES(october),
        november = VALUES(november),
        december = VALUES(december)";

    $stmt = $conn->prepare($sql);

    // Process each row
    foreach ($rows as $index => $row) {
        // Skip empty rows
        if (empty($row[0])) continue;

        try {
            $stmt->execute([
                $row[0],  // nik
                $row[1],  // employee_name
                $row[2],  // kpi_metrics
                $row[3],  // queue
                $row[4],  // january
                $row[5],  // february
                $row[6],  // march
                $row[7],  // april
                $row[8],  // may
                $row[9],  // june
                $row[10], // july
                $row[11], // august
                $row[12], // september
                $row[13], // october
                $row[14], // november
                $row[15]  // december
            ]);
        } catch (PDOException $e) {
            throw new Exception("Row " . ($index + 2) . ": " . $e->getMessage());
        }
    }

    // Commit transaction
    $conn->commit();

    die(json_encode([
        'success' => true,
        'message' => 'Data imported successfully'
    ]));

} catch (Exception $e) {
    // Rollback transaction if active
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Error importing data: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Error importing data: ' . $e->getMessage()
    ]));
} 