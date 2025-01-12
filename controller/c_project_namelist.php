<?php
session_start();
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Clear any previous output
ob_clean();
header('Content-Type: application/json');

try {
    // Debug log
    error_log("POST Data: " . print_r($_POST, true));
    
    // Edit Project
    if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['edit_id'])) {
        $sql = "UPDATE project_namelist 
                SET main_project = ?, 
                    project_name = ?, 
                    unit_name = ?, 
                    job_code = ? 
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $_POST['main_project'],
            $_POST['project_name'],
            $_POST['unit_name'],
            $_POST['job_code'],
            $_POST['edit_id']
        ]);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Project updated successfully' : 'Failed to update project'
        ]);
    }
    // Add Project
    else if (isset($_POST['main_project'])) {
        $sql = "INSERT INTO project_namelist (main_project, project_name, unit_name, job_code) 
                VALUES (?, ?, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $_POST['main_project'],
            $_POST['project_name'],
            $_POST['unit_name'],
            $_POST['job_code']
        ]);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Project added successfully' : 'Failed to add project'
        ]);
    }
    // Delete Project
    else if (isset($_POST['id']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $sql = "DELETE FROM project_namelist WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$_POST['id']]);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Project deleted successfully' : 'Failed to delete project'
        ]);
    }
    // Get Project Details
    else if (isset($_GET['id'])) {
        $sql = "SELECT * FROM project_namelist WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['id']]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($project) {
            echo json_encode([
                'success' => true,
                'data' => $project
            ]);
        } else {
            throw new Exception("Project not found");
        }
    }
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
