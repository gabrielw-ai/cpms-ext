<?php
// Prevent any output before JSON response
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/controller/conn.php';
require_once dirname(__DIR__) . '/controller/c_uac.php';

// Initialize UAC
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);
$isLimitedAccess = $uac->userPrivilege === 1;

try {
    // Build the base query
    $baseQuery = "
        SELECT 
            cr.*,
            ea.employee_name
        FROM ccs_rules cr
        LEFT JOIN employee_active ea ON cr.nik = ea.nik
    ";

    // Initialize where clause and parameters
    $where = [];
    $params = [];

    // Add filters based on user privilege
    if ($isLimitedAccess) {
        $where[] = "cr.nik = ?";
        $params[] = $_SESSION['user_nik'];
    } else {
        // Add filters for non-limited access users
        if (!empty($_POST['projectFilter'])) {
            $where[] = "cr.project = ?";
            $params[] = $_POST['projectFilter'];
        }
        if (!empty($_POST['roleFilter'])) {
            $where[] = "cr.role = ?";
            $params[] = $_POST['roleFilter'];
        }
        if (!empty($_POST['statusFilter'])) {
            $where[] = "cr.status = ?";
            $params[] = $_POST['statusFilter'];
        }
    }

    // Combine where clauses
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get total count
    $countQuery = "SELECT COUNT(*) FROM ccs_rules cr $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $filteredRecords = $totalRecords;

    // Add ordering
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 7;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    $columns = [
        'cr.project', 'cr.nik', 'ea.employee_name', 'cr.role', 'cr.tenure', 
        'cr.case_chronology', 'cr.consequences', 'cr.effective_date', 
        'cr.end_date', 'cr.status', 'cr.supporting_doc_url'
    ];
    $orderBy = "ORDER BY " . $columns[$orderColumn] . " " . $orderDir;

    // Add pagination
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $limit = "LIMIT $start, $length";

    // Final query
    $query = "$baseQuery $whereClause $orderBy $limit";
    error_log("Final SQL Query: " . $query);
    error_log("Parameters: " . print_r($params, true));

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data for DataTables
    $formattedData = [];
    foreach ($data as $row) {
        $formattedData[] = [
            'id' => $row['id'],
            'project' => $row['project'],
            'nik' => $row['nik'],
            'employee_name' => $row['employee_name'],
            'role' => $row['role'],
            'tenure' => $row['tenure'],
            'case_chronology' => $row['case_chronology'],
            'consequences' => $row['consequences'],
            'effective_date' => $row['effective_date'],
            'end_date' => $row['end_date'],
            'status' => $row['status'],
            'doc' => $row['supporting_doc_url']
        ];
    }

    $response = [
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $formattedData
    ];

    error_log("Response data: " . print_r($response, true));
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    // Clear any output buffers
    ob_clean();
    
    error_log("Error in get_ccs_rules.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $error_response = [
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ];
    
    echo json_encode($error_response);
}

// Clear any remaining output and send only the JSON response
ob_end_flush(); 