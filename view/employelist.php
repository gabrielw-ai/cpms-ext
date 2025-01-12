<?php
$page_title = "Employee List";
ob_start();
require_once '../controller/conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="../adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
</head>
<body>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Employee List</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display: none;">
                <i class="fas fa-trash mr-2"></i>Delete Selected (<span id="selectedCount">0</span>)
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="employeeTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="50px">
                        <input type="checkbox" id="selectAll" class="select-all-checkbox">
                    </th>
                    <th>NIK</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Project</th>
                    <th>Join Date</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                try {
                    $sql = "SELECT NIK, employee_name, employee_email, role, project, 
                            DATE_FORMAT(join_date, '%d-%m-%Y') as formatted_join_date 
                            FROM employee_active 
                            ORDER BY employee_name";

                    $stmt = $conn->query($sql);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td><input type='checkbox' class='employee-select' value='" . htmlspecialchars($row['NIK']) . "'></td>";
                        echo "<td>" . htmlspecialchars($row['NIK']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['employee_email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['project']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['formatted_join_date']) . "</td>";
                        echo "</tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='7'>Error: " . $e->getMessage() . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- AdminLTE JS -->
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script>
    $(document).ready(function () {
        const selectAllCheckbox = $('#selectAll');
        const employeeCheckboxes = $('.employee-select');

        // "Select All" functionality
        selectAllCheckbox.on('change', function () {
            const isChecked = $(this).is(':checked');
            employeeCheckboxes.prop('checked', isChecked);
        });

        // Update "Select All" state dynamically
        employeeCheckboxes.on('change', function () {
            const totalCheckboxes = employeeCheckboxes.length;
            const checkedCheckboxes = employeeCheckboxes.filter(':checked').length;
            selectAllCheckbox.prop('checked', totalCheckboxes === checkedCheckboxes);
            selectAllCheckbox.prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        });

        // Example: Show/hide delete button based on selected count
        employeeCheckboxes.on('change', function () {
            const selectedCount = employeeCheckboxes.filter(':checked').length;
            $('#bulkDeleteBtn').toggle(selectedCount > 0);
            $('#selectedCount').text(selectedCount);
        });
    });
</script>
</body>
</html>
