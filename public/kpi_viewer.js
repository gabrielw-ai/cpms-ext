$(function() {
    // Handle default view for selected project
    var selectedOption = $("select[name='table'] option:selected");
    if (selectedOption.length && selectedOption.data('default-url')) {
        window.location.href = selectedOption.data('default-url');
    }

    // Initialize Select2 Elements
    $(".select2").select2({
        theme: "bootstrap4"
    });

    // Initialize DataTable
    $("#kpiTable").DataTable({
        "responsive": false,
        "autoWidth": false,
        "scrollX": true,
        "scrollCollapse": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "asc"], [1, "asc"]],
        "columnDefs": [
            {
                "targets": "_all",
                "defaultContent": "-"
            }
        ]
    });

    // Initialize file input
    bsCustomFileInput.init();

    // Handle edit button clicks
    $(document).on('click', '.edit-kpi', function() {
        var button = $(this);
        
        $('#editModal #kpi_id').val(button.data('id'));
        $('#editModal #queue').val(button.data('queue'));
        $('#editModal #kpi_metrics').val(button.data('kpi_metrics'));
        $('#editModal #target').val(button.data('kpi_target'));
        $('#editModal #target_type').val(button.data('target_type'));
        
        // Store original values for reference
        $('#editModal #original_queue').val(button.data('queue'));
        $('#editModal #original_kpi_metrics').val(button.data('kpi_metrics'));
        
        $('#editModal').modal('show');
    });

    // Handle edit form submission
    $('#editKPIForm').on('submit', function(e) {
        e.preventDefault();
        var formData = {
            table_name: currentTable,
            queue: $('#editModal #queue').val(),
            kpi_metrics: $('#editModal #kpi_metrics').val(),
            target: $('#editModal #target').val(),
            target_type: $('#editModal #target_type').val(),
            original_queue: $('#editModal #original_queue').val(),
            original_kpi_metrics: $('#editModal #original_kpi_metrics').val()
        };

        $.ajax({
            url: '../controller/c_viewer_update.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editModal').modal('hide');
                    // Use the custom notification function
                    showNotification(response.message || 'KPI updated successfully', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.message || 'Update failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error updating KPI: ' + error, 'error');
            }
        });
    });

    // Handle delete button clicks
    $(document).on('click', '.delete-kpi', function() {
        if (confirm("Are you sure you want to delete this KPI?")) {
            var button = $(this);
            button.prop('disabled', true);
            
            var formData = {
                table_name: currentTable,
                queue: button.data('queue'),
                kpi_metrics: button.data('kpi_metrics'),
                view_type: currentView
            };

            $.ajax({
                url: '../controller/c_viewer_del.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message || 'KPI deleted successfully', 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(response.message || 'Delete failed', 'error');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Error deleting KPI: ' + error, 'error');
                    button.prop('disabled', false);
                }
            });
        }
    });

    // Initialize Toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
});

// Function to show notifications
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    $('.floating-alert').remove();
    
    // Create the notification element
    const alert = $('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show floating-alert">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' mr-2"></i>' +
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
