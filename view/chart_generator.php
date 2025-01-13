<?php
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

$page_title = "Chart Generator";

// Function to get week number from date
function getWeekNumber($date) {
    return date('W', strtotime($date));
}

// Function to get month name from date
function getMonthName($date) {
    return date('F', strtotime($date));
}

// Add required CSS
$additional_css = '
<!-- Select2 -->
<link rel="stylesheet" href="../adminlte/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<!-- DateRangePicker -->
<link rel="stylesheet" href="../adminlte/plugins/daterangepicker/daterangepicker.css">
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
    .date-range-container {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }
    .date-range-container .form-group {
        flex: 1;
    }
</style>';

// Add required JavaScript
$additional_js = '
<!-- Select2 -->
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
<!-- Moment.js -->
<script src="../adminlte/plugins/moment/moment.min.js"></script>
<!-- DateRangePicker -->
<script src="../adminlte/plugins/daterangepicker/daterangepicker.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 Elements
    $(".select2").select2({
        theme: "bootstrap4"
    });

    // Initialize DateRangePicker
    $("#date_range").daterangepicker({
        locale: {
            format: "YYYY-MM-DD"
        },
        startDate: moment().startOf("month"),
        endDate: moment(),
        ranges: {
           "This Month": [moment().startOf("month"), moment().endOf("month")],
           "Last Month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],
           "Last 3 Months": [moment().subtract(2, "month").startOf("month"), moment()],
           "This Year": [moment().startOf("year"), moment()]
        }
    });

    // Handle period change
    $("#period").on("change", function() {
        const period = $(this).val();
        const dateRange = $("#date_range").data("daterangepicker");
        const startDate = dateRange.startDate;
        const endDate = dateRange.endDate;
        
        if (period === "weekly") {
            // Get week numbers for the date range
            const weeks = [];
            let currentDate = moment(startDate);
            
            while (currentDate <= endDate) {
                const weekNum = currentDate.isoWeek();
                const year = currentDate.year();
                if (!weeks.includes(`Week ${weekNum} (${year})`)) {
                    weeks.push(`Week ${weekNum} (${year})`);
                }
                currentDate.add(1, "week");
            }
            
            console.log("Weeks in range:", weeks);
        } else if (period === "monthly") {
            // Get months for the date range
            const months = [];
            let currentDate = moment(startDate);
            
            while (currentDate <= endDate) {
                const monthName = currentDate.format("MMMM YYYY");
                if (!months.includes(monthName)) {
                    months.push(monthName);
                }
                currentDate.add(1, "month");
            }
            
            console.log("Months in range:", months);
        }
    });

    // Handle form submission
    $("#chartFilterForm").submit(function(e) {
        e.preventDefault();
        
        const project = $("#project").val();
        const period = $("#period").val();
        const metric = $("#kpi_metrics").val();
        const dateRange = $("#date_range").data("daterangepicker");
        const startDate = dateRange.startDate.format("YYYY-MM-DD");
        const endDate = dateRange.endDate.format("YYYY-MM-DD");
        
        if (!project || !metric) return;
        
        // Show loading
        const chartContainer = document.getElementById("chartContainer");
        chartContainer.innerHTML = "<div class=\"d-flex justify-content-center align-items-center\" style=\"height:500px\"><div class=\"spinner-border text-primary\"></div></div>";
        
        // Fetch data
        fetch(`../controller/get_chart_data.php?project=${encodeURIComponent(project)}&period=${period}&metric=${encodeURIComponent(metric)}&start_date=${startDate}&end_date=${endDate}`)
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
                                text: `${metric} (${period === "weekly" ? "Weekly" : "Monthly"} View)`
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
                <div class="col-md-3">
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
                <div class="col-md-2">
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
                        <label for="date_range">Date Range</label>
                        <input type="text" class="form-control" id="date_range" name="date_range" required>
                    </div>
                </div>
                <div class="col-md-3">
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
