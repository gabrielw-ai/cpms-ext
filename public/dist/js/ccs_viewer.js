$(document).ready(function() {
    // Define base columns (without action column)
    let columns = [
        { "data": "project" },
        { "data": "nik" },
        { "data": "employee_name" },
        { "data": "role" },
        { "data": "tenure" },
        { "data": "case_chronology" },
        { "data": "consequences" },
        { "data": "effective_date" },
        { "data": "end_date" },
        { 
            "data": "status",
            "render": function(data, type, row) {
                return data === 'active' ? 
                    '<span class="badge badge-success">Active</span>' : 
                    '<span class="badge badge-danger">Expired</span>';
            }
        },
        {
            "data": "supporting_doc_url",
            "render": function(data, type, row) {
                if (data) {
                    return '<a href="' + baseUrl + data + '" target="_blank" class="btn btn-default btn-sm">View</a>';
                }
                return '<span class="badge badge-secondary">No document</span>';
            }
        }
    ];

    // Only add action column for admin (privilege 6)
    if (userPrivilege === 6) {
        columns.push({
            "data": null,
            "orderable": false,
            "render": function(data, type, row) {
                return '<div class="btn-group">' +
                       '<button type="button" class="btn btn-primary btn-sm edit-rule" ' +
                       'data-id="' + row.id + '">' +
                       '<i class="fas fa-edit"></i> Edit</button>' +
                       '<button type="button" class="btn btn-danger btn-sm delete-rule" ' +
                       'data-id="' + row.id + '" ' +
                       'data-project="' + row.project + '">' +
                       '<i class="fas fa-trash"></i> Delete</button>' +
                       '</div>';
            }
        });
    }

    // Initialize DataTable
    const rulesTable = $('#rulesTable').DataTable({
        responsive: false,
        autoWidth: false,
        pageLength: 10,
        searching: false,  // Disable the search functionality
        dom: 'Bfrtip',    // Remove 'f' from dom to hide the search box
        buttons: [
            // ... your existing buttons
        ],
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "scrollCollapse": true,
        "fixedHeader": true,
        "ajax": {
            "url": baseUrl + "controller/get_filtered_ccs_rules.php",
            "type": "POST",
            "data": function(d) {
                return {
                    ...d,
                    projectFilter: $('#projectFilter').val(),
                    roleFilter: $('#roleFilter').val(),
                    statusFilter: $('#statusFilter').val()
                };
            }
        },
        "columns": columns,
        "order": [[7, "desc"]],
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });

    // Only bind event handlers if user is admin
    if (userPrivilege === 6) {
        // Edit button handler
        $('#rulesTable').on('click', '.edit-rule', function() {
            var tr = $(this).closest('tr');
            var row = rulesTable.row(tr).data();
            
            // Populate modal with data
            $('#edit_id').val(row.id);
            $('#edit_project').val(row.project);
            $('#edit_nik').val(row.nik);
            $('#edit_name').val(row.employee_name);
            $('#edit_role').val(row.role);
            $('#edit_case_chronology').val(row.case_chronology);
            $('#edit_consequences').val(row.consequences);
            $('#edit_effective_date').val(row.effective_date);
            $('#edit_end_date').val(row.end_date);
            $('#edit_existing_doc').val(row.supporting_doc_url);

            // Update current document display
            if (row.supporting_doc_url) {
                $('#current_doc_name').html(
                    `<a href="${baseUrl}${row.supporting_doc_url}" target="_blank">View current document</a>`
                );
            } else {
                $('#current_doc_name').text('None');
            }

            // Show the modal
            $('#editRuleModal').modal('show');
        });

        // Delete button handler
        $('#rulesTable').on('click', '.delete-rule', function() {
            var tr = $(this).closest('tr');
            var row = rulesTable.row(tr).data();
            
            if (confirm('Are you sure you want to delete this rule?')) {
                $.ajax({
                    url: baseUrl + 'controller/c_ccs_rules.php',
                    type: 'POST',
                    data: { 
                        action: 'delete',
                        id: $(this).data('id'),
                        project: $(this).data('project')
                    },
                    success: function(response) {
                        if (response.success) {
                            rulesTable.ajax.reload();
                            showNotification('Rule deleted successfully', 'success');
                        } else {
                            showNotification(response.message || 'Failed to delete rule', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', xhr.responseText);
                        showNotification('Error deleting rule: ' + error, 'error');
                    }
                });
            }
        });
    }

    // Auto-trigger project filter for privilege level 2
    if (userPrivilege === 2) {
        // Get the project value from hidden input
        const userProject = $('#projectFilter').val();
        // Get the first role value (which is pre-selected)
        const selectedRole = $('#roleFilter').val();
        if (userProject) {
            // Make sure role is always selected for privilege 2
            if (!selectedRole) {
                const firstRole = $('#roleFilter option:first').val();
                $('#roleFilter').val(firstRole);
            }
            // Trigger DataTable reload with the user's project
            rulesTable.ajax.reload();
        }
    }

    // Handle filter changes - modify to respect privilege level 2
    $('.filter-select').on('change', function() {
        const filterId = $(this).attr('id');
        if (userPrivilege === 2) {
            if (filterId === 'projectFilter') {
                // Don't allow project filter changes for privilege level 2
                return;
            }
            
            if (filterId === 'roleFilter') {
                // Don't allow empty role selection for privilege 2
                const selectedRole = $(this).val();
                if (!selectedRole) {
                    const firstRole = $('#roleFilter option:first').val();
                    $(this).val(firstRole);
                    showNotification('You do not have permission to view this role', 'error');
                    return;
                }
            }
        }
        rulesTable.ajax.reload();
    });

    // Clear filters - modify to respect privilege level 2
    $('#clearFilters').on('click', function() {
        if (userPrivilege === 2) {
            // Don't clear project filter and ensure role is selected
            $('#statusFilter').val('');
            const firstRole = $('#roleFilter option:first').val();
            $('#roleFilter').val(firstRole);
        } else {
            $('.filter-select').val('');
        }
        rulesTable.ajax.reload();
    });

    // Handle edit form submission
    $('#editRuleForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'edit');
        
        $.ajax({
            url: baseUrl + 'controller/c_ccs_rules.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editRuleModal').modal('hide');
                    rulesTable.ajax.reload();
                    showNotification(response.message || 'CCS Rule updated successfully', 'success');
                } else {
                    showNotification(response.message || 'Failed to update rule', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Raw response:', xhr.responseText);
                let errorMessage = 'Error updating rule';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    console.error('Parse error:', e);
                    errorMessage = error || 'Unknown error occurred';
                }
                showNotification(errorMessage, 'error');
            }
        });
    });

    // Handle file input change
    $('#edit_doc').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });

    // Add consequences change handler
    $('#edit_consequences').on('change', function() {
        updateEndDate();
    });

    $('#edit_effective_date').on('change', function() {
        updateEndDate();
    });

    // Add the styles when document loads
    addStyles();
});

// Function to update end date based on consequences
function updateEndDate() {
    const effectiveDate = $('#edit_effective_date').val();
    const consequences = $('#edit_consequences').val();
    
    if (effectiveDate && consequences) {
        const date = new Date(effectiveDate);
        
        if (consequences.toLowerCase().includes('warning letter')) {
            // Add 6 months for Warning Letters
            date.setMonth(date.getMonth() + 6);
        } else if (consequences.toLowerCase().includes('written reminder')) {
            // Add 1 year for Written Reminders
            date.setFullYear(date.getFullYear() + 1);
        }
        
        $('#edit_end_date').val(date.toISOString().split('T')[0]);
    }
}

// Function to show alerts
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

// Add this CSS to ccs_viewer.php
function addStyles() {
    const styles = `
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
        }

        .alert-success {
            background-color: #28a745;
            color: #fff;
        }

        .alert-danger {
            background-color: #dc3545;
            color: #fff;
        }
    </style>`;
    
    $('head').append(styles);
} 