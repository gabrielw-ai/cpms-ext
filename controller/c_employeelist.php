<?php
session_start();
require_once 'conn.php';
require_once dirname(__DIR__) . '/routing.php';

// Function to create table
function createEmployeeTable($conn) {
    try {
        $checkSql = "SHOW TABLES LIKE 'employee_active'";
        $exists = $conn->query($checkSql)->rowCount() > 0;

        if (!$exists) {
            $sql = "CREATE TABLE employee_active (
                NIK VARCHAR(20) PRIMARY KEY,
                employee_name VARCHAR(100) NOT NULL,
                employee_email VARCHAR(100) NOT NULL,
                role VARCHAR(50) NOT NULL,
                project VARCHAR(100) NOT NULL,
                join_date DATE NOT NULL,
                password VARCHAR(500) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $conn->exec($sql);
        } else {
            // Modify existing password column if it exists
            $checkColumn = "SHOW COLUMNS FROM employee_active LIKE 'password'";
            $columnExists = $conn->query($checkColumn)->rowCount() > 0;
            
            if ($columnExists) {
                // Alter existing column to increase length
                $sql = "ALTER TABLE employee_active MODIFY password VARCHAR(500)";
                $conn->exec($sql);
            } else {
                // Add new column if it doesn't exist
                $sql = "ALTER TABLE employee_active ADD COLUMN password VARCHAR(500) DEFAULT NULL";
                $conn->exec($sql);
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error in createEmployeeTable: " . $e->getMessage());
        return false;
    }
}

// Create table if not exists
createEmployeeTable($conn);

// Function to add new employee
function addEmployee($conn, $data) {
    try {
        // Debug log
        error_log("=== START Adding Employee ===");
        error_log("Raw data: " . print_r($data, true));

        $defaultPassword = "CPMS2025!!";
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

        // Format join_date to ensure MySQL compatibility
        $formattedDate = date('Y-m-d', strtotime($data['join_date']));
        error_log("Formatted date: " . $formattedDate);

        $sql = "INSERT INTO employee_active (
            NIK, 
            employee_name, 
            employee_email, 
            role, 
            project, 
            join_date, 
            password
        ) VALUES (
            :nik, 
            :name, 
            :email, 
            :role, 
            :project, 
            :join_date, 
            :password
        )";
        
        $params = [
            ':nik' => $data['nik'],
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':role' => $data['role'],
            ':project' => $data['project'],
            ':join_date' => $formattedDate,
            ':password' => $hashedPassword
        ];
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            $error = $stmt->errorInfo();
            throw new PDOException("SQL Error: " . $error[2], $error[1]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("PDO Exception in addEmployee: " . $e->getMessage());
        throw $e; // Re-throw to be caught in the switch case
    }
}

// Function to update employee
function updateEmployee($conn, $originalNik, $data) {
    try {
        $sql = "UPDATE employee_active 
                SET NIK = :new_nik,
                    employee_name = :name,
                    employee_email = :email,
                    role = :role,
                    project = :project,
                    join_date = :join_date
                WHERE NIK = :original_nik";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':new_nik' => $data['nik'],
            ':original_nik' => $originalNik,
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':role' => $data['role'],
            ':project' => $data['project'],
            ':join_date' => $data['join_date']
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateEmployee: " . $e->getMessage());
        return false;
    }
}

// Function to delete employee
function deleteEmployee($conn, $nik) {
    try {
        $sql = "DELETE FROM employee_active WHERE NIK = :nik";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':nik' => $nik]);
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get all employees with pagination
function getAllEmployees($conn) {
    try {
        $stmt = $conn->query("
            SELECT 
                ea.NIK,
                ea.employee_name,
                ea.employee_email,
                ea.role,
                ea.project,
                ea.join_date
            FROM employee_active ea
            ORDER BY ea.NIK
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllEmployees: " . $e->getMessage());
        return [];
    }
}

// Add this function to check for duplicate NIK
function checkNIKExists($conn, $nik) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM employee_active WHERE NIK = ?");
        $stmt->execute([$nik]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Update validateEmployeeData function
function validateEmployeeData($data, $conn, $isUpdate = false) {
    $errors = [];
    
    // Validate NIK
    if (empty($data['nik'])) {
        $errors[] = "NIK is required";
    } elseif (!is_numeric($data['nik'])) {
        $errors[] = "NIK must be numeric";
    } elseif ($isUpdate) {
        // For updates, check if the new NIK is different from original and not already in use
        if ($data['nik'] !== $data['original_nik']) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM employee_active WHERE NIK = ?");
            $stmt->execute([$data['nik']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "This NIK already exists";
            }
        }
    } elseif (checkNIKExists($conn, $data['nik'])) {
        $errors[] = "This NIK already exists";
    }
    
    // Rest of validations...
    if (empty($data['name'])) $errors[] = "Name is required";
    if (empty($data['email'])) $errors[] = "Email is required";
    // Validate role exists in role_mgmt
    if (empty($data['role'])) {
        $errors[] = "Role is required";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM role_mgmt WHERE role = ? AND role != 'Super_User'");
        $stmt->execute([$data['role']]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = "Invalid role selected";
        }
    }
    if (empty($data['project'])) $errors[] = "Project is required";
    if (empty($data['join_date'])) $errors[] = "Join date is required";
    
    return $errors;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            try {
                error_log("=== START Add Employee Request ===");
                error_log("POST data: " . print_r($_POST, true));
                
                $errors = validateEmployeeData($_POST, $conn);
                if (empty($errors)) {
                    try {
                        addEmployee($conn, $_POST);
                        $_SESSION['success'] = "Employee added successfully";
                        header('Location: ' . Router::url('employees') . '?success=added');
                        exit();
                    } catch (PDOException $e) {
                        $_SESSION['error'] = "Database Error: " . $e->getMessage();
                        header('Location: ' . Router::url('employees') . '?error=' . urlencode($e->getMessage()));
                        exit();
                    }
                } else {
                    $_SESSION['error'] = implode(', ', $errors);
                    header('Location: ' . Router::url('employees') . '?error=' . urlencode(implode(', ', $errors)));
                    exit();
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                header('Location: ' . Router::url('employees') . '?error=' . urlencode($e->getMessage()));
                exit();
            }
            break;
            
        case 'edit':
            $errors = validateEmployeeData($_POST, $conn, true);
            if (empty($errors)) {
                if (updateEmployee($conn, $_POST['original_nik'], $_POST)) {
                    $_SESSION['success'] = "Employee updated successfully";
                    header('Location: ' . Router::url('employees') . '?success=updated');
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to update employee";
                    header('Location: ' . Router::url('employees') . '?error=update_failed');
                    exit();
                }
            } else {
                $_SESSION['error'] = implode(', ', $errors);
                header('Location: ' . Router::url('employees') . '?error=' . urlencode(implode(', ', $errors)));
                exit();
            }
            break;
            
        case 'delete':
            if (!empty($_POST['nik'])) {
                try {
                    if (deleteEmployee($conn, $_POST['nik'])) {
                        $_SESSION['success'] = "Data successfully deleted";
                    } else {
                        $_SESSION['error'] = "Failed to delete data";
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                header('Location: ' . Router::url('employees'));
                exit();
            }
            break;
            
        case 'bulk_delete':
            if (!empty($_POST['niks'])) {
                try {
                    $niks = json_decode($_POST['niks'], true);
                    if (!is_array($niks)) {
                        throw new Exception("Invalid NIK data");
                    }

                    $conn->beginTransaction();
                    
                    $sql = "DELETE FROM employee_active WHERE NIK = ?";
                    $stmt = $conn->prepare($sql);
                    
                    $deletedCount = 0;
                    foreach ($niks as $nik) {
                        if ($stmt->execute([$nik])) {
                            $deletedCount++;
                        }
                    }
                    
                    if ($deletedCount === count($niks)) {
                        $conn->commit();
                        $_SESSION['success'] = "Data successfully deleted";
                    } else {
                        $conn->rollBack();
                        $_SESSION['error'] = "Failed to delete data";
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
                header('Location: ' . Router::url('employees'));
                exit();
            }
            break;
    }
    exit();
}
?>
