<?php
require_once 'conn.php';
require_once dirname(__DIR__) . '/routing.php';
global $conn;

// Create role_mgmt table if it doesn't exist
try {
    $sql = "CREATE TABLE IF NOT EXISTS role_mgmt (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    
    // Insert default roles if table is empty
    $checkSql = "SELECT COUNT(*) FROM role_mgmt";
    $count = $conn->query($checkSql)->fetchColumn();
    
    if ($count == 0) {
        $defaultRoles = [
            'Super_User',
            'Team_Leader',
            'Agent'
        ];
        
        $insertSql = "INSERT INTO role_mgmt (role) VALUES (?)";
        $stmt = $conn->prepare($insertSql);
        
        foreach ($defaultRoles as $role) {
            $stmt->execute([$role]);
        }
    }

    // Handle POST requests for CRUD operations
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                if (empty($_POST['role'])) {
                    throw new Exception('Role name is required');
                }
                $stmt = $conn->prepare("INSERT INTO role_mgmt (role) VALUES (?)");
                $stmt->execute([$_POST['role']]);
                header('Location: ' . Router::url('roles') . '?success=added');
                break;
                
            case 'update':
                if (empty($_POST['id']) || empty($_POST['role'])) {
                    throw new Exception('Role ID and name are required');
                }
                $stmt = $conn->prepare("UPDATE role_mgmt SET role = ? WHERE id = ?");
                $stmt->execute([$_POST['role'], $_POST['id']]);
                header('Location: ' . Router::url('roles') . '?success=updated');
                break;
                
            case 'delete':
                if (empty($_POST['id'])) {
                    throw new Exception('Role ID is required');
                }
                $stmt = $conn->prepare("DELETE FROM role_mgmt WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                header('Location: ' . Router::url('roles') . '?success=deleted');
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    }

} catch (Exception $e) {
    error_log("Error in role management: " . $e->getMessage());
    header('Location: ' . Router::url('roles') . '?error=' . urlencode($e->getMessage()));
}
?>
