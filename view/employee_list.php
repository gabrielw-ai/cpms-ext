<?php
// Add UAC requirement at the top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
require_once dirname(__DIR__) . '/controller/c_uac.php';

global $conn;
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

// Check if user has access to this page
if (!$uac->hasAccess('employee_list.php')) {
    header('Location: ' . Router::url('unauthorized'));
    exit;
}

// Add this function definition
function getAllRoles($conn) {
    try {
        $stmt = $conn->query("SELECT role FROM role_mgmt WHERE role != 'Super_User' ORDER BY role");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$page_title = "Employee List";
ob_start();


// Add DataTables CSS to additional_css variable
$additional_css = '
<!-- DataTables -->
<link rel="stylesheet" href="adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

<style>
    .card-tools {
        float: right;
    }
    
    .table thead th {
        vertical-align: middle;
        text-align: center;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .select-all-checkbox, .employee-select {
        width: 16px !important;
        height: 16px !important;
        cursor: pointer;
        vertical-align: middle;
        margin: 0;
        padding: 0;
        position: relative;
        top: 0;
    }
    
    .table td:first-child,
    .table th:first-child {
        text-align: center;
        vertical-align: middle;
        width: 40px;
        padding: 8px;
    }
    
    .input-group-text {
        border-right: 0;
    }
    
    .input-group .form-control {
        border-left: 0;
    }
    
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1rem;
    }
    
    .bulk-delete-container {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }
    
    .custom-file-label::after {
        content: "Browse";
    }
    
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }
    
    th.no-sort {
        background-image: none !important;
        padding-right: 8px !important;
    }
    
    /* Add this specific style for the checkbox column */
    #employeeTable thead th:first-child::before,
    #employeeTable thead th:first-child::after {
        display: none !important;
    }

    /* Add these styles to fix table responsiveness */
    .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Ensure table takes full width */
    .table {
        width: 100% !important;
        margin-bottom: 0;
    }

    /* Adjust content wrapper padding */
    .content-wrapper {
        transition: margin-left .3s ease-in-out;
        margin-left: 250px;  /* Default with sidebar open */
    }

    /* Adjust when sidebar is collapsed */
    body.sidebar-collapse .content-wrapper {
        margin-left: 4.6rem;
    }

    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0;
        }
    }

    th.no-sort::before,
    th.no-sort::after {
        display: none !important;
    }

    th.dt-body-center {
        text-align: center;
    }

    .btn-group .btn {
        padding: 6px 12px;
        line-height: 1.2;
        margin: 0 2px;
    }

    .btn-group .btn i {
        font-size: 16px;
    }

    .actions-column {
        white-space: nowrap;
        width: 120px;
        text-align: center;
        padding: 8px !important;
    }

    .actions-column .btn-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-sm {
        height: 32px;
        min-width: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .table td {
        vertical-align: middle !important;
    }

    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
        max-width: 350px;
        animation: slideIn 0.5s ease-in-out;
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
</style>
';

// Add DataTables and AdminLTE JS to additional_js variable
$additional_js = '
<!-- DataTables & Plugins -->
<script src="adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
';
?>

<!-- Main content -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Employee List</h3>
                <?php if ($uac->canAdd()): ?>
                <div class="card-tools">
                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display: none;">
                        <i class="fas fa-trash mr-2"></i>Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                    <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#addEmployeeModal">
                        <i class="fas fa-plus mr-2"></i>Add Employee
                    </button>
                    <a href="<?php echo Router::url('employee/export'); ?>" class="btn btn-success ml-2">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </a>
                    <button type="button" class="btn btn-info ml-2" data-toggle="modal" data-target="#importModal">
                        <i class="fas fa-file-import mr-2"></i>Import Excel
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error']) || isset($_SESSION['success'])): ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show floating-alert">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show floating-alert">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Add auto-dismiss script -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Auto dismiss alerts after 3 seconds
                            const alerts = document.querySelectorAll('.floating-alert');
                            alerts.forEach(function(alert) {
                                setTimeout(function() {
                                    $(alert).fadeOut('slow', function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                            });
                        });
                    </script>
                <?php endif; ?>

                <!-- Search Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search NIK, Name, or Email">
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table id="employeeTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <?php if ($uac->canDelete()): ?>
                                <th width="40px">
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="selectAll" class="select-all-checkbox" onclick="toggleAllCheckboxes()">
                                    </div>
                                </th>
                                <?php endif; ?>
                                <th>nik</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Project</th>
                                <th>Join Date</th>
                                <th>Tenure</th>
                                <?php if ($uac->canEdit() || $uac->canDelete()): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            try {
                                $sql = "SELECT 
                                        nik, 
                                        employee_name, 
                                        employee_email, 
                                        role, 
                                        project,
                                        join_date,
                                        DATE_FORMAT(join_date, '%d-%m-%Y') as formatted_join_date,
                                        TIMESTAMPDIFF(MONTH, join_date, CURRENT_DATE()) as months_diff,
                                        DATEDIFF(CURRENT_DATE(), join_date) as days_diff
                                        FROM employee_active 
                                        WHERE role != 'Super_User'";
                                
                                // Add project filter based on user privilege
                                $sql .= $uac->getEmployeeListAccess($conn, $_SESSION['user_nik']);
                                $sql .= " ORDER BY employee_name";
                                
                                $stmt = $conn->query($sql);
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // Calculate tenure
                                    $months = $row['months_diff'];
                                    $days = $row['days_diff'];
                                    $years = floor($months / 12);
                                    $remaining_months = $months % 12;
                                    
                                    // Format tenure string
                                    if ($months < 1) {
                                        // Show days if less than a month
                                        $tenure = $days . " days";
                                    } else if ($years < 1) {
                                        // Show only months if less than a year
                                        $tenure = $months . " months";
                                    } else {
                                        // Show years and months
                                        $tenure = $years . " years";
                                        if ($remaining_months > 0) {
                                            $tenure .= " " . $remaining_months . " months";
                                        }
                                    }

                                    echo "<tr>";
                                    if ($uac->canDelete()) {
                                        echo "<td><div class='checkbox-wrapper'><input type='checkbox' class='employee-select' value='" . htmlspecialchars($row['nik']) . "' onchange='toggleAllCheckboxes()'></div></td>";
                                    }
                                    echo "<td>" . htmlspecialchars($row['nik']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['employee_email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['project']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['formatted_join_date']) . "</td>";
                                    echo "<td>" . htmlspecialchars($tenure) . "</td>";
                                    
                                    if ($uac->canEdit() || $uac->canDelete()) {
                                        echo "<td class='text-center'>";
                                        echo "<div class='btn-group'>";
                                        
                                        // Get role privilege level
                                        $roleStmt = $conn->prepare("
                                            SELECT privileges 
                                            FROM role_mgmt 
                                            WHERE role = ?
                                        ");
                                        $roleStmt->execute([$row['role']]);
                                        $rolePrivilege = $roleStmt->fetchColumn();
                                        
                                        // For privilege 3 and 4, only show edit/delete for roles with privilege <= 3
                                        $canModifyRole = $uac->userPrivilege === 6 || 
                                                       (($uac->userPrivilege === 3 || $uac->userPrivilege === 4) && $rolePrivilege <= 3);
                                        
                                        if ($uac->canEdit() && $canModifyRole) {
                                            echo "<button type='button' class='btn btn-sm btn-primary' onclick='editEmployee(this)' 
                                                    data-nik='" . htmlspecialchars($row['nik']) . "'
                                                    data-name='" . htmlspecialchars($row['employee_name']) . "'
                                                    data-email='" . htmlspecialchars($row['employee_email']) . "'
                                                    data-role='" . htmlspecialchars($row['role']) . "'
                                                    data-project='" . htmlspecialchars($row['project']) . "'
                                                    data-join-date='" . htmlspecialchars($row['join_date']) . "'>
                                                    <i class='fas fa-edit'></i>
                                                </button>";
                                        }
                                        if ($uac->canDelete() && $canModifyRole) {
                                            echo "<button type='button' class='btn btn-sm btn-danger ml-1' onclick='deleteEmployee(\"" . htmlspecialchars($row['nik']) . "\")'>
                                                    <i class='fas fa-trash'></i>
                                                </button>";
                                        }
                                        echo "</div>";
                                        echo "</td>";
                                    }
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='8'>Error: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- REQUIRED SCRIPTS -->
<script src="adminlte/plugins/jquery/jquery.min.js"></script>
<script src="adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="adminlte/dist/js/adminlte.min.js"></script>
<script src="adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AdminLTE Sidebar
    $('[data-widget="pushmenu"]').on('click', function() {
        $('body').toggleClass('sidebar-collapse');
        if ($(window).width() <= 768) {
            $('body').toggleClass('sidebar-open');
        }
    });

    // Initialize DataTable
    let table = $('#employeeTable').DataTable({
        "responsive": true,
        "pageLength": 20,
        "lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "All"]],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 bulk-delete-container">><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "searching": true,
        "search": {
            "smart": true,
            "caseInsensitive": true
        },
        "columnDefs": [
            {
                "targets": [0],
                "orderable": false,
                "searchable": false,
                "width": "40px",
                "className": 'dt-body-center no-sort'
            }
        ],
        "autoWidth": false,
        "scrollX": true,
        "order": [[1, 'asc']]
    });

    // Move bulk delete button
    $('.bulk-delete-container').append($('#bulkDeleteBtn'));

    // Custom search functionality
    $('#searchInput').on('keyup', function() {
        let searchValue = $(this).val();
        
        // Use global search instead of column-specific search
        table.search(searchValue).draw();
    });

    // Add this to handle table redraw on sidebar toggle
    $('[data-widget="pushmenu"]').on('click', function() {
        setTimeout(function() {
            table.columns.adjust().draw();
        }, 300);
    });
});

// Select All functionality
function toggleAllCheckboxes() {
    const mainCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.employee-select');
    
    // If triggered by clicking individual checkboxes, update the select all checkbox
    if (!event.target.matches('#selectAll')) {
        mainCheckbox.checked = [...checkboxes].every(checkbox => checkbox.checked);
        mainCheckbox.indeterminate = [...checkboxes].some(checkbox => checkbox.checked) && !mainCheckbox.checked;
    } else {
        // If triggered by select all checkbox, update all individual checkboxes
        checkboxes.forEach(checkbox => {
            checkbox.checked = mainCheckbox.checked;
        });
    }
    
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkedCount = document.querySelectorAll('.employee-select:checked').length;
    document.getElementById('selectedCount').textContent = checkedCount;
    document.getElementById('bulkDeleteBtn').style.display = checkedCount > 0 ? 'block' : 'none';
}

// Bulk Delete action
document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
    const selectedNIKs = Array.from(document.querySelectorAll('.employee-select:checked')).map(cb => cb.value);
    
    if (selectedNIKs.length > 0) {
        // Get roles for selected NIKs
        const selectedRows = selectedNIKs.map(nik => {
            const row = document.querySelector(`input[value="${nik}"]`).closest('tr');
            return {
                nik: nik,
                role: row.children[4].textContent.trim() // Role is in 5th column
            };
        });

        // Check if any Super_User is selected
        const hasSuperUser = selectedRows.some(row => row.role === 'Super_User');
        
        if (hasSuperUser) {
            alert('Cannot delete Super_User accounts');
            return;
        }

        if (confirm('Delete ' + selectedNIKs.length + ' selected employees?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo Router::url('employee/delete'); ?>';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'bulk_delete';

            const niksInput = document.createElement('input');
            niksInput.type = 'hidden';
            niksInput.name = 'niks';
            niksInput.value = JSON.stringify(selectedNIKs);

            form.appendChild(actionInput);
            form.appendChild(niksInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
});

// File input handler for import
const fileInput = document.querySelector('.custom-file-input');
if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });
}

// Add these functions to your existing JavaScript
function editEmployee(btn) {
    const data = btn.dataset;
    
    // Fill the edit modal with data
    document.getElementById('edit_original_nik').value = data.nik;
    document.getElementById('edit_nik').value = data.nik;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_role').value = data.role;
    document.getElementById('edit_project').value = data.project;
    document.getElementById('edit_join_date').value = data.joinDate;
    
    // Show the modal
    $('#editEmployeeModal').modal('show');
}

function deleteEmployee(nik) {
    if (confirm('Are you sure you want to delete this employee?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo Router::url('employee/delete'); ?>';
        
        const inputs = {
            'action': 'delete',
            'nik': nik
        };
        
        Object.entries(inputs).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addEmployeeForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>nik</label>
                        <input type="text" class="form-control" name="nik" required>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <?php
                            try {
                                // For privilege 3 and 4, show all roles (they can add any role)
                                if ($uac->userPrivilege === 3 || $uac->userPrivilege === 4) {
                                    $stmt = $conn->prepare("
                                        SELECT DISTINCT role 
                                        FROM role_mgmt 
                                        WHERE role != 'Super_User'
                                        ORDER BY role
                                    ");
                                    $stmt->execute();
                                } else {
                                    // Existing role query for other privilege levels
                                    $stmt = $conn->prepare("SELECT role FROM role_mgmt WHERE role != 'Super_User' ORDER BY role");
                                    $stmt->execute();
                                }
                                
                                while ($role = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . htmlspecialchars($role['role']) . "'>" . 
                                         htmlspecialchars($role['role']) . "</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Error loading roles</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Project</label>
                        <select class="form-control" name="project" required>
                            <option value="">Select Project</option>
                            <?php
                            try {
                                // Changed query to use project_namelist table
                                $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . htmlspecialchars($row['project_name']) . "'>" . 
                                         htmlspecialchars($row['project_name']) . "</option>";
                                }
                            } catch (PDOException $e) {
                                error_log("Error loading projects: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Join Date</label>
                        <input type="date" class="form-control" name="join_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="<?php echo Router::url('employee/edit'); ?>" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="original_nik" id="edit_original_nik">
                <div class="modal-body">
                    <div class="form-group">
                        <label>nik</label>
                        <input type="text" class="form-control" name="nik" id="edit_nik" required>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="role" id="edit_role" required>
                            <?php
                            $roles = getAllRoles($conn);
                            foreach ($roles as $role) {
                                echo "<option value='" . htmlspecialchars($role['role']) . "'>" . 
                                     htmlspecialchars($role['role']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Project</label>
                        <select class="form-control" name="project" id="edit_project" required>
                            <?php
                            $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($row['project_name']) . "'>" . 
                                     htmlspecialchars($row['project_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Join Date</label>
                        <input type="date" class="form-control" name="join_date" id="edit_join_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Employee Data</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="<?php echo Router::url('employee/import'); ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Excel File (.xlsx)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" accept=".xlsx" required>
                            <label class="custom-file-label">Choose file</label>
                        </div>
                        <small class="form-text text-muted">
                            Download the template <a href="<?php echo Router::url('employee/export'); ?>?template=1">here</a>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
//require_once '../main_navbar.php';
?>
