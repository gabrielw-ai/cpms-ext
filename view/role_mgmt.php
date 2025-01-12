<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Role Managements";
ob_start();

// Include routing and database connection
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add required CSS for notifications
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
</style>
';

// Function to get all roles
function getAllRoles($conn) {
    $stmt = $conn->query("SELECT * FROM role_mgmt ORDER BY role");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check for success/error messages in URL and show notification
if (isset($_GET['success']) || isset($_GET['error'])) {
    $message = '';
    $type = 'success';
    
    if (isset($_GET['success'])) {
        switch ($_GET['success']) {
            case 'added': $message = 'Role added successfully'; break;
            case 'updated': $message = 'Role updated successfully'; break;
            case 'deleted': $message = 'Role deleted successfully'; break;
        }
    } else {
        // Handle specific error messages
        $error = urldecode($_GET['error']);
        if (strpos($error, 'Duplicate entry') !== false) {
            $message = 'This role already exists. Please use a different name.';
        } else if (strpos($error, 'Integrity constraint violation') !== false) {
            $message = 'This role cannot be modified as it is being used by the system.';
        } else {
            $message = 'An error occurred: ' . $error;
        }
        $type = 'error';
    }
    
    // Remove success/error from URL using JavaScript
    echo "<script>
        window.history.replaceState({}, document.title, window.location.pathname);
        document.addEventListener('DOMContentLoaded', function() {
            showNotification(" . json_encode($message) . ", '$type');
        });
    </script>";
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Role Management</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRoleModal">
                <i class="fas fa-plus mr-2"></i>Add Role
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php
        // Remove this entire block of success/error messages since we're using floating notifications
        /*
        if (isset($_GET['success'])) {
            $message = '';
            switch ($_GET['success']) {
                case 'added': $message = 'Role added successfully'; break;
                case 'updated': $message = 'Role updated successfully'; break;
                case 'deleted': $message = 'Role deleted successfully'; break;
            }
            echo "<div class='alert alert-success'>{$message}</div>";
        }
        if (isset($_GET['error'])) {
            echo "<div class='alert alert-danger'>{$_GET['error']}</div>";
        }
        */
        ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Actions</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $roles = getAllRoles($conn);
                    foreach ($roles as $role) {
                        echo "<tr>";
                        echo "<td class='text-nowrap'>
                                <button type='button' class='btn btn-sm btn-info' onclick='editRole(this)'
                                        data-id='" . htmlspecialchars($role['id']) . "'
                                        data-role='" . htmlspecialchars($role['role']) . "'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button type='button' class='btn btn-sm btn-danger' onclick='deleteRole(" . htmlspecialchars($role['id']) . ")'>
                                    <i class='fas fa-trash'></i>
                                </button>
                              </td>";
                        echo "<td>" . htmlspecialchars($role['role']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo Router::url('role/manage'); ?>" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Role Name</label>
                        <input type="text" class="form-control" name="role" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo Router::url('role/manage'); ?>" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Role Name</label>
                        <input type="text" class="form-control" name="role" id="edit_role" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Role Form -->
<form id="deleteForm" action="<?php echo Router::url('role/manage'); ?>" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
// Add showNotification function
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    $('.floating-alert').remove();
    
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

function editRole(button) {
    const data = button.dataset;
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_role').value = data.role;
    $('#editRoleModal').modal('show');
}

function deleteRole(id) {
    if (confirm('Are you sure you want to delete this role?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Initialize DataTable
$(document).ready(function() {
    $('.table').DataTable({
        "responsive": true,
        "order": [[1, "asc"]], // Sort by role name by default
        "pageLength": 10
    });
});
</script>

<?php
$content = ob_get_clean();
?>
