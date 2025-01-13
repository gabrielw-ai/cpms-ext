<?php
require_once 'conn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to create tables for a project
function createProjectTables($conn, $baseTableName) {
    try {
        // Create weekly table (base table)
        $weeklySQL = "CREATE TABLE IF NOT EXISTS `$baseTableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            target VARCHAR(50) NOT NULL,
            target_type VARCHAR(20) NOT NULL,
            week1 DECIMAL(10,2) DEFAULT NULL,
            week2 DECIMAL(10,2) DEFAULT NULL,
            week3 DECIMAL(10,2) DEFAULT NULL,
            week4 DECIMAL(10,2) DEFAULT NULL,
            week5 DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_queue_kpi (queue, kpi_metrics)
        )";
        
        error_log("Creating weekly table with SQL: " . $weeklySQL);
        $conn->exec($weeklySQL);

        // Create monthly table
        $monthlyTableName = $baseTableName . "_mon";
        $monthlySQL = "CREATE TABLE IF NOT EXISTS `$monthlyTableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            target VARCHAR(50) NOT NULL,
            target_type VARCHAR(20) NOT NULL,
            january DECIMAL(10,2) DEFAULT NULL,
            february DECIMAL(10,2) DEFAULT NULL,
            march DECIMAL(10,2) DEFAULT NULL,
            april DECIMAL(10,2) DEFAULT NULL,
            may DECIMAL(10,2) DEFAULT NULL,
            june DECIMAL(10,2) DEFAULT NULL,
            july DECIMAL(10,2) DEFAULT NULL,
            august DECIMAL(10,2) DEFAULT NULL,
            september DECIMAL(10,2) DEFAULT NULL,
            october DECIMAL(10,2) DEFAULT NULL,
            november DECIMAL(10,2) DEFAULT NULL,
            december DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_queue_kpi (queue, kpi_metrics)
        )";
        
        error_log("Creating monthly table with SQL: " . $monthlySQL);
        $conn->exec($monthlySQL);

        // Create individual weekly table
        $individualWeeklyTable = $baseTableName . "_individual";
        $individualWeeklySQL = "CREATE TABLE IF NOT EXISTS `$individualWeeklyTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nik VARCHAR(50) NOT NULL,
            employee_name VARCHAR(255) NOT NULL,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            week1 DECIMAL(10,2) DEFAULT NULL,
            week2 DECIMAL(10,2) DEFAULT NULL,
            week3 DECIMAL(10,2) DEFAULT NULL,
            week4 DECIMAL(10,2) DEFAULT NULL,
            week5 DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_employee_kpi (nik, queue, kpi_metrics)
        )";
        
        error_log("Creating individual weekly table with SQL: " . $individualWeeklySQL);
        $conn->exec($individualWeeklySQL);

        // Create individual monthly table
        $individualMonthlyTable = $baseTableName . "_individual_mon";
        $individualMonthlySQL = "CREATE TABLE IF NOT EXISTS `$individualMonthlyTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nik VARCHAR(50) NOT NULL,
            employee_name VARCHAR(255) NOT NULL,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            january DECIMAL(10,2) DEFAULT NULL,
            february DECIMAL(10,2) DEFAULT NULL,
            march DECIMAL(10,2) DEFAULT NULL,
            april DECIMAL(10,2) DEFAULT NULL,
            may DECIMAL(10,2) DEFAULT NULL,
            june DECIMAL(10,2) DEFAULT NULL,
            july DECIMAL(10,2) DEFAULT NULL,
            august DECIMAL(10,2) DEFAULT NULL,
            september DECIMAL(10,2) DEFAULT NULL,
            october DECIMAL(10,2) DEFAULT NULL,
            november DECIMAL(10,2) DEFAULT NULL,
            december DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_employee_kpi (nik, queue, kpi_metrics)
        )";
        
        error_log("Creating individual monthly table with SQL: " . $individualMonthlySQL);
        $conn->exec($individualMonthlySQL);

        // Create weekly values table
        $weeklyValuesTable = $baseTableName . "_values";
        $weeklyValuesSQL = "CREATE TABLE IF NOT EXISTS `$weeklyValuesTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kpi_id INT NOT NULL,
            week INT NOT NULL,
            value DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_record (kpi_id, week),
            FOREIGN KEY (kpi_id) REFERENCES `$baseTableName`(id) ON DELETE CASCADE
        )";
        
        error_log("Creating weekly values table with SQL: " . $weeklyValuesSQL);
        $conn->exec($weeklyValuesSQL);

        // Create monthly values table
        $monthlyValuesTable = $baseTableName . "_mon_values";
        $monthlyValuesSQL = "CREATE TABLE IF NOT EXISTS `$monthlyValuesTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kpi_id INT NOT NULL,
            month INT NOT NULL,
            value DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_record (kpi_id, month),
            FOREIGN KEY (kpi_id) REFERENCES `{$baseTableName}_mon`(id) ON DELETE CASCADE
        )";
        
        error_log("Creating monthly values table with SQL: " . $monthlyValuesSQL);
        $conn->exec($monthlyValuesSQL);

        return true;
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
        throw $e;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Processing form submission");
        error_log("POST data: " . print_r($_POST, true));
        
        // Check if this is an update action
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            // Validate required fields for update
            $requiredFields = ['id', 'project', 'table_name', 'queue', 'kpi_metrics', 'target', 'target_type'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            // Get table names from the provided table_name
            $baseTableName = $_POST['table_name'];
            $monthlyTableName = $baseTableName . "_mon";
            
            // Define both weekly and monthly tables
            $tables = [
                $baseTableName,             // Weekly table
                $monthlyTableName           // Monthly table
            ];

            // Start transaction
            $conn->beginTransaction();

            try {
                foreach ($tables as $tableName) {
                    // First check if the new combination would create a duplicate
                    if ($_POST['queue'] !== $_POST['original_queue'] || $_POST['kpi_metrics'] !== $_POST['original_kpi_metrics']) {
                        $checkStmt = $conn->prepare("
                            SELECT id FROM `$tableName` 
                            WHERE queue = ? AND kpi_metrics = ? 
                            AND id != ?
                        ");
                        $checkStmt->execute([
                            $_POST['queue'],
                            $_POST['kpi_metrics'],
                            $_POST['id']
                        ]);
                        
                        if ($checkStmt->fetch()) {
                            throw new Exception("This combination of Queue and KPI Metrics already exists");
                        }
                    }

                    // If no duplicate found, proceed with update
                    $stmt = $conn->prepare("
                        UPDATE `$tableName` 
                        SET queue = ?, 
                            kpi_metrics = ?, 
                            target = ?, 
                            target_type = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['queue'],
                        $_POST['kpi_metrics'],
                        $_POST['target'],
                        $_POST['target_type'],
                        $_POST['id']
                    ]);
                }

                // Commit transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'KPI updated successfully'
                ]);
                exit;
                
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        } else {
            // Handle regular insert
            // Validate required fields
            $requiredFields = ['project', 'queue', 'kpi_metrics', 'target', 'target_type'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            // Get the base table name for the selected project
            $baseTableName = "kpi_" . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['project']));
            error_log("Base table name: " . $baseTableName);

            // Create both weekly and monthly tables
            createProjectTables($conn, $baseTableName);

            // Insert KPI definition into both tables
            $tables = [$baseTableName, $baseTableName . "_mon"];
            
            foreach ($tables as $tableName) {
                $stmt = $conn->prepare("
                    INSERT INTO `$tableName` (queue, kpi_metrics, target, target_type) 
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $_POST['queue'],
                    $_POST['kpi_metrics'],
                    $_POST['target'],
                    $_POST['target_type']
                ]);
                
                error_log("KPI definition inserted into $tableName");
            }

            echo json_encode([
                'success' => true,
                'message' => 'KPI created successfully'
            ]);
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Error in form processing: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}
?>

