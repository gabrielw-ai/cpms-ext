$(function() {
    // Initialize Select2 Elements
    $(".select2bs4").select2({
        theme: "bootstrap4",
        width: '100%'
    });

    // Initialize Custom File Input
    bsCustomFileInput.init();

    // Filter out options with "NIK -" in the Name dropdown
    $("#employee option").each(function() {
        if ($(this).text().trim().endsWith('-')) {
            $(this).remove();
        }
    });

    // Get the logged-in user's NIK from hidden input instead of text
    const loggedInNik = $("#loggedInNik").val();
    
    // Handle project selection
    $("#project").on("change", function() {
        const selectedProject = $(this).val();
        const loggedInNik = $("#loggedInNik").val(); // Get logged-in NIK
        console.log('Selected project:', selectedProject);
        
        if (selectedProject) {
            // Get the project name without the 'kpi_' prefix
            const projectName = selectedProject.replace(/^kpi_/, '');
            const url = baseUrl + 'project/employees?project=' + encodeURIComponent(projectName);
            
            console.log('Fetching employees from:', url); // Debug log
            
            // Fetch employees for the selected project
            fetch(url)
                .then(response => response.json())
                .then(response => {
                    console.log('Employee data:', response); // Debug log
                    
                    const employeeSelect = $("#employee");
                    employeeSelect.empty().append('<option value="">-- Select Employee --</option>');

                    if (response.success && response.data) {
                        response.data
                            .filter(emp => {
                                // Filter out logged-in user and invalid entries
                                return emp && 
                                       emp.employee_name && 
                                       emp.employee_name.trim() !== '' && 
                                       emp.nik && 
                                       emp.nik.trim() !== '' &&
                                       emp.nik !== loggedInNik; // Exclude logged-in user
                            })
                            .forEach(emp => {
                                const optionText = `${emp.nik} - ${emp.employee_name}`;
                                if (!optionText.endsWith(' -')) {
                                    employeeSelect.append(
                                        `<option value="${emp.nik}" data-name="${emp.employee_name}">
                                            ${emp.nik} - ${emp.employee_name}
                                        </option>`
                                    );
                                }
                            });
                        
                        // Re-initialize Select2
                        employeeSelect.select2({
                            theme: "bootstrap4",
                            width: "100%",
                            placeholder: "Select Employee"
                        });

                        if (employeeSelect.find('option').length <= 1) {
                            showNotification('No eligible employees found for this project', 'error');
                        }
                    } else {
                        showNotification('No employees found for this project', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading employees: ' + error.message, 'error');
                });
        } else {
            // Reset employee select if no project selected
            $("#employee").empty()
                .append('<option value="">-- Select Employee --</option>')
                .trigger('change');
        }
    });

    // Employee change handler
    $("#employee").on("change", function() {
        const selectedOption = $(this).find("option:selected");
        const nik = selectedOption.val();
        const name = selectedOption.data('name');
        
        $("#name").val(name);
        $("#nik").val(nik);
        
        if (nik) {
            // Fetch employee details (role, tenure) here if needed
            fetchEmployeeDetails(nik);
        } else {
            // Reset fields if no employee selected
            $("#role").val('');
            $("#tenure").val('');
        }
    });

    // Enhanced form validation
    $('#ccsRulesForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get the effective date
        const effectiveDate = new Date($('#effective_date').val());
        const today = new Date();
        
        // Reset time parts for proper date comparison
        effectiveDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        
        // Only block future dates, allow today
        if (effectiveDate > today) {
            showNotification('Effective date cannot be in the future', 'error');
            return false;
        }

        // Continue with form submission
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('CCS Rule added successfully', 'success');
                    setTimeout(function() {
                        window.location.href = baseUrl + 'ccs/viewer';
                    }, 1500);
                } else {
                    showNotification(response.message || 'Failed to add CCS Rule', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error: ' + error, 'error');
            }
        });
    });

    // File input handler with validation
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        var fileExtension = fileName.split('.').pop().toLowerCase();
        
        if (['pdf', 'xlsx', 'xls'].includes(fileExtension)) {
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
            $(this).removeClass('is-invalid');
        } else {
            $(this).val('');
            $(this).next('.custom-file-label').html('Choose file');
            $(this).addClass('is-invalid');
            alert('Please upload only PDF or Excel files');
        }
    });

    // Calculate end date with validation
    $('#effective_date, #ccs_rule').on('change', function() {
        calculateEndDate();
    });

    function calculateEndDate() {
        const effectiveDate = $('#effective_date').val();
        const ccsRule = $('#ccs_rule').val();
        
        if (effectiveDate && ccsRule) {
            const startDate = new Date(effectiveDate);
            let endDate = new Date(startDate);
            
            if (ccsRule.startsWith('WR')) {
                endDate.setFullYear(endDate.getFullYear() + 1);
                endDate.setDate(endDate.getDate() - 1);
            } else {
                endDate.setMonth(endDate.getMonth() + 6);
                endDate.setDate(endDate.getDate() - 1);
            }
            
            $('#end_date').val(endDate.toISOString().split('T')[0]);
        }
    }

    // Pre-fill form fields in edit mode
    if (typeof ruleData !== 'undefined') {
        $('#project').val(ruleData.project).trigger('change');
        $('#name').val(ruleData.name);
        $('#nik').val(ruleData.nik);
        $('#role').val(ruleData.role);
        $('#tenure').val(ruleData.tenure);
        $('#case_chronology').val(ruleData.case_chronology);
        $('#ccs_rule').val(ruleData.consequences);
        $('#effective_date').val(ruleData.effective_date);
        
        // Make document upload optional when editing
        $('#document').removeAttr('required');
    }

    // Date input change handler
    $('#effective_date').on('change', function() {
        const selectedDate = new Date($(this).val());
        const today = new Date();
        
        // Reset time parts for proper date comparison
        selectedDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        
        // Only block future dates, allow today
        if (selectedDate > today) {
            showNotification('Effective date cannot be in the future', 'error');
            $(this).val(''); // Clear the input
        }
    });
});

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

// Function to fetch employee details
function fetchEmployeeDetails(nik) {
    // Add console.log to debug the URL
    console.log('Base URL:', baseUrl);
    const url = `${baseUrl}employee/details?nik=${encodeURIComponent(nik)}`;
    console.log('Fetching from URL:', url);
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Employee details:', data); // Debug log
            if (data.success) {
                $("#role").val(data.role || '');
                $("#tenure").val(data.tenure || '');
            } else {
                throw new Error(data.message || 'Failed to load employee details');
            }
        })
        .catch(error => {
            console.error('Error fetching employee details:', error);
            showNotification('Error loading employee details: ' + error.message, 'error');
        });
} 