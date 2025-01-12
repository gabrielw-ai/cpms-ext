<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent any output before headers
ob_start();

require_once dirname(__DIR__) . '/controller/conn.php';

// Only set JSON header for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
}

$userPrivilege = $_SESSION['user_privilege'] ?? 0;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Clear any previous output
        ob_clean();
        
        $action = $_POST['action'] ?? '';
        
        // Before processing any edit/delete actions
        if ($action === 'edit' || $action === 'delete') {
            // Only allow privilege > 2
            if ($userPrivilege <= 2) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action'
                ]);
                exit;
            }
        }
        
        switch ($action) {
            case 'add':
                // Prevent self-assignment of CCS rules
                if ($_POST['nik'] === $_SESSION['user_nik']) {
                    throw new Exception("You cannot assign CCS rules to yourself");
                }

                // Handle adding new rule
                $required_fields = ['project', 'nik', 'name', 'role', 'case_chronology', 'ccs_rule', 'effective_date'];
                foreach ($required_fields as $field) {
                    if (!isset($_POST[$field]) || empty($_POST[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }

                // Begin transaction
                $conn->beginTransaction();

                try {
                    // Handle file upload
                    $document_path = null;
                    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = dirname(__DIR__) . '/uploads/ccs_docs/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_extension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid('doc_') . '.' . $file_extension;
                        $document_path = 'uploads/ccs_docs/' . $new_filename;

                        if (!move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $new_filename)) {
                            throw new Exception("Error uploading file");
                        }
                    }

                    // Calculate end date based on CCS rule
                    $effective_date = new DateTime($_POST['effective_date']);
                    $end_date = clone $effective_date;
                    
                    if (strpos($_POST['ccs_rule'], 'WR') === 0) {
                        $end_date->modify('+1 year -1 day');
                    } else {
                        $end_date->modify('+6 months -1 day');
                    }

                    // Strip 'kpi_' prefix from project name
                    $projectName = preg_replace('/^kpi_/', '', $_POST['project']);
                    
                    // Insert new rule
                    $stmt = $conn->prepare("INSERT INTO ccs_rules (project, nik, name, role, tenure, case_chronology, 
                                          consequences, effective_date, end_date, supporting_doc_url, status) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    
                    $result = $stmt->execute([
                        $projectName,
                        $_POST['nik'],
                        $_POST['name'],
                        $_POST['role'],
                        $_POST['tenure'],
                        $_POST['case_chronology'],
                        $_POST['ccs_rule'],
                        $_POST['effective_date'],
                        $end_date->format('Y-m-d'),
                        $document_path
                    ]);

                    if (!$result) {
                        throw new Exception("Error adding rule");
                    }

                    $conn->commit();

                    // Clear output buffer before sending response
                    ob_clean();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rule added successfully'
                    ]);
                    exit;

                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
                break;

            case 'edit':
                // Clear any existing output and start fresh
                ob_end_clean();
                ob_start();
                
                // Set JSON header
                header('Content-Type: application/json');

                try {
                    // Validate required fields
                    $required_fields = ['id', 'project', 'case_chronology', 'consequences', 'effective_date', 'end_date'];
                    foreach ($required_fields as $field) {
                        if (!isset($_POST[$field]) || empty($_POST[$field])) {
                            throw new Exception("Missing required field: $field");
                        }
                    }

                    $conn->beginTransaction();

                    // Get the data
                    $id = $_POST['id'];
                    $project = $_POST['project'];
                    $case_chronology = $_POST['case_chronology'];
                    $consequences = $_POST['consequences'];
                    $effective_date = $_POST['effective_date'];
                    $end_date = $_POST['end_date'];
                    $existing_doc = $_POST['existing_doc'] ?? null;

                    // Handle file upload if new document is provided
                    $document_path = $existing_doc;
                    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = dirname(__DIR__) . '/uploads/ccs_docs/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        // Delete old file if exists
                        if ($existing_doc && file_exists(dirname(__DIR__) . '/' . $existing_doc)) {
                            unlink(dirname(__DIR__) . '/' . $existing_doc);
                        }

                        $file_extension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid('doc_') . '.' . $file_extension;
                        $document_path = 'uploads/ccs_docs/' . $new_filename;

                        if (!move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $new_filename)) {
                            throw new Exception("Error uploading file");
                        }
                    }

                    // Update the record
                    $sql = "UPDATE ccs_rules SET 
                            case_chronology = :case_chronology,
                            consequences = :consequences,
                            effective_date = :effective_date,
                            end_date = :end_date,
                            supporting_doc_url = :doc
                            WHERE id = :id";
                    
                    $stmt = $conn->prepare($sql);
                    $result = $stmt->execute([
                        ':id' => $id,
                        ':case_chronology' => $case_chronology,
                        ':consequences' => $consequences,
                        ':effective_date' => $effective_date,
                        ':end_date' => $end_date,
                        ':doc' => $document_path
                    ]);

                    if (!$result) {
                        throw new Exception("Failed to update record");
                    }

                    $conn->commit();

                    // Clear buffer before sending response
                    ob_clean();
                    
                    // Send success response
                    echo json_encode([
                        'success' => true,
                        'message' => 'CCS Rule updated successfully'
                    ]);

                } catch (Exception $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    
                    // Clear buffer before sending error response
                    ob_clean();
                    
                    error_log("Update error: " . $e->getMessage());
                    
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                
                // End output buffering and exit
                ob_end_flush();
                exit;
                break;

            case 'delete':
                error_log("Delete request received: " . print_r($_POST, true));
                
                // Validate required fields
                if (!isset($_POST['id']) || empty($_POST['id'])) {
                    error_log("Missing ID parameter");
                    throw new Exception("Missing rule ID");
                }
                if (!isset($_POST['project']) || empty($_POST['project'])) {
                    error_log("Missing project parameter");
                    throw new Exception("Missing project parameter");
                }

                try {
                    // Begin transaction
                    $conn->beginTransaction();
                    error_log("Starting delete transaction for ID: {$_POST['id']}, Project: {$_POST['project']}");
                    
                    // First check if rule exists
                    $checkStmt = $conn->prepare("SELECT * FROM ccs_rules WHERE id = ? AND project = ?");
                    $checkStmt->execute([$_POST['id'], $_POST['project']]);
                    $rule = $checkStmt->fetch();

                    if (!$rule) {
                        throw new Exception("Rule not found");
                    }

                    // Delete supporting document if exists
                    if (!empty($rule['supporting_doc_url'])) {
                        $docPath = dirname(__DIR__) . '/' . $rule['supporting_doc_url'];
                        if (file_exists($docPath)) {
                            unlink($docPath);
                        }
                    }

                    // Delete the rule
                    $deleteStmt = $conn->prepare("DELETE FROM ccs_rules WHERE id = ? AND project = ?");
                    $deleteStmt->execute([$_POST['id'], $_POST['project']]);

                    // Commit transaction
                    $conn->commit();

                    // Make sure there's no whitespace or output before this
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rule deleted successfully'
                    ]);
                    exit;
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollBack();
                    
                    // Make sure there's no whitespace or output before this
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => "Error deleting rule: " . $e->getMessage()
                    ]);
                    exit;
                }
                break;

            case 'update_status':
                try {
                    // Clear any existing output
                    ob_clean();
                    header('Content-Type: application/json');
                    
                    $conn->beginTransaction();

                    $select = $conn->prepare("
                        SELECT id 
                        FROM ccs_rules 
                        WHERE end_date < CURRENT_DATE 
                        AND status = 'active'
                    ");
                    $select->execute();
                    $rules = $select->fetchAll(PDO::FETCH_COLUMN);

                    $update = $conn->prepare("
                        UPDATE ccs_rules 
                        SET status = 'expired' 
                        WHERE id = ?
                    ");

                    foreach ($rules as $id) {
                        $update->execute([$id]);
                    }

                    $conn->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Statuses updated successfully'
                    ]);
                    exit;
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Status update error: " . $e->getMessage());
                    
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    exit;
                }
                break;

            default:
                throw new Exception("Invalid action");
        }
    }
} catch (Exception $e) {
    // Clear any output before sending error response
    ob_clean();
    
    if (isAjaxRequest()) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit;
}

// Helper function to check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Helper function to get rule by ID
function getRuleById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM ccs_rules WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add this new function
function updateExpiredStatuses($conn) {
    try {
        // Set timezone to GMT+7
        date_default_timezone_set('Asia/Jakarta');
        
        // Get current date in Asia/Jakarta timezone
        $today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $currentDate = $today->format('Y-m-d');
        
        $stmt = $conn->prepare("
            UPDATE ccs_rules 
            SET status = 'expired' 
            WHERE end_date < :currentDate 
            AND status = 'active'
        ");
        
        $stmt->execute([':currentDate' => $currentDate]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating statuses: " . $e->getMessage());
        return false;
    }
}
?>
