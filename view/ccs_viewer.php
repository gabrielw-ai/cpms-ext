<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/controller/c_ccs_rules.php';

// Update expired statuses when page loads
updateExpiredStatuses($conn);

$page_title = "CCS Rules Viewer";
ob_start();

require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
require_once dirname(__DIR__) . '/controller/c_uac.php';

global $conn;
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

$isLimitedAccess = $uac->userPrivilege === 1;
$isProjectManager = in_array($uac->userPrivilege, [3, 4]);

// Add back DataTables CSS
$additional_css = '
<!-- DataTables -->
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('public/dist/css/ccs_viewer.css') . '">';

// Add back DataTables JS
$additional_js = '
<!-- DataTables & Plugins -->
<script src="' . Router::url('adminlte/plugins/datatables/jquery.dataTables.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js') . '"></script>
<script>
var baseUrl = "' . Router::url('') . '";
var isLimitedAccess = ' . ($isLimitedAccess ? 'true' : 'false') . ';
var isProjectManager = ' . ($isProjectManager ? 'true' : 'false') . ';
var userNik = "' . $_SESSION['user_nik'] . '";
var userPrivilege = ' . $_SESSION['user_privilege'] . ';
</script>
<script src="' . Router::url('public/dist/js/ccs_viewer.js') . '"></script>';

// Add Select2 CSS to your additional_css
$additional_css .= '
<!-- Select2 -->
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/select2/css/select2.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') . '">';

// Add Select2 JS to your additional_js
$additional_js .= '
<!-- Select2 -->
<script src="' . Router::url('adminlte/plugins/select2/js/select2.full.min.js') . '"></script>';
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">CCS Rules Viewer</h3>
                        <?php if ($uac->canExport()): ?>
                        <div class="card-tools">
                            <a href="<?php echo Router::url('controller/c_export_ccsrules.php'); ?>" 
                               class="btn btn-success btn-sm">
                                <i class="fas fa-download"></i> Export
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (!$isLimitedAccess): ?>
                        <!-- Filter section -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <!-- Project Filter -->
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label class="mb-2">Project</label>
                                                    <?php if ($uac->userPrivilege === 2 || $isProjectManager): ?>
                                                        <?php
                                                        // Get user's project
                                                        $stmt = $conn->prepare("SELECT project FROM employee_active WHERE NIK = ?");
                                                        $stmt->execute([$_SESSION['user_nik']]);
                                                        $userProject = $stmt->fetchColumn();
                                                        ?>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProject); ?>" readonly>
                                                        <input type="hidden" id="projectFilter" name="projectFilter" value="<?php echo htmlspecialchars($userProject); ?>">
                                                    <?php else: ?>
                                                    <select class="form-control filter-select" id="projectFilter">
                                                        <option value="">All Projects</option>
                                                        <?php
                                                        $projects = $conn->query("SELECT DISTINCT project FROM ccs_rules ORDER BY project")->fetchAll(PDO::FETCH_COLUMN);
                                                        foreach ($projects as $project) {
                                                            echo "<option value='" . htmlspecialchars($project) . "'>" . 
                                                                 htmlspecialchars($project) . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Role Filter -->
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label class="mb-2">Role</label>
                                                    <select class="form-control filter-select" id="roleFilter">
                                                        <?php
                                                        // Different role queries based on privilege level
                                                        if ($isProjectManager) {
                                                            // For privilege 3 and 4, show roles with privileges 1, 2, and 3
                                                            $stmt = $conn->prepare("
                                                                SELECT DISTINCT ea.role 
                                                                FROM employee_active ea
                                                                JOIN role_mgmt rm ON ea.role = rm.role
                                                                WHERE rm.privileges IN (1, 2, 3)
                                                                AND ea.project = (
                                                                    SELECT project 
                                                                    FROM employee_active 
                                                                    WHERE NIK = ?
                                                                )
                                                                ORDER BY ea.role
                                                            ");
                                                            $stmt->execute([$_SESSION['user_nik']]);
                                                            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                                            
                                                            echo "<option value=''>All Roles</option>";
                                                            foreach ($roles as $role) {
                                                                echo "<option value='" . htmlspecialchars($role) . "'>" . 
                                                                     htmlspecialchars($role) . "</option>";
                                                            }
                                                        } elseif ($uac->userPrivilege === 2) {
                                                            // For privilege 2, only show roles they can manage based on role_mgmt
                                                            $stmt = $conn->prepare("
                                                                SELECT DISTINCT ea.role 
                                                                FROM employee_active ea
                                                                JOIN role_mgmt rm ON ea.role = rm.role
                                                                WHERE (
                                                                    -- Show only their own role if they are priv 2
                                                                    (ea.NIK = ? AND EXISTS (
                                                                        SELECT 1 FROM role_mgmt 
                                                                        WHERE role = ea.role AND privileges = 2
                                                                    ))
                                                                    OR 
                                                                    -- Show roles that have privilege 1 in role_mgmt
                                                                    (ea.project = (
                                                                        SELECT project 
                                                                        FROM employee_active 
                                                                        WHERE NIK = ?
                                                                    ) 
                                                                    AND EXISTS (
                                                                        SELECT 1 FROM role_mgmt 
                                                                        WHERE role = ea.role AND privileges = 1
                                                                    ))
                                                                )
                                                                ORDER BY ea.role
                                                            ");
                                                            $stmt->execute([$_SESSION['user_nik'], $_SESSION['user_nik']]);
                                                            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                                            // For privilege 2, force selection of first role
                                                            if (!empty($roles)) {
                                                                echo "<option value='" . htmlspecialchars($roles[0]) . "' selected>" . 
                                                                     htmlspecialchars($roles[0]) . "</option>";
                                                                // Add remaining roles if any
                                                                for ($i = 1; $i < count($roles); $i++) {
                                                                    echo "<option value='" . htmlspecialchars($roles[$i]) . "'>" . 
                                                                         htmlspecialchars($roles[$i]) . "</option>";
                                                                }
                                                            }
                                                        } elseif ($uac->userPrivilege === 6) {
                                                            // Only admin can see "All Roles" option
                                                            echo "<option value=''>All Roles</option>";
                                                            // For admin, show all roles
                                                            $roles = $conn->query("
                                                                SELECT DISTINCT role 
                                                                FROM ccs_rules 
                                                                ORDER BY role
                                                            ")->fetchAll(PDO::FETCH_COLUMN);
                                                            foreach ($roles as $role) {
                                                                echo "<option value='" . htmlspecialchars($role) . "'>" . 
                                                                     htmlspecialchars($role) . "</option>";
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Status Filter -->
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label class="mb-2">Status</label>
                                                    <select class="form-control filter-select" id="statusFilter">
                                                        <option value="">All Status</option>
                                                        <option value="active">Active</option>
                                                        <option value="expired">Expired</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Clear Filters Button -->
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="button" id="clearFilters" class="btn btn-secondary">Clear Filters</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Table -->
                        <table id="rulesTable" class="table table-bordered table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>NIK</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Tenure</th>
                                    <th>Case Chronology</th>
                                    <th>Consequences</th>
                                    <th>Effective Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Doc</th>
                                    <?php if ($uac->userPrivilege === 6): ?>
                                    <th>Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Edit Rule Modal -->
<?php if ($uac->userPrivilege === 6): ?>
<div class="modal fade" id="editRuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Rule</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editRuleForm" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" id="edit_project" name="project">
                <input type="hidden" id="edit_existing_doc" name="existing_doc">
                <div class="modal-body">
                    <!-- Display only fields -->
                    <div class="form-group">
                        <label>NIK</label>
                        <input type="text" class="form-control" id="edit_nik" readonly>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" id="edit_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" class="form-control" id="edit_role" readonly>
                    </div>

                    <!-- Editable fields -->
                    <div class="form-group">
                        <label>Case Chronology</label>
                        <textarea class="form-control" id="edit_case_chronology" name="case_chronology" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Consequences</label>
                        <select class="form-control" id="edit_consequences" name="consequences" required>
                            <option value="">Select Consequences</option>
                            <option value="Written Reminder 1">Written Reminder 1</option>
                            <option value="Written Reminder 2">Written Reminder 2</option>
                            <option value="Written Reminder 3">Written Reminder 3</option>
                            <option value="Warning Letter 1">Warning Letter 1</option>
                            <option value="Warning Letter 2">Warning Letter 2</option>
                            <option value="Warning Letter 3">Warning Letter 3</option>
                            <option value="First & Last Warning Letter">First & Last Warning Letter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Effective Date</label>
                        <input type="date" class="form-control" id="edit_effective_date" name="effective_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                    </div>
                    <!-- Add document field -->
                    <div class="form-group">
                        <label>Supporting Document</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="edit_doc" name="document">
                                <label class="custom-file-label" for="edit_doc">Choose file</label>
                            </div>
                        </div>
                        <div id="current_doc_container" class="mt-2">
                            <small class="text-muted">Current document: <span id="current_doc_name">None</span></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
?>
