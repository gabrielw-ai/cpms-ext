<?php
defined('ROUTING_INCLUDE') OR exit('Direct access is not allowed');

// Check for privilege level 6
if (!isset($_SESSION['user_privilege']) || $_SESSION['user_privilege'] != 6) {
    header("Location: " . Router::url('dashboard'));
    exit;
}

$page_title = "Role User Access Control";
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
</style>';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-shield mr-1"></i>
                    Manage Role Privileges
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Role</th>
                                <th>Privileges</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="roleTableBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Role Privileges</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="roleId">
                    <div class="form-group">
                        <label for="privileges">Privileges Level (1-6)</label>
                        <input type="number" class="form-control" id="privileges" min="1" max="6" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show notification function
function showNotification(message, type = 'success') {
    // Remove any existing notifications and modal backdrops
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

// Load roles data
function loadRoles() {
    fetch('<?php echo Router::url('role/uac/manage'); ?>')
        .then(response => response.json())
        .then(response => {
            const tableBody = document.getElementById('roleTableBody');
            tableBody.innerHTML = '';
            
            response.data.forEach(role => {
                tableBody.innerHTML += `
                    <tr>
                        <td>${role.id}</td>
                        <td>${role.role}</td>
                        <td>${role.privileges}</td>
                        <td>
                            <button class="btn btn-primary btn-sm edit-role" 
                                    data-id="${role.id}" 
                                    data-role="${role.role}"
                                    data-privileges="${role.privileges}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                `;
            });

            // Add edit button handlers
            document.querySelectorAll('.edit-role').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const role = this.dataset.role;
                    const privileges = this.dataset.privileges;

                    document.getElementById('roleId').value = id;
                    document.getElementById('privileges').value = privileges;
                    document.getElementById('editModalLabel').textContent = `Edit Privileges for ${role}`;
                    
                    $('#editModal').modal('show');
                });
            });
        })
        .catch(error => {
            console.error('Error loading roles:', error);
            showNotification('Error loading roles. Please try again.', 'error');
        });
}

// Save changes
document.getElementById('saveChanges').addEventListener('click', function() {
    const id = document.getElementById('roleId').value;
    const privileges = document.getElementById('privileges').value;

    fetch('<?php echo Router::url('role/uac/manage'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            privileges: privileges
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#editModal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
            loadRoles(); // Reload the table
            showNotification('Privileges updated successfully', 'success');
        } else {
            throw new Error(data.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Error updating privileges:', error);
        showNotification(error.message || 'Failed to update privileges', 'error');
    });
});

// Load roles when page loads
document.addEventListener('DOMContentLoaded', loadRoles);
</script>

<?php
$content = ob_get_clean();
?>
