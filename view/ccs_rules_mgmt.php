<?php
$page_title = "CCS Rules Management";

// Include necessary files
require_once dirname(__DIR__) . '/controller/conn.php';
require_once dirname(__DIR__) . '/controller/c_ccs_rules.php';
require_once dirname(__DIR__) . '/controller/c_uac.php';
global $conn;

// Initialize UAC
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

// Check if user has access to this page
if (!$uac->hasAccess('ccs_rules_mgmt.php')) {
    header('Location: ' . Router::url('unauthorized'));
    exit;
}

$isProjectManager = in_array($uac->userPrivilege, [3, 4]);

// Edit mode check
$isEdit = false;
$ruleData = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $isEdit = true;
    $ruleData = getRuleById($conn, $_GET['id']);
    if (!$ruleData) {
        $_SESSION['error'] = "Rule not found";
        header('Location: ../view/ccs_viewer.php');
        exit;
    }
}

// Additional CSS
$additional_css = '
<!-- Select2 -->
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/select2/css/select2.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') . '">
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
    
    /* Form Group Styling */
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        font-weight: 500;
    }

    /* Custom File Input */
    .custom-file-label::after {
        content: "Browse";
    }

    /* Notification Styling */
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

    .alert-success {
        background-color: #28a745;
        color: #fff;
    }

    .alert-danger {
        background-color: #dc3545;
        color: #fff;
    }
</style>';

// Additional JavaScript
$additional_js = '
<script>var baseUrl = "' . Router::url('') . '";</script>
<script src="' . Router::url('adminlte/plugins/jquery/jquery.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/select2/js/select2.full.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js') . '"></script>
<script src="' . Router::url('public/dist/js/ccs_rules_mgmt.js') . '"></script>';

ob_start();
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">CCS Rules Management</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form id="ccsRulesForm" action="<?php echo Router::url('controller/c_ccs_rules.php'); ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Project Select -->
                                    <div class="form-group">
                                        <label>Project</label>
                                        <?php if ($isProjectManager): ?>
                                            <?php
                                            // Get user's project
                                            $stmt = $conn->prepare("SELECT project FROM employee_active WHERE NIK = ?");
                                            $stmt->execute([$_SESSION['user_nik']]);
                                            $userProject = $stmt->fetchColumn();
                                            ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProject); ?>" readonly>
                                            <input type="hidden" id="project" name="project" value="<?php echo htmlspecialchars($userProject); ?>">
                                        <?php else: ?>
                                        <select class="form-control select2" id="project" name="project">
                                            <option value="">Select Project</option>
                                            <?php
                                            try {
                                                $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    $projectName = strtolower($row['project_name']);
                                                    $tableName = 'kpi_' . preg_replace('/[^a-z0-9_]/', '_', $projectName);
                                                    
                                                    echo "<option value='" . htmlspecialchars($tableName) . "'>" . 
                                                         htmlspecialchars($row['project_name']) . "</option>";
                                                }
                                            } catch (PDOException $e) {
                                                echo "<option value=''>Error loading projects</option>";
                                            }
                                            ?>
                                        </select>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Initialize Select2 in JavaScript -->
                                    <script>
                                    $(document).ready(function() {
                                        // Initialize Select2
                                        $("#project").select2({
                                            theme: "bootstrap4",
                                            width: "100%",
                                            placeholder: "Select a project",
                                            allowClear: true
                                        });

                                        // Project change handler
                                        $("#project").on("change", function() {
                                            const selectedProject = $(this).val();
                                            console.log('Selected project:', selectedProject);
                                            
                                            if (selectedProject) {
                                                // Your existing code...
                                            }
                                        });
                                    });
                                    </script>

                                    <!-- Name -->
                                    <div class="form-group">
                                        <label>Name</label>
                                        <select class="form-control select2bs4" id="employee" name="employee" required>
                                            <option value="">-- Select Employee --</option>
                                            <?php if ($isProjectManager): ?>
                                                <?php
                                                try {
                                                    // For privilege level 3 and 4, only show employees from their project
                                                    $stmt = $conn->prepare("
                                                        SELECT ea.NIK, ea.employee_name, ea.role, ea.project, ea.join_date
                                                        FROM employee_active ea
                                                        JOIN role_mgmt rm ON ea.role = rm.role
                                                        WHERE ea.project = (
                                                            SELECT project 
                                                            FROM employee_active 
                                                            WHERE NIK = ?
                                                        )
                                                        AND rm.privileges <= 3
                                                        ORDER BY ea.employee_name
                                                    ");
                                                    $stmt->execute([$_SESSION['user_nik']]);
                                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                        echo "<option value='" . htmlspecialchars($row['NIK']) . "' 
                                                            data-name='" . htmlspecialchars($row['employee_name']) . "'
                                                            data-role='" . htmlspecialchars($row['role']) . "'
                                                            data-project='" . htmlspecialchars($row['project']) . "'
                                                            data-join-date='" . htmlspecialchars($row['join_date']) . "'>" . 
                                                            htmlspecialchars($row['employee_name']) . " (" . htmlspecialchars($row['NIK']) . ")</option>";
                                                    }
                                                } catch (PDOException $e) {
                                                    error_log("Error loading employees: " . $e->getMessage());
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </select>
                                        <input type="hidden" id="name" name="name">
                                        <input type="hidden" id="nik" name="nik">
                                    </div>

                                    <!-- Role -->
                                    <div class="form-group">
                                        <label for="role">Role</label>
                                        <input type="text" class="form-control" id="role" name="role" readonly>
                                    </div>

                                    <!-- Tenure -->
                                    <div class="form-group">
                                        <label for="tenure">Tenure</label>
                                        <input type="text" class="form-control" id="tenure" name="tenure" readonly>
                                    </div>

                                    <!-- Case Chronology -->
                                    <div class="form-group">
                                        <label for="case_chronology">Case Chronology</label>
                                        <textarea class="form-control" id="case_chronology" name="case_chronology" rows="3" required><?php 
                                            echo $isEdit ? htmlspecialchars($ruleData['case_chronology']) : ''; 
                                        ?></textarea>
                                    </div>

                                    <!-- CCS Rules -->
                                    <div class="form-group">
                                        <label for="ccs_rule">CCS Rule</label>
                                        <select class="form-control select2bs4" id="ccs_rule" name="ccs_rule" required>
                                            <option value="">Select CCS Rule</option>
                                            <option value="WR1">Written Reminder 1</option>
                                            <option value="WR2">Written Reminder 2</option>
                                            <option value="WR3">Written Reminder 3</option>
                                            <option value="WL1">Warning Letter 1</option>
                                            <option value="WL2">Warning Letter 2</option>
                                            <option value="WL3">Warning Letter 3</option>
                                            <option value="FLW">First & Last Warning Letter</option>
                                        </select>
                                    </div>

                                    <!-- Effective Date -->
                                    <div class="form-group">
                                        <label for="effective_date">Effective Date</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="effective_date" 
                                               name="effective_date" 
                                               max="<?php echo date('Y-m-d'); ?>" 
                                               required>
                                    </div>

                                    <!-- Supporting Document -->
                                    <div class="form-group">
                                        <label for="document">Supporting Document</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="document" name="document" accept=".pdf,.xlsx,.xls" required>
                                            <label class="custom-file-label" for="document">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">Accepted formats: PDF, Excel (.xlsx, .xls)</small>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($isEdit): ?>
<script>
    var ruleData = <?php echo json_encode($ruleData); ?>;
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();

if (!defined('ROUTING_INCLUDE')) {
    require_once dirname(__DIR__) . '/main_navbar.php';
}
?>
