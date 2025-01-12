<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "KPI Viewer";


// Check if user is logged in
if (!isset($_SESSION['user_nik'])) {
    header('Location: ' . Router::url('login'));
    exit;
}



// Update the UAC path
require_once dirname(__DIR__) . '/controller/c_uac.php';

// Include routing and database connection
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Initialize UAC
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

// Check if user has access to this page
if (!$uac->hasAccess('kpi_viewer.php')) {
    header('Location: ' . Router::url('unauthorized'));
    exit;
}

// Define access levels
$isLimitedAccess = in_array($uac->userPrivilege, [1, 2]); // Privilege levels 1 and 2 have limited access

// Get user's project if privilege level is 3 or 4
$userProject = null;
if ($uac->userPrivilege === 3 || $uac->userPrivilege === 4) {
    try {
        $stmt = $conn->prepare("SELECT project FROM employee_active WHERE nik = ?");
        $stmt->execute([$_SESSION['user_nik']]);
        $userProject = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting user project: " . $e->getMessage());
    }
}

// Additional CSS
$additional_css = '
<!-- Select2 -->
<link rel="stylesheet" href="../adminlte/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<!-- DataTables -->
<link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<!-- Toastr -->
<link rel="stylesheet" href="../adminlte/plugins/toastr/toastr.min.css">
<style>
    .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px);
        padding: .375rem .75rem;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
    }
    .table th, .table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    /* Add notification styles */
    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
        max-width: 350px;
        animation: slideIn 0.5s ease-in-out;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .alert {
        margin-bottom: 1rem;
        border: none;
        border-radius: 4px;
    }

    .alert-success {
        background-color: #28a745;
        color: #fff;
    }

    .alert-danger {
        background-color: #dc3545;
        color: #fff;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .alert .close {
        color: inherit;
        opacity: 0.8;
    }

    .alert .close:hover {
        opacity: 1;
    }
</style>';

// Additional JavaScript
$table = isset($_GET['table']) ? $_GET['table'] : '';
$view = isset($_GET['view']) ? $_GET['view'] : 'weekly';

$additional_js = <<<JAVASCRIPT
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>
<script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../adminlte/plugins/toastr/toastr.min.js"></script>
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
<script src="../adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
var currentTable = '{$table}';
var currentView = '{$view}';
</script>
<script src="../public/kpi_viewer.js"></script>
JAVASCRIPT;

// Start output buffering for main content
ob_start();
?>

<!-- Only the content part, no HTML structure -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">KPI Data Viewer</h3>
        <?php if (isset($_GET['table']) && !$isLimitedAccess): ?>
            <div class="card-tools">
                <!-- Export Button -->
                <a href="<?php echo Router::url('controller/c_export_kpi.php'); ?>?table=<?php echo urlencode($_GET['table']); ?>&view=<?php echo urlencode($_GET['view'] ?? 'weekly'); ?>" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> Export
                </a>
                <!-- Import Button -->
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#importModal">
                    <i class="fas fa-upload"></i> Import
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!$isLimitedAccess): ?>
            <form id="kpiFilterForm" method="GET">
                <div class="row mb-4">
                    <!-- Project Selection -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Select Project:</label>
                            <select class="form-control select2" name="table" onchange="this.form.submit()">
                                <option value="">Select Project</option>
                                <?php
                                try {
                                    if ($uac->userPrivilege === 3 || $uac->userPrivilege === 4) {
                                        // For privilege level 3 and 4, only show their assigned project
                                        $stmt = $conn->prepare("SELECT project_name FROM project_namelist 
                                                              WHERE project_name IN 
                                                              (SELECT project FROM employee_active WHERE nik = ?) 
                                                              ORDER BY project_name");
                                        $stmt->execute([$_SESSION['user_nik']]);
                                    } else {
                                        // For other privilege levels, show all projects
                                        $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                                    }
                                    
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $tableName = 'kpi_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $row['project_name']));
                                        $selected = (isset($_GET['table']) && strtolower($_GET['table']) === $tableName) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($tableName) . "' {$selected}>" . 
                                             htmlspecialchars($row['project_name']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value=''>Error loading projects</option>";
                                    error_log("Error loading projects: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Period Selection -->
                    <div class="col-md-6">
                    <?php if (isset($_GET['table'])): ?>
                            <div class="form-group">
                                <label>View Period:</label>
                                <select class="form-control select2" name="view" onchange="this.form.submit()">
                                    <option value="weekly" <?php echo (!isset($_GET['view']) || $_GET['view'] === 'weekly') ? 'selected' : ''; ?>>
                                        Weekly View
                                    </option>
                                    <option value="monthly" <?php echo (isset($_GET['view']) && $_GET['view'] === 'monthly') ? 'selected' : ''; ?>>
                                        Monthly View
                                    </option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <!-- Limited access users (including privilege level 2) -->
            <form method="GET">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($_GET['table'] ?? ''); ?>">
                
                <!-- Show project name but not selectable -->
                <div class="alert alert-info mb-3">
                    <i class="icon fas fa-info-circle"></i> 
                    Viewing KPI data for project: <?php echo htmlspecialchars($userProject); ?>
                </div>

                <!-- Allow period switching for privilege level 2 -->
                <?php if ($uac->userPrivilege === 2 && isset($_GET['table'])): ?>
                    <div class="form-group">
                        <label>View Period:</label>
                        <select class="form-control select2" name="view" onchange="this.form.submit()">
                            <option value="weekly" <?php echo (!isset($_GET['view']) || $_GET['view'] === 'weekly') ? 'selected' : ''; ?>>
                                Weekly View
                            </option>
                            <option value="monthly" <?php echo (isset($_GET['view']) && $_GET['view'] === 'monthly') ? 'selected' : ''; ?>>
                                Monthly View
                            </option>
                        </select>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <!-- Content Area -->
        <div class="mt-4">
            <?php if (!isset($_GET['table'])): ?>
                <div class="alert alert-info">
                    <i class="icon fas fa-info-circle"></i> Please select a project to view its KPI data.
                </div>
            <?php else: ?>
                <div id="kpiDataContainer">
                    <!-- KPI Data Table -->
                    <div class="table-responsive">
                        <table id="kpiTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <?php if (!$isLimitedAccess): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                    <th>Queue</th>
                                    <th>KPI Metrics</th>
                                    <th>Target</th>
                                    <?php
                                    if (isset($_GET['view']) && $_GET['view'] === 'monthly') {
                                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                                  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                        foreach ($months as $month) {
                                            echo "<th>{$month}</th>";
                                        }
                                    } else {
                                        for ($week = 1; $week <= 52; $week++) {
                                            echo "<th>Wk " . str_pad($week, 2, '0', STR_PAD_LEFT) . "</th>";
                                        }
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $tableName = $_GET['table'] ?? '';
                                    if ($tableName) {
                                        // Set table names based on view type - same as export logic
                                        if (isset($_GET['view']) && $_GET['view'] === 'monthly') {
                                            $kpiTable = $tableName . "_mon";  // Use _mon table for KPI definitions
                                            $valuesTable = $tableName . "_mon_values";
                                            $periodColumn = 'month';
                                            $periods = ['January', 'February', 'March', 'April', 'May', 'June', 
                                                       'July', 'August', 'September', 'October', 'November', 'December'];
                                            $periodCount = 12;
                                        } else {
                                            $kpiTable = $tableName;  // Use base table for KPI definitions
                                            $valuesTable = $tableName . "_values";
                                            $periodColumn = 'week';
                                            $periodCount = 52;
                                        }

                                        // Get data with JOIN - using same query as export
                                        $sql = "SELECT k.id, k.queue, k.kpi_metrics, k.target, k.target_type, 
                                                       v.{$periodColumn}, v.value
                                                FROM `$kpiTable` k
                                                LEFT JOIN `{$valuesTable}` v ON k.id = v.kpi_id
                                                ORDER BY k.queue, k.kpi_metrics, v.{$periodColumn}";
                                        
                                        error_log("KPI Query: " . $sql);
                                        
                                        $stmt = $conn->query($sql);
                                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        // Process data into rows - same logic as export
                                        $rowData = [];
                                        
                                        foreach ($data as $row) {
                                            $kpiKey = $row['queue'] . '|' . $row['kpi_metrics'];
                                            
                                            if (!isset($rowData[$kpiKey])) {
                                                $rowData[$kpiKey] = [
                                                    'id' => $row['id'],
                                                    'queue' => $row['queue'],
                                                    'kpi_metrics' => $row['kpi_metrics'],
                                                    'target' => $row['target'],
                                                    'target_type' => $row['target_type'],
                                                    'values' => array_fill(1, $periodCount, null)
                                                ];
                                            }
                                            
                                            // Store value using the correct period
                                            $period = $row[$periodColumn];
                                            if ($period !== null) {
                                                $rowData[$kpiKey]['values'][$period] = $row['value'];
                                            }
                                        }

                                        // Output the rows
                                        foreach ($rowData as $row) {
                                            echo "<tr>";
                                            if (!$isLimitedAccess) {
                                                echo "<td class='text-center'>";
                                                echo "<button type='button' class='btn btn-sm btn-primary edit-kpi mr-2' 
                                                    data-id='{$row['id']}'
                                                    data-queue='{$row['queue']}'
                                                    data-kpi_metrics='{$row['kpi_metrics']}'
                                                    data-kpi_target='{$row['target']}'
                                                    data-target_type='{$row['target_type']}'>";
                                                echo "<i class='fas fa-edit'></i></button>";
                                                echo "<button type='button' class='btn btn-sm btn-danger delete-kpi' 
                                                    data-id='{$row['id']}'
                                                    data-queue='{$row['queue']}'
                                                    data-kpi_metrics='{$row['kpi_metrics']}'>";
                                                echo "<i class='fas fa-trash'></i></button>";
                                                echo "</td>";
                                            }
                                            
                                            echo "<td>" . htmlspecialchars($row['queue']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['kpi_metrics']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['target']) . 
                                                 ($row['target_type'] === 'percentage' ? '%' : '') . "</td>";
                                            
                                            // Output period values
                                            foreach ($row['values'] as $value) {
                                                echo "<td>" . htmlspecialchars($value ?? '-') . 
                                                     ($value !== null && $row['target_type'] === 'percentage' ? '%' : '') . "</td>";
                                            }
                                            
                                            echo "</tr>";
                                        }
                                    }
                                } catch (PDOException $e) {
                                    error_log("Error in KPI viewer: " . $e->getMessage());
                                    error_log("Stack trace: " . $e->getTraceAsString());
                                    echo "<tr><td colspan='5' class='text-center text-danger'>Error loading KPI data: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Only show modals for full access users -->
<?php if (!$isLimitedAccess): ?>
    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import KPI Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="../controller/c_import_kpi.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars(strtolower($_GET['table'] ?? '')); ?>">
                        <input type="hidden" name="view_type" value="<?php echo htmlspecialchars($_GET['view'] ?? 'weekly'); ?>">
                        
                        <div class="form-group">
                            <label for="file">Select Excel File</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="file" name="file" accept=".xlsx,.xls" required>
                                <label class="custom-file-label" for="file">Choose file</label>
                            </div>
                            <small class="form-text text-muted mt-2">
                                Download the template first to ensure correct format.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="../controller/c_export_kpi.php?table=<?php echo urlencode($_GET['table'] ?? ''); ?>&template=1" 
                           class="btn btn-info">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="importKPI" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit KPI</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editKPIForm" onsubmit="return false">
                    <input type="hidden" id="kpi_id" name="kpi_id">
                    <input type="hidden" id="original_queue" name="original_queue">
                    <input type="hidden" id="original_kpi_metrics" name="original_kpi_metrics">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="queue">Queue</label>
                            <input type="text" class="form-control" id="queue" name="queue" required>
                        </div>
                        <div class="form-group">
                            <label for="kpi_metrics">KPI Metrics</label>
                            <input type="text" class="form-control" id="kpi_metrics" name="kpi_metrics" required>
                        </div>
                        <div class="form-group">
                            <label for="target">Target</label>
                            <input type="number" class="form-control" id="target" name="target" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="target_type">Target Type</label>
                            <select class="form-control" id="target_type" name="target_type" required>
                                <option value="percentage">Percentage</option>
                                <option value="number">Number</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Get the buffered content
$content = ob_get_clean();

// Clear session messages
unset($_SESSION['success'], $_SESSION['error']);
?>