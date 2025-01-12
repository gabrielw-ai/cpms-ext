<?php
session_start();
require_once 'conn.php';
require_once 'c_uac.php';

header('Content-Type: application/json');

try {
    $uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);
    $isLimitedAccess = in_array($uac->userPrivilege, [1, 2]) && $uac->userPrivilege !== 6;

    $tableName = $_GET['table'] ?? '';
    $viewType = $_GET['view'] ?? 'weekly';

    if (empty($tableName)) {
        throw new Exception('Table name is required');
    }

    // Apply project filter based on privilege
    $filter = $uac->getProjectFilter();
    $sql = "SELECT * FROM `$tableName`" . $filter;
    
    $stmt = $conn->prepare($sql);
    if ($isLimitedAccess) {
        if ($uac->userPrivilege === 1) {
            $stmt->execute([$_SESSION['user_nik'], $_SESSION['user_nik']]);
        } else {
            $stmt->execute([$_SESSION['user_nik']]);
        }
    } else {
        $stmt->execute();
    }

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rowData = [];
        
        // Add action buttons for non-limited access users
        if (!$isLimitedAccess) {
            $actions = '<div class="btn-group">' .
                      '<button type="button" class="btn btn-sm btn-primary edit-kpi" data-id="' . $row['id'] . '">' .
                      '<i class="fas fa-edit"></i></button>' .
                      '<button type="button" class="btn btn-sm btn-danger delete-kpi" data-id="' . $row['id'] . '">' .
                      '<i class="fas fa-trash"></i></button>' .
                      '</div>';
            $rowData[] = $actions;
        }

        // Add basic columns
        $rowData[] = $row['queue'];
        $rowData[] = $row['kpi_metrics'];
        $rowData[] = $row['target'];

        // Add period data
        if ($viewType === 'monthly') {
            $months = ['january', 'february', 'march', 'april', 'may', 'june', 
                      'july', 'august', 'september', 'october', 'november', 'december'];
            foreach ($months as $month) {
                $rowData[] = $row[$month] ?? '-';
            }
        } else {
            for ($week = 1; $week <= 52; $week++) {
                $weekField = 'week_' . str_pad($week, 2, '0', STR_PAD_LEFT);
                $rowData[] = $row[$weekField] ?? '-';
            }
        }

        $data[] = $rowData;
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 