<?php
// Start output buffering
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/controller/conn.php';
require_once dirname(__DIR__) . '/controller/c_uac.php';

// Clear any previous output
ob_clean();

try {
    $uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);
    $isLimitedAccess = $uac->userPrivilege === 1;

    // Log incoming request
    error_log("Received request: " . print_r($_POST, true));

    $baseQuery = "
        SELECT cr.*, ea.employee_name 
        FROM ccs_rules cr
        LEFT JOIN employee_active ea ON cr.nik = ea.nik
    ";

    $where = [];
    $params = [];

    // Get access filter based on user privilege
    $accessFilter = $uac->getRuleAccessFilter($conn, $_SESSION['user_nik']);
    if ($accessFilter) {
        // Replace any unqualified 'role' references with 'cr.role'
        $accessFilter = str_replace(' role ', ' cr.role ', $accessFilter);
        $accessFilter = str_replace('(role ', '(cr.role ', $accessFilter);
        $where[] = trim($accessFilter, " AND ");
    }

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

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get total records
    $stmt = $conn->query("SELECT COUNT(*) FROM ccs_rules");
    $recordsTotal = $stmt->fetchColumn();

    // Get filtered records
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ccs_rules cr $whereClause");
    $stmt->execute($params);
    $recordsFiltered = $stmt->fetchColumn();

    // Get paginated and filtered data
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;

    // Fix: Use LIMIT with direct integers instead of parameters
    $query = "$baseQuery $whereClause ORDER BY cr.effective_date DESC LIMIT $start, $length";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params); // Remove pagination parameters from params array
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the query and results
    error_log("Query: $query");
    error_log("Parameters: " . print_r($params, true));
    error_log("Result count: " . count($data));

    $response = [
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log("Error in get_filtered_ccs_rules.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
    exit;
}

// End output buffering and send response
ob_end_flush();
exit; 