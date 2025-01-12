<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix include paths
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/controller/conn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error output to avoid corrupting JSON

// Check for privilege level 6
if (!isset($_SESSION['user_privilege']) || $_SESSION['user_privilege'] != 6) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    // Use the existing connection from conn.php
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }

    // Handle GET request - Fetch all roles
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = "SELECT id, role, privileges FROM role_mgmt ORDER BY id";
        $stmt = $conn->prepare($query);
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing query");
        }
        
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clear any previous output or whitespace
        if (ob_get_length()) ob_clean();
        
        // Set headers
        header('Content-Type: application/json');
        
        // Send response
        echo json_encode(['data' => $roles]);
        exit;
    }

    // Handle POST request - Update role privileges
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Clear any previous output or whitespace
        if (ob_get_length()) ob_clean();
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['id']) || !isset($data['privileges'])) {
            throw new Exception('Missing required fields');
        }
        
        $id = intval($data['id']);
        $privileges = intval($data['privileges']);
        
        // Validate privileges range
        if ($privileges < 1 || $privileges > 6) {
            throw new Exception('Invalid privileges value (must be between 1 and 6)');
        }
        
        $stmt = $conn->prepare("UPDATE role_mgmt SET privileges = :privileges WHERE id = :id");
        $stmt->bindParam(':privileges', $privileges, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update privileges");
        }
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("No role found with ID: " . $id);
        }
        
        // Set headers
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Privileges updated successfully',
            'affected_rows' => $stmt->rowCount()
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
