<?php
// Replace the simple session_start() with a check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Project Namelist";
ob_start();

// Include routing and database connection
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add CSS for notifications
$additional_css = '
<style>
    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
        max-width: 350px;
        animation: slideIn 0.5s ease-in-out;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

    .alert {
        margin-bottom: 1rem;
        border: none;
        border-radius: 4px;
        color: #fff;
    }

    .alert-success {
        background-color: #28a745;
    }

    .alert-danger {
        background-color: #dc3545;
    }

    .alert-info {
        background-color: #17a2b8;
    }

    .alert .close {
        color: #fff;
        opacity: 0.8;
    }

    .alert .close:hover {
        opacity: 1;
    }
</style>';

// Add DataTables CSS to additional_css
$additional_css .= '
<!-- DataTables -->
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') . '">';

// Add this to include DataTables JavaScript files
$additional_js = '
<!-- jQuery -->
<script src="' . Router::url('adminlte/plugins/jquery/jquery.min.js') . '"></script>
<!-- Bootstrap -->
<script src="' . Router::url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') . '"></script>
<!-- DataTables -->
<script src="' . Router::url('adminlte/plugins/datatables/jquery.dataTables.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') . '"></script>
<!-- Base URL -->
<script>var baseUrl = "' . Router::url('') . '";</script>
<!-- Custom JS -->
<script src="' . Router::url('public/dist/js/project_namelist.js') . '"></script>
';

// Handle session messages
if (isset($_SESSION['success_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . addslashes($_SESSION['success_message']) . "', 'success');
        });
    </script>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . addslashes($_SESSION['error_message']) . "', 'danger');
        });
    </script>";
    unset($_SESSION['error_message']);
}
?>

<!-- Add notification handling -->
<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show floating-alert">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
    <script>
        // Auto dismiss alert after 3 seconds
        setTimeout(function() {
            $('.floating-alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    </script>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Project List</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addProjectModal">
                Add New Project
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="projectTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Main Project</th>
                    <th>Project Name</th>
                    <th>Unit Name</th>
                    <th>Job Code</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM project_namelist ORDER BY id");
                    $no = 1; // Initialize counter
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td class='text-center'>" . $no++ . "</td>"; // Add number here
                        echo "<td>" . htmlspecialchars($row['main_project']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['project_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['unit_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['job_code']) . "</td>";
                        echo "<td class='text-center'>
                                <button class='btn btn-sm btn-info' onclick='editProject(" . $row['id'] . ")'>Edit</button>
                                <button class='btn btn-sm btn-danger' onclick='deleteProject(" . $row['id'] . ")'>Delete</button>
                              </td>";
                        echo "</tr>";
                    }
                } catch (PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Project</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addProjectForm" method="POST" action="<?php echo Router::url('project/add'); ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="main_project">Main Project</label>
                        <input type="text" class="form-control" id="main_project" name="main_project" required>
                    </div>
                    <div class="form-group">
                        <label for="project_name">Project Name</label>
                        <input type="text" class="form-control" id="project_name" name="project_name" required>
                    </div>
                    <div class="form-group">
                        <label for="unit_name">Unit Name</label>
                        <input type="text" class="form-control" id="unit_name" name="unit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="job_code">Job Code</label>
                        <input type="text" class="form-control" id="job_code" name="job_code" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Project</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editProjectForm">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="hidden" name="action" value="edit">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_main_project">Main Project</label>
                        <input type="text" class="form-control" id="edit_main_project" name="main_project" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_project_name">Project Name</label>
                        <input type="text" class="form-control" id="edit_project_name" name="project_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_unit_name">Unit Name</label>
                        <input type="text" class="form-control" id="edit_unit_name" name="unit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_job_code">Job Code</label>
                        <input type="text" class="form-control" id="edit_job_code" name="job_code" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

?>
