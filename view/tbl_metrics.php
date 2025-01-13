<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Add KPI";

// Include config instead of helpers
require_once dirname(__DIR__) . '/config.php';

// Include the database connection with function redeclaration protection
$controller_path = realpath(dirname(__DIR__) . '/controller/conn.php');
if (!file_exists($controller_path)) {
    die("Cannot find database connection file at: " . $controller_path);
}

// Include only if closeConnection isn't already defined
if (!function_exists('closeConnection')) {
    global $conn;
    include $controller_path;
} else {
    // If function exists, just make sure we have access to $conn
    global $conn;
}

// Verify connection is established with more detailed error reporting
if (!isset($conn)) {
    $error_message = "Database connection failed in tbl_metrics.php\n";
    $error_message .= "Path used: " . $controller_path . "\n";
    $error_message .= "Current file: " . __FILE__ . "\n";
    $error_message .= "Variables available: " . implode(', ', array_keys(get_defined_vars()));
    
    error_log($error_message);
    die("Database connection failed - check error log for details");
}

// Test the connection explicitly
try {
    $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS);
} catch (PDOException $e) {
    error_log("Database connection test failed: " . $e->getMessage());
    die("Database connection test failed");
}

// Add required CSS and JS in the correct order
$additional_css = '
<link rel="stylesheet" href="' . getAssetUrl('plugins/select2/css/select2.min.css') . '">
<link rel="stylesheet" href="' . getAssetUrl('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . getAssetUrl('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
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
        border: none;
    }

    /* Ensure alerts are always on top */
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

    /* Ensure alert close button is visible */
    .alert .close {
        color: inherit;
        opacity: 0.8;
    }

    .alert .close:hover {
        opacity: 1;
    }

    .modal-body {
        position: relative;
        min-height: 100px;
    }

    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    body.modal-open {
        overflow: auto !important;
    }
    
    .modal-backdrop {
        opacity: 0.5;
    }
    
    .modal-backdrop.fade.show {
        opacity: 0.5;
    }
    
    .modal.fade.show {
        background-color: rgba(0, 0, 0, 0.5);
    }

    /* Remove select arrow and make styling consistent */
    select.form-control {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: none !important;
        padding-right: 12px !important; /* Same padding as other inputs */
    }

    /* Remove default arrow in IE */
    select.form-control::-ms-expand {
        display: none;
    }

    /* Make select2 match other form controls */
    .select2-container--bootstrap4 .select2-selection {
        height: calc(2.25rem + 2px) !important;
        padding: .375rem .75rem !important;
        font-size: 1rem !important;
        line-height: 1.5 !important;
        border: 1px solid #ced4da !important;
        border-radius: .25rem !important;
    }

    /* Remove select2 dropdown arrow */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        display: none !important;
    }

    /* Fix select2 positioning and spacing */
    .select2-container {
        width: 100% !important;
        margin: 0;
    }

    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 8px 12px !important;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        padding: 0 !important;
        line-height: 1.5 !important;
        color: #495057;
    }

    /* Remove extra spacing */
    .select2-container--bootstrap4 {
        margin: 0 !important;
    }

    /* Ensure placeholder text aligns with other inputs */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
        line-height: 1.5;
    }

    .btn:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }
</style>
';

$additional_js = '
<script src="' . getAssetUrl('plugins/jquery/jquery.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/jquery-ui/jquery-ui.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/bootstrap/js/bootstrap.bundle.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/select2/js/select2.full.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/datatables/jquery.dataTables.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') . '"></script>
';

// Define allowed roles and check permissions
require_once dirname(__DIR__) . '/controller/c_uac.php';
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

// Allow access for all privileges except level 1
if ($uac->userPrivilege === 1) {
    $_SESSION['error'] = "Access Denied. You don't have permission to access this page.";
    header('Location: ' . Router::url('dashboard'));
    exit;
}

// Get user's project if privilege level is 3 or 4
$userProject = null;
if ($uac->userPrivilege === 3 || $uac->userPrivilege === 4) {
    try {
        $userProject = $uac->getUserProject($conn, $_SESSION['user_nik']);
    } catch (PDOException $e) {
        error_log("Error getting user project: " . $e->getMessage());
    }
}

// Start capturing content
ob_start();
?>

<div class="row">
    <div class="col-12">
        <!-- Create New KPI Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create New KPI</h3>
            </div>
            <div class="card-body">
                <!-- Form -->
                <form action="../controller/c_tbl_metrics.php" method="POST">
                    <div class="form-group">
                        <label>Project</label>
                        <select class="form-control select2" name="project" required>
                            <option value="">Project</option>
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
                                    echo "<option value='" . htmlspecialchars($row['project_name']) . "'>" . 
                                         htmlspecialchars($row['project_name']) . "</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Error loading projects</option>";
                                error_log("Error loading projects: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Queue</label>
                        <input type="text" class="form-control" name="queue" required>
                    </div>
                    <div class="form-group">
                        <label>KPI Metrics</label>
                        <input type="text" class="form-control" name="kpi_metrics" required>
                    </div>
                    <div class="form-group">
                        <label>Target</label>
                        <input type="number" class="form-control" name="target" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Target Type</label>
                        <select class="form-control" name="target_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create KPI</button>
                </form>
            </div>
        </div>

        <!-- KPI Summary Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">KPI Summary</h3>
                <div class="card-tools">
                    <button type="button" id="exportKPISummary" class="btn btn-sm btn-success mr-1" disabled>
                        <i class="fas fa-download mr-1"></i> Export
                    </button>
                    <button type="button" id="importKPISummary" class="btn btn-sm btn-warning mr-1" 
                            data-toggle="modal" data-target="#importSummaryModal" disabled>
                        <i class="fas fa-upload mr-1"></i> Import
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Select Project</label>
                    <select class="form-control select2" id="summaryProject">
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

                <div class="table-responsive">
                    <table id="kpiSummaryTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Queue</th>
                                <th>KPI Metrics</th>
                                <th>Target</th>
                                <th>Target Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit KPI Modal -->
<div class="modal fade" id="editKPIModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit KPI</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editKPIForm">
                <input type="hidden" id="editKPIId" name="id">
                <input type="hidden" id="original_queue" name="original_queue">
                <input type="hidden" id="original_kpi_metrics" name="original_kpi_metrics">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Queue</label>
                        <input type="text" class="form-control" id="editQueue" name="queue" required>
                    </div>
                    <div class="form-group">
                        <label>KPI Metrics</label>
                        <input type="text" class="form-control" id="editKPIMetrics" name="kpi_metrics" required>
                    </div>
                    <div class="form-group">
                        <label>Target</label>
                        <input type="number" class="form-control" id="editTarget" name="target" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Target Type</label>
                        <select class="form-control" id="editTargetType" name="target_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importSummaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import KPI Data</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <input type="hidden" name="table_name" id="importTableName">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Excel File</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" accept=".xlsx,.xls" required>
                            <label class="custom-file-label">Choose file</label>
                        </div>
                        <small class="form-text text-muted mt-2">
                            Download the template first to ensure correct format.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="downloadTemplate">
                        <i class="fas fa-download"></i> Download Template
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Initially disable export and import buttons
    $('#exportKPISummary').prop('disabled', true);
    $('#importKPISummary').prop('disabled', true);

    // Initialize DataTable with simpler configuration first
    var kpiTable = $('#kpiSummaryTable').DataTable({
        processing: true,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        columns: [
            { data: 'queue' },
            { data: 'kpi_metrics' },
            { data: 'target' },
            { data: 'target_type' },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return '<button class="btn btn-sm btn-info edit-kpi" ' +
                           'data-id="' + row.id + '" ' +
                           'data-queue="' + row.queue + '" ' +
                           'data-kpi-metrics="' + row.kpi_metrics + '" ' +
                           'data-target="' + row.target + '" ' +
                           'data-target-type="' + row.target_type + '">' +
                           '<i class="fas fa-edit"></i></button>';
                }
            }
        ],
        order: [[0, 'asc']]
    });

    // Export button handler
    $('#exportKPISummary').on('click', function() {
        var selectedProject = $('#summaryProject').val();
        if (!selectedProject) {
            showNotification('Please select a project first', 'error');
            return;
        }
        
        window.location.href = '<?php echo Router::url("kpi/summary/export"); ?>?project=' + encodeURIComponent(selectedProject);
    });

    // Handle project selection
    $('#summaryProject').on('change', function() {
        const selectedProject = $(this).val();
        
        // Enable/disable buttons based on project selection
        $('#exportKPISummary').prop('disabled', !selectedProject);
        $('#importKPISummary').prop('disabled', !selectedProject);
        
        if (!selectedProject) {
            kpiTable.clear().draw();
            return;
        }

        // Load KPI data with error handling
        $.ajax({
            url: '<?php echo Router::url("kpi/summary/get"); ?>',
            type: 'GET',
            data: { 
                project: selectedProject,
                nik: '<?php echo $_SESSION['user_nik']; ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    showNotification(response.error, 'error');
                    return;
                }
                if (response.success && response.kpis) {
                    kpiTable.clear().rows.add(response.kpis).draw();
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 404) {
                    showNotification('Error: Resource not found. Please check if the controller exists.', 'error');
                } else {
                    showNotification('Error loading KPI data: ' + error, 'error');
                }
                console.error('Error:', error);
            }
        });
    });

    // Form submission handler with error handling
    $('form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo Router::url("controller/c_tbl_metrics.php"); ?>',
            type: 'POST',
            data: $(this).serialize() + '&nik=<?php echo $_SESSION['user_nik']; ?>',
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    showNotification(response.error, 'error');
                    return;
                }
                
                // Clear form
                $('form')[0].reset();
                $('.select2').val('').trigger('change');
                
                // Show success message
                showNotification('KPI added successfully', 'success');
                
                // Refresh KPI table if it exists and a project is selected
                var selectedProject = $('#summaryProject').val();
                if (selectedProject) {
                    $('#summaryProject').trigger('change');
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 404) {
                    showNotification('Error: Resource not found. Please check if the controller exists.', 'error');
                } else {
                    showNotification('Error adding KPI: ' + error, 'error');
                }
            }
        });
    });

    // Edit KPI handler
    $('#kpiSummaryTable').on('click', '.edit-kpi', function() {
        var button = $(this);
        var id = button.data('id');
        var queue = button.data('queue');
        var kpiMetrics = button.data('kpi-metrics');
        var target = button.data('target');
        var targetType = button.data('target-type');

        $('#editKPIId').val(id);
        $('#editQueue').val(queue);
        $('#editKPIMetrics').val(kpiMetrics);
        $('#editTarget').val(target);
        $('#editTargetType').val(targetType);
        $('#original_queue').val(queue);
        $('#original_kpi_metrics').val(kpiMetrics);

        $('#editKPIModal').modal('show');
    });

    // Edit form submission
    $('#editKPIForm').on('submit', function(e) {
        e.preventDefault();
        var selectedProject = $('#summaryProject').val();
        
        if (!selectedProject) {
            showNotification('Please select a project first', 'error');
            return;
        }

        // Get the actual project name from the select option text
        var projectName = $('#summaryProject option:selected').text();
        
        var formData = $(this).serializeArray();
        formData.push(
            {name: 'project', value: projectName},
            {name: 'table_name', value: selectedProject},
            {name: 'action', value: 'update'},
            {name: 'nik', value: '<?php echo $_SESSION['user_nik']; ?>'}
        );

        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        // Close modal before AJAX request
        $('#editKPIModal').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');

        // Add slight delay before making the AJAX request
        setTimeout(function() {
            $.ajax({
                url: '<?php echo Router::url("controller/c_tbl_metrics.php"); ?>',
                type: 'POST',
                data: formData,
                success: function(response) {
                    try {
                        var result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.error) {
                            showNotification(result.error, 'error');
                        } else {
                            showNotification('KPI edited successfully', 'success');
                            // Refresh data after notification
                            setTimeout(function() {
                                $('#summaryProject').trigger('change');
                            }, 500);
                        }
                    } catch (e) {
                        showNotification('Error processing response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Error updating KPI: ' + error, 'error');
                },
                complete: function() {
                    // Reset button state
                    submitBtn.prop('disabled', false).html('Update KPI');
                }
            });
        }, 300);
    });

    // Import modal handling
    $('#importSummaryModal').on('show.bs.modal', function() {
        var selectedProject = $('#summaryProject').val();
        if (!selectedProject) {
            showNotification('Please select a project first', 'error');
            return false;
        }
        $('#importTableName').val(selectedProject);
    });

    // Download template handler
    $('#downloadTemplate').on('click', function() {
        var selectedProject = $('#summaryProject').val();
        if (!selectedProject) {
            showNotification('Please select a project first', 'error');
            return;
        }
        window.location.href = '<?php echo Router::url("controller/c_export_kpi_summary.php"); ?>?project=' + encodeURIComponent(selectedProject) + '&template=1';
    });

    // Import form submission
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        var selectedProject = $('#summaryProject').val();
        if (!selectedProject) {
            showNotification('Please select a project first', 'error');
            return;
        }
        
        // Add project name from the select option text
        var projectName = $('#summaryProject option:selected').text();
        
        formData.append('project', projectName);
        formData.append('table_name', selectedProject);
        
        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');

        // Close modal before AJAX request
        $('#importSummaryModal').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');

        // Add slight delay before making the AJAX request
        setTimeout(function() {
            $.ajax({
                url: '<?php echo Router::url("kpi/summary/import"); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showNotification('KPI data imported successfully', 'success');
                        // Refresh the table after notification
                        setTimeout(function() {
                            $('#summaryProject').trigger('change');
                        }, 500);
                    } else {
                        showNotification(response.error || 'Import failed', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Error importing data: ' + error, 'error');
                },
                complete: function() {
                    // Reset button state
                    submitBtn.prop('disabled', false).html('Import');
                }
            });
        }, 300);
    });

    // File input change handler
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });
});

// Update showNotification function
function showNotification(message, type = 'success') {
    // Remove any existing notifications and modal artifacts
    $('.floating-alert').remove();
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
    
    // Create the notification element
    const alert = $('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show floating-alert">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        message +
        '</div>');

    // Add to body
    $('body').append(alert);

    // Auto dismiss after 3 seconds
    setTimeout(function() {
        alert.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<?php
// Store the buffered content
$content = ob_get_clean();
?>
