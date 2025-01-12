$(document).ready(function() {
    // Initialize DataTable
    $('#projectTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[1, 'asc']]
    });

    // Handle form submission for adding project
    $('#addProjectForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#addProjectModal').modal('hide');
                showNotification('Project added successfully', 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            },
            error: function(xhr, status, error) {
                showNotification('Error adding project: ' + error, 'error');
            }
        });
    });

    // Handle form submission for editing project
    $('#editProjectForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = $(this).serializeArray();
        console.log('Form data:', formData); // Debug log
        
        $.ajax({
            url: baseUrl + 'controller/c_project_namelist.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response); // Debug log
                if (response.success) {
                    $('#editProjectModal').modal('hide');
                    showNotification('Project updated successfully', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.message || 'Failed to update project', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error response:', xhr.responseText);
                showNotification('Error updating project: ' + error, 'error');
            }
        });
    });

    // Format project name when typing in Add Modal
    $('#project_name').on('input', function() {
        let value = $(this).val();
        let words = value.split(/[\s_]+/);  // Split by space or underscore
        let formatted = words.map(word => word.toUpperCase()).join('_');
        $(this).val(formatted);
    });

    // Format project name when typing in Edit Modal
    $('#edit_project_name').on('input', function() {
        let value = $(this).val();
        let words = value.split(/[\s_]+/);  // Split by space or underscore
        let formatted = words.map(word => word.toUpperCase()).join('_');
        $(this).val(formatted);
    });
});

// Edit project function
function editProject(id) {
    $.ajax({
        url: baseUrl + 'controller/c_project_namelist.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#edit_id').val(response.data.id);
                $('#edit_main_project').val(response.data.main_project);
                $('#edit_project_name').val(response.data.project_name);
                $('#edit_unit_name').val(response.data.unit_name);
                $('#edit_job_code').val(response.data.job_code);
                $('#editProjectModal').modal('show');
            } else {
                showNotification('Error loading project data', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error response:', xhr.responseText);
            showNotification('Error loading project: ' + error, 'error');
        }
    });
}

// Delete project function
function deleteProject(id) {
    if (confirm('Are you sure you want to delete this project?')) {
        $.ajax({
            url: baseUrl + 'controller/c_project_namelist.php',
            type: 'POST',
            data: { 
                id: id,
                action: 'delete'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Project deleted successfully', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.message || 'Failed to delete project', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error response:', xhr.responseText);
                showNotification('Error deleting project: ' + error, 'error');
            }
        });
    }
}

// Notification helper function
function showNotification(message, type = 'success') {
    $('.floating-alert').remove();
    
    const alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show floating-alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ${message}
        </div>
    `);
    
    $('body').append(alert);
    setTimeout(() => alert.fadeOut('slow', function() { $(this).remove(); }), 3000);
} 