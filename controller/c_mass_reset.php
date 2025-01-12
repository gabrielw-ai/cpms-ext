<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for minimum privilege level 4
if (!isset($_SESSION['user_privilege']) || $_SESSION['user_privilege'] < 4) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/controller/conn.php';

try {
    // Use the existing connection from conn.php
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }

    // Handle GET request - Fetch users for a project
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['project'])) {
            throw new Exception("Project parameter is required");
        }

        $project = $_GET['project'];
        
        // For privilege level 4, only allow access to their assigned project
        if ($_SESSION['user_privilege'] === 4) {
            $stmt = $conn->prepare("SELECT project FROM employee_active WHERE nik = ?");
            $stmt->execute([$_SESSION['user_nik']]);
            $userProject = $stmt->fetchColumn();
            
            if ($project !== $userProject) {
                throw new Exception("Access denied to this project");
            }
        }
        
        // Fetch users for the selected project
        $stmt = $conn->prepare("SELECT nik, employee_name, employee_email, project FROM employee_active WHERE project = ? ORDER BY employee_name");
        $stmt->execute([$project]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clear any previous output or whitespace
        if (ob_get_length()) ob_clean();
        
        // Set headers
        header('Content-Type: application/json');
        
        // Send response
        echo json_encode(['data' => $users]);
        exit;
    }

    // Handle POST request - Reset passwords
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['niks']) || !is_array($input['niks'])) {
            throw new Exception("Invalid request data");
        }

        $niks = $input['niks'];
        $updatedCount = 0;
        $errors = [];

        foreach ($niks as $nik) {
            try {
                // Get user's project to verify access
                $stmt = $conn->prepare("SELECT project FROM employee_active WHERE nik = ?");
                $stmt->execute([$nik]);
                $userProject = $stmt->fetchColumn();

                // For privilege level 4, verify project access
                if ($_SESSION['user_privilege'] === 4) {
                    $stmt = $conn->prepare("SELECT project FROM employee_active WHERE nik = ?");
                    $stmt->execute([$_SESSION['user_nik']]);
                    $adminProject = $stmt->fetchColumn();

                    if ($userProject !== $adminProject) {
                        throw new Exception("Access denied to user: " . $nik);
                    }
                }

                // Reset password to CPMS2025!!
                $hashedPassword = password_hash('CPMS2025!!', PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE employee_active SET password = ? WHERE nik = ?");
                $stmt->execute([$hashedPassword, $nik]);
                
                if ($stmt->rowCount() > 0) {
                    $updatedCount++;
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Clear any previous output or whitespace
        if (ob_get_length()) ob_clean();
        
        // Set headers
        header('Content-Type: application/json');
        
        // Send response
        echo json_encode([
            'success' => true,
            'message' => "Successfully reset " . $updatedCount . " password(s)" . 
                        (count($errors) > 0 ? ". Errors: " . implode(", ", $errors) : ""),
            'updated_count' => $updatedCount,
            'errors' => $errors
        ]);
        exit;
    }

} catch (Exception $e) {
    // Clear any previous output or whitespace
    if (ob_get_length()) ob_clean();
    
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} 