// Define baseUrl globally
const baseUrl = document.querySelector('meta[name="base-url"]').content;

// Utility function to generate consistent table names
function generateTableName(projectName) {
    return 'kpi_' + projectName.toLowerCase().replace(/[^a-z0-9_]/g, '_');
}

$(document).ready(function() {
    // Check if user has privilege level 1
    const isLimitedAccess = document.body.dataset.userPrivilege === '1';

    // Hide filter section for limited access users
    if (isLimitedAccess) {
        $('.filter-section').hide();
    }

    // Initialize Select2 (only for non-limited users)
    if (!isLimitedAccess) {
        $(".select2").select2({
            theme: "bootstrap4",
            width: "100%",
            placeholder: "Select options"
        });
    }

    // Initialize DataTable and store the instance
    const stagingTable = $("#stagingTable").DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        scrollX: true,
        scrollCollapse: true,
        columns: [
            { data: 'nik' },
            { data: 'employee_name' },
            { data: 'kpi_metrics' },
            { data: 'queue' },
            { data: 'january', defaultContent: '-' },
            { data: 'february', defaultContent: '-' },
            { data: 'march', defaultContent: '-' },
            { data: 'april', defaultContent: '-' },
            { data: 'may', defaultContent: '-' },
            { data: 'june', defaultContent: '-' },
            { data: 'july', defaultContent: '-' },
            { data: 'august', defaultContent: '-' },
            { data: 'september', defaultContent: '-' },
            { data: 'october', defaultContent: '-' },
            { data: 'november', defaultContent: '-' },
            { data: 'december', defaultContent: '-' }
        ],
        language: {
            emptyTable: "No data available",
            loadingRecords: "Loading...",
            processing: "Processing...",
            zeroRecords: "No matching records found"
        }
    });

    // Project change handler
    $("#project").on("change", function() {
        const selectedProject = $(this).val();
        console.log('Selected project:', selectedProject);
        
        if (selectedProject) {
            // Ensure project name has kpi_ prefix and is lowercase
            let projectName = selectedProject.toLowerCase();
            if (!projectName.startsWith('kpi_')) {
                projectName = 'kpi_' + projectName;
            }
            
            // Fetch KPI Metrics
            const kpiUrl = baseUrl + 'project/kpi?project=' + encodeURIComponent(projectName);
            console.log('Fetching KPI metrics from:', kpiUrl);
            
            fetch(kpiUrl)
                .then(response => response.json())
                .then(data => {
                    const kpiSelect = $("#kpiMetrics");
                    kpiSelect.empty();
                    
                    if (data.success && data.metrics) {
                        data.metrics.forEach(metric => {
                            kpiSelect.append(
                                `<option value="${metric}">${metric}</option>`
                            );
                        });
                        kpiSelect.prop('disabled', false);
                    } else {
                        kpiSelect.append('<option value="">No metrics found</option>');
                    }
                    
                    kpiSelect.trigger('change');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading KPI metrics', 'error');
                });
        }
    });

    // Initialize metrics table
    $("#metricsTable").DataTable({
        responsive: true,
        autoWidth: false
    });

    // KPI Metrics change handler
    $("#kpiMetrics").on("change", function() {
        const selectedProject = $("#project").val();
        const selectedMetrics = $(this).val();
        console.log('Selected metrics:', selectedMetrics);
        
        if (selectedMetrics && selectedMetrics.length > 0) {
            // Ensure project name has kpi_ prefix and is lowercase
            let projectName = selectedProject.toLowerCase();
            if (!projectName.startsWith('kpi_')) {
                projectName = 'kpi_' + projectName;
            }
            
            // Fetch Queues
            const queueUrl = baseUrl + 'project/queues?project=' + encodeURIComponent(projectName) + 
                            '&kpi=' + encodeURIComponent(JSON.stringify(selectedMetrics));
            console.log('Fetching queues from:', queueUrl);
            
            fetch(queueUrl)
                .then(response => response.json())
                .then(data => {
                    const queueSelect = $("#queue");
                    queueSelect.empty();
                    
                    if (data.success && data.queues) {
                        data.queues.forEach(queue => {
                            queueSelect.append(
                                `<option value="${queue}">${queue}</option>`
                            );
                        });
                        queueSelect.prop('disabled', false);
                    } else {
                        queueSelect.append('<option value="">No queues found</option>');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading queues', 'error');
                });
        }
    });

    // Process button click handler
    $("#processKPI").on("click", function() {
        if (isLimitedAccess) return;
        
        // Get selected values
        var kpiMetrics = $('#kpiMetrics').val();
        var queue = $('#queue').val();
        var project = $("#project").val();
        
        // Validate project
        if (!project) {
            showNotification('You need to select a project first', 'error');
            return;
        }
        
        // Validate KPI Metrics
        if (!kpiMetrics || kpiMetrics.length === 0 || kpiMetrics[0] === '' || kpiMetrics[0] === 'Select options') {
            showNotification('You need to select KPI Metrics', 'error');
            return;
        }
        
        // Validate Queue
        if (!queue || queue.length === 0 || queue[0] === '' || queue[0] === 'Select options') {
            showNotification('You need to select Queue', 'error');
            return;
        }

        // Add debug logs
        console.log('Current project value:', $("#project").val());
        console.log('Current metrics:', $("#kpiMetrics").val());
        console.log('Current queues:', $("#queue").val());

        const selectedMetrics = $("#kpiMetrics").val();
        const selectedQueues = $("#queue").val();

        if (!project || !selectedMetrics || !selectedQueues) {
            showNotification('Please select all required fields', 'error');
            return;
        }

        // Show loading state
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');

        // Prepare the request data
        const requestData = {
            project: project,
            metrics: selectedMetrics,
            queues: selectedQueues
        };

        console.log('Request data:', requestData); // Debug log

        // Fetch data from server
        fetch(baseUrl + 'kpi/individual/process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            console.log('Raw response:', response);
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Raw response text:', text);
                    throw new Error('Invalid JSON response');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Clear and reload the table with new data
                stagingTable.clear();
                stagingTable.rows.add(data.data).draw();
                showNotification('Data processed successfully', 'success');
            } else {
                throw new Error(data.message || 'Failed to process data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error processing data: ' + error.message, 'error');
        })
        .finally(() => {
            // Reset button state
            $(this).prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Process');
        });
    });

    // Add KPI Modal form field handlers
    $('#addKPIModal').on('shown.bs.modal', function () {
        const project = $("#project").val();
        const modalKPISelect = $("#modalKPIMetrics");
        const modalQueueSelect = $("#modalQueue");

        // Initialize Select2 for all dropdowns
        $('#employeeSelect, #modalKPIMetrics, #modalQueue').select2({
            theme: "bootstrap4",
            width: "100%",
            dropdownParent: $('#addKPIModal'),
            placeholder: "Select an option...",
            allowClear: true
        });

        // Fetch KPI Metrics for the modal
        const kpiUrl = baseUrl + 'project/kpi?project=' + encodeURIComponent(project);
        fetch(kpiUrl)
            .then(response => response.json())
            .then(data => {
                modalKPISelect.empty().append('<option value="">Select KPI Metrics</option>');
                if (data.success) {
                    data.metrics.forEach(metric => {
                        modalKPISelect.append(
                            `<option value="${metric}">${metric}</option>`
                        );
                    });
                }
            })
            .catch(error => {
                console.error('Error loading KPI metrics:', error);
                showNotification('Error loading KPI metrics', 'error');
            });
    });

    // Handle KPI Metrics change for Queue population - moved outside modal shown event
    $("#modalKPIMetrics").on('change', function() {
        const project = $("#project").val();
        const selectedMetric = $(this).val();
        const modalQueueSelect = $("#modalQueue");

        if (selectedMetric) {
            const queueUrl = baseUrl + 'project/queues?project=' + encodeURIComponent(project) + 
                            '&kpi=' + encodeURIComponent(JSON.stringify([selectedMetric]));
            
            console.log('Fetching queues from:', queueUrl); // Debug log
            
            fetch(queueUrl)
                .then(response => response.json())
                .then(data => {
                    console.log('Queue response:', data); // Debug log
                    
                    modalQueueSelect.empty().append('<option value="">Select Queue</option>');
                    
                    if (data.success && data.queues && Array.isArray(data.queues)) {
                        data.queues.forEach(queue => {
                            modalQueueSelect.append(
                                `<option value="${queue}">${queue}</option>`
                            );
                        });
                        modalQueueSelect.prop('disabled', false);
                    } else if (data.success && data.data && Array.isArray(data.data)) {
                        data.data.forEach(queue => {
                            modalQueueSelect.append(
                                `<option value="${queue}">${queue}</option>`
                            );
                        });
                        modalQueueSelect.prop('disabled', false);
                    } else {
                        throw new Error('No queues found for the selected metric');
                    }
                })
                .catch(error => {
                    console.error('Error loading queues:', error);
                    showNotification('Error loading queues: ' + error.message, 'error');
                    modalQueueSelect.empty()
                        .append('<option value="">Error loading queues</option>')
                        .prop('disabled', true);
                });
        } else {
            modalQueueSelect.empty()
                .append('<option value="">Select KPI Metrics First</option>')
                .prop('disabled', true);
        }
    });

    // Clean up when modal closes
    $('#addKPIModal').on('hidden.bs.modal', function () {
        $('#employeeSelect, #modalKPIMetrics, #modalQueue').select2('destroy');
    });

    // Add KPI button click handler
    $("#addKPI").on("click", function() {
        const project = $("#project").val();
        if (!project) {
            showNotification('Please select a project first', 'error');
            return;
        }

        // Get the project name without the 'kpi_' prefix
        const projectName = project.replace(/^kpi_/, '');
        const url = baseUrl + 'project/employees?project=' + encodeURIComponent(projectName);

        console.log('Fetching employees from:', url); // Debug log

        // Fetch employees for the selected project
        fetch(url)
            .then(response => response.json())
            .then(response => {
                console.log('Employee data:', response); // Debug log
                
                const employeeSelect = $("#employeeSelect");
                employeeSelect.empty().append('<option value="">Search employee...</option>');

                if (response.success && response.data) {
                    response.data.forEach(emp => {
                        employeeSelect.append(
                            `<option value="${emp.nik}" data-name="${emp.employee_name}">
                                ${emp.nik} - ${emp.employee_name}
                            </option>`
                        );
                    });

                    // Initialize Select2 after populating options
                    employeeSelect.select2({
                        theme: "bootstrap4",
                        width: "100%",
                        dropdownParent: $('#addKPIModal'),
                        placeholder: "Search employee...",
                        allowClear: true
                    });

                    // Show the modal after initializing Select2
                    $("#addKPIModal").modal('show');
                } else {
                    showNotification('No employees found for this project', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error loading employees: ' + error.message, 'error');
            });
    });

    // Employee select change handler
    $("#employeeSelect").on("change", function() {
        const selectedOption = $(this).find("option:selected");
        const nik = selectedOption.val();
        const name = selectedOption.data('name');
        
        $("#selectedNik").val(nik);
        $("#selectedName").val(name);
    });

    // Add KPI form submit handler
    $("#addKPIForm").on("submit", function(e) {
        e.preventDefault();
        
        // Get form values and validate
        var employee = $('#employeeSelect').val();
        var kpiMetrics = $('#modalKPIMetrics').val();
        var queue = $('#modalQueue').val();
        var month = $('select[name="month"]').val();
        var value = $('input[name="value"]').val();
        
        // Validate employee
        if (!employee || employee === '') {
            showNotification('You need to select an employee', 'error');
            return false;
        }
        
        // Validate KPI Metrics
        if (!kpiMetrics || kpiMetrics === '' || kpiMetrics === 'Select KPI Metrics' || kpiMetrics === 'Select options') {
            showNotification('You need to select KPI Metrics', 'error');
            return false;
        }
        
        // Validate Queue
        if (!queue || queue === '' || queue === 'Select KPI Metrics First' || queue === 'Select options') {
            showNotification('You need to select Queue', 'error');
            return false;
        }
        
        // Validate month
        if (!month) {
            showNotification('You need to select a month', 'error');
            return false;
        }
        
        // Validate value
        if (!value || value === '') {
            showNotification('You need to enter a value', 'error');
            return false;
        }
        
        // If validation passes, proceed with form submission
        const formData = new FormData(this);
        formData.append('project', $("#project").val());

        fetch(baseUrl + 'kpi/individual/save', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $("#addKPIModal").modal('hide');
                showNotification('KPI added successfully', 'success');
                // Refresh the table
                $("#processKPI").click();
            } else {
                throw new Error(data.message || 'Failed to add KPI');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error adding KPI: ' + error.message, 'error');
        });
    });

    // Import form submit handler
    $("#importForm").on("submit", function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('project', $("#project").val());

        fetch(baseUrl + 'kpi/individual/import', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $("#importModal").modal('hide');
                showNotification('Data imported successfully', 'success');
                // Refresh the table
                $("#processKPI").click();
            } else {
                throw new Error(data.message || 'Import failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error importing data: ' + error.message, 'error');
        });
    });

    // Export button click handler
    $("#exportKPI").on("click", function() {
        const project = $("#project").val(); // This already contains 'kpi_' prefix
        if (!project) {
            showNotification('Please select a project first', 'error');
            return;
        }

        // Build the export URL
        let url = baseUrl + 'kpi/individual/export?project=' + encodeURIComponent(project);

        // Add metrics and queues if selected
        const selectedMetrics = $("#kpiMetrics").val();
        const selectedQueues = $("#queue").val();
        if (selectedMetrics && selectedMetrics.length > 0) {
            url += '&kpi=' + encodeURIComponent(JSON.stringify(selectedMetrics));
        }
        if (selectedQueues && selectedQueues.length > 0) {
            url += '&queue=' + encodeURIComponent(JSON.stringify(selectedQueues));
        }

        window.location.href = url;
    });

    // View metrics button click handler
    $(".view-metrics").on("click", function() {
        const project = $(this).data('project');
        const kpi = $(this).data('kpi');
        const queue = $(this).data('queue');

        // Set the selections
        $("#project").val(project).trigger('change');
        
        // Wait for KPI metrics to load
        setTimeout(() => {
            $("#kpiMetrics").val(kpi).trigger('change');
            
            // Wait for queues to load
            setTimeout(() => {
                $("#queue").val(queue).trigger('change');
                $("#processKPI").click();
            }, 500);
        }, 500);
    });

    // Make sure the input fields are editable when modal shows
    $('#addKPIModal').on('shown.bs.modal', function () {
        $('#modalKPIMetrics').prop('readonly', false);
        $('#modalQueue').prop('readonly', false);
    });

    // Use this function whenever you need to generate a table name
    $('#project').on('change', function() {
        let project = $(this).val();
        if (project) {
            let tableName = generateTableName(project);
            // Rest of your code...
        }
    });

    // For privilege level 2, trigger project loading automatically
    if ($('#project').is('input[type="hidden"]')) {
        const projectValue = $('#project').val();
        if (projectValue) {
            // Initialize select2 for KPI Metrics and Queue
            $('#kpiMetrics, #queue').select2({
                theme: "bootstrap4",
                width: "100%",
                placeholder: "Select options..."
            });
            
            // Trigger change event to load KPI metrics and other data
            $('#project').trigger('change');
        }
    }
});

// Notification helper function
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

// File input handler
$('.custom-file-input').on('change', function() {
    let fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').addClass("selected").html(fileName);
});
