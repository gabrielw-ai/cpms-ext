<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for minimum privilege level 4
if (!isset($_SESSION['user_privilege']) || (int)$_SESSION['user_privilege'] < 4) {
    header('Location: ' . Router::url('dashboard'));
    exit;
}

$page_title = "Mass Reset Password";

// Add DataTables CSS
$additional_css = '
<!-- DataTables -->
<link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<!-- Select2 -->
<link rel="stylesheet" href="../adminlte/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<!-- SweetAlert2 -->
<link rel="stylesheet" href="../adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
<style>
    .table th, .table td {
        white-space: nowrap;
        vertical-align: middle;
    }

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

// Add DataTables JS
$additional_js = '
<!-- jQuery -->
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI -->
<script src="../adminlte/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables & Plugins -->
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<!-- Select2 -->
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
<!-- SweetAlert2 -->
<script src="../adminlte/plugins/sweetalert2/sweetalert2.min.js"></script>';

// Get database connection
require_once dirname(__DIR__) . '/controller/conn.php';

// Get user's project if privilege level is 4
$userProject = null;
if (isset($_SESSION['user_privilege']) && (int)$_SESSION['user_privilege'] === 4) {
    $stmt = $conn->prepare("SELECT project FROM employee_active WHERE nik = ?");
    $stmt->execute([$_SESSION['user_nik']]);
    $userProject = $stmt->fetchColumn();
}

// Get list of projects based on privilege
if (isset($_SESSION['user_privilege']) && (int)$_SESSION['user_privilege'] === 4 && $userProject) {
    $stmt = $conn->prepare("SELECT DISTINCT project FROM employee_active WHERE project = ? ORDER BY project");
    $stmt->execute([$userProject]);
} else {
    $stmt = $conn->prepare("SELECT DISTINCT project FROM employee_active WHERE project IS NOT NULL ORDER BY project");
    $stmt->execute();
}
$projects = $stmt->fetchAll(PDO::FETCH_COLUMN);

ob_start();
?>

<!-- Only the content part, no HTML structure -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Mass Reset Password</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="project">Select Project:</label>
            <select class="form-control select2" id="project" name="project">
                <option value="">Select a project</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= htmlspecialchars($project) ?>"><?= htmlspecialchars($project) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="userListCard" style="display: none;">
            <div class="table-responsive mt-4">
                <table id="userTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="50px"><input type="checkbox" id="selectAll"></th>
                            <th>NIK</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Project</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-primary" id="resetPasswordBtn" disabled>
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize Select2
    $(".select2").select2({
        theme: "bootstrap4"
    });

    // Initialize DataTable
    var table = $("#userTable").DataTable({
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        order: [[1, "asc"]], // Sort by NIK column by default
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                width: "50px",
                className: "text-center"
            },
            {
                targets: [1, 2, 3, 4],
                className: "align-middle"
            }
        ],
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search:",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });

    // Handle project selection
    $("#project").change(function() {
        var project = $(this).val();
        if (project) {
            // Show the user list card
            $("#userListCard").show();
            
            // Fetch users for the selected project
            $.ajax({
                url: <?php echo json_encode(Router::url('user/mass-reset/manage')); ?>,
                type: "GET",
                data: { project: project },
                success: function(response) {
                    table.clear();
                    response.data.forEach(function(user) {
                        table.row.add([
                            '<input type="checkbox" class="user-select" value="' + user.nik + '">',
                            user.nik,
                            user.employee_name,
                            user.employee_email,
                            user.project
                        ]);
                    });
                    table.draw();
                    updateResetButton();
                },
                error: function(xhr, status, error) {
                    showNotification("Failed to fetch users: " + error, "error");
                }
            });
        } else {
            $("#userListCard").hide();
            table.clear().draw();
            updateResetButton();
        }
    });

    // Handle select all checkbox
    $("#selectAll").change(function() {
        $(".user-select").prop("checked", $(this).prop("checked"));
        updateResetButton();
    });

    // Handle individual checkboxes
    $(document).on("change", ".user-select", function() {
        updateResetButton();
    });

    // Update reset button state
    function updateResetButton() {
        var checkedCount = $(".user-select:checked").length;
        $("#resetPasswordBtn").prop("disabled", checkedCount === 0);
    }

    // Function to show notifications
    function showNotification(message, type) {
        if (typeof type === "undefined") {
            type = "success";
        }
        
        // Remove any existing notifications
        $(".floating-alert").remove();
        
        // Create the notification element
        const alert = $('<div class="alert alert-' + (type === "success" ? "success" : "danger") + ' alert-dismissible fade show floating-alert">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            message +
            '</div>');

        // Add to body
        $("body").append(alert);

        // Auto dismiss after 3 seconds
        setTimeout(function() {
            alert.fadeOut("slow", function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Handle reset password button
    $("#resetPasswordBtn").click(function() {
        var selectedNiks = [];
        $(".user-select:checked").each(function() {
            selectedNiks.push($(this).val());
        });

        if (selectedNiks.length > 0) {
            if (confirm("Are you sure you want to reset the password for the selected users? The password will be set to CPMS2025!!")) {
                // Send reset request
                $.ajax({
                    url: <?php echo json_encode(Router::url('user/mass-reset/manage')); ?>,
                    type: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        niks: selectedNiks
                    }),
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.message, "success");
                            // Uncheck all checkboxes
                            $("#selectAll").prop("checked", false);
                            $(".user-select").prop("checked", false);
                            updateResetButton();
                        } else {
                            showNotification(response.message, "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotification("Failed to reset passwords: " + error, "error");
                    }
                });
            }
        }
    });
});
</script>';

<?php
$content = ob_get_clean();
?>
