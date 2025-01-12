<?php
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

$page_title = "Chart Generator";

// Add required CSS
$additional_css = '
<!-- Select2 -->
<link rel="stylesheet" href="../adminlte/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<style>
    .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px);
        padding: .375rem .75rem;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
    }
    .table th, .table td {
        white-space: nowrap;
        vertical-align: middle;
    }
</style>';

// Add required JavaScript
$additional_js = '
<!-- Select2 -->
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 Elements
    $(".select2").select2({
        theme: "bootstrap4"
    });

    // Update KPI metrics when project or period changes
    $("#project, #period").change(function() {
        const project = $("#project").val();
        const period = $("#period").val();
        
        if (!project) {
            $("#kpi_metrics").html("<option value=\'\'>Select Project First</option>").prop("disabled", true);
            return;
        }

        const tableName = project + (period === "monthly" ? "_mon" : "");
        
        // Show loading
        $("#kpi_metrics").html("<option value=\'\'>Loading...</option>").prop("disabled", true);
        
        // Fetch metrics
        fetch(`../controller/get_kpi_metrics.php?table=${encodeURIComponent(tableName)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(\'Network response was not ok\');
                }
                return response.json();
            })
            .then(data => {
                const select = $("#kpi_metrics");
                select.empty().append("<option value=\'\'>Select KPI Metric</option>");
                
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(metric => {
                        select.append(new Option(metric, metric));
                    });
                    select.prop("disabled", false);
                } else if (data.error) {
                    throw new Error(data.error);
                } else {
                    select.html("<option value=\'\'>No metrics found</option>");
                }
            })
            .catch(error => {
                console.error(\'Error:\', error);
                $("#kpi_metrics")
                    .html("<option value=\'\'>Error loading metrics</option>")
                    .prop("disabled", true);
            });
    });

    // Handle form submission
    $("#chartFilterForm").submit(function(e) {
        e.preventDefault();
        
        const project = $("#project").val();
        const period = $("#period").val();
        const metric = $("#kpi_metrics").val();
        
        if (!project || !metric) return;
        
        // Show loading
        const chartContainer = document.getElementById("chartContainer");
        chartContainer.innerHTML = "<div class=\"d-flex justify-content-center align-items-center\" style=\"height:500px\"><div class=\"spinner-border text-primary\"></div></div>";
        
        // Fetch data
        fetch(`../controller/get_chart_data.php?project=${encodeURIComponent(project)}&period=${period}&metric=${encodeURIComponent(metric)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.error);
                
                // Clear loading
                chartContainer.innerHTML = "<canvas></canvas>";
                
                // Create chart
                new Chart(chartContainer.querySelector("canvas"), {
                    type: "line",
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: metric,
                            data: data.values,
                            borderColor: "rgb(75, 192, 192)",
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: metric
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                chartContainer.innerHTML = `<div class="alert alert-danger m-3">${error.message}</div>`;
            });
    });
});
</script>';

ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">KPI Chart Generator</h3>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form id="chartFilterForm" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="project">Project Name</label>
                        <select class="form-control select2" id="project" name="project" required>
                            <option value="">Select Project</option>
                            <?php
                            try {
                                $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $projectName = $row['project_name'];
                                    $tableName = 'kpi_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $projectName));
                                    echo "<option value='" . htmlspecialchars($tableName) . "'>" . 
                                         htmlspecialchars($projectName) . "</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Error loading projects</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="period">Period</label>
                        <select class="form-control select2" id="period" name="period" required>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="kpi_metrics">KPI Metrics</label>
                        <select class="form-control select2" id="kpi_metrics" name="kpi_metrics" required>
                            <option value="">Select Project First</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-chart-line mr-2"></i>Generate Chart
                </button>
            </div>
        </form>

        <!-- Chart Container -->
        <div id="chartContainer" style="height: 500px;">
            <div class="text-center text-muted my-5">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <p>Select filters and click Generate Chart to view the data</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
?>
