<?php
require_once 'conn.php';

header('Content-Type: application/json');

// Helper function to get week number
function getWeekNumber($date) {
    return date('W', strtotime($date));
}

// Helper function to get year
function getYear($date) {
    return date('Y', strtotime($date));
}

// Helper function to get month name with year
function getMonthYear($date) {
    return date('F Y', strtotime($date));
}

if (isset($_GET['project']) && isset($_GET['period']) && isset($_GET['metric']) && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    try {
        $tableName = $_GET['project'];
        $period = $_GET['period'];
        $metric = $_GET['metric'];
        $startDate = $_GET['start_date'];
        $endDate = $_GET['end_date'];
        
        // Add _mon suffix for monthly data
        if ($period === 'monthly' && !str_ends_with($tableName, '_mon')) {
            $tableName = $tableName . '_mon';
        }

        // Base query to get data between dates
        $sql = "SELECT date_created, value 
                FROM `{$tableName}` 
                WHERE kpi_metrics = ? 
                AND date_created BETWEEN ? AND ?
                ORDER BY date_created ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$metric, $startDate, $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        $labels = [];
        
        if ($period === 'weekly') {
            // Group by week number and year
            $weeklyData = [];
            foreach ($results as $row) {
                $weekNum = getWeekNumber($row['date_created']);
                $year = getYear($row['date_created']);
                $key = "Week {$weekNum} ({$year})";
                
                if (!isset($weeklyData[$key])) {
                    $weeklyData[$key] = [
                        'sum' => 0,
                        'count' => 0
                    ];
                }
                
                $weeklyData[$key]['sum'] += floatval($row['value']);
                $weeklyData[$key]['count']++;
            }
            
            // Calculate averages and prepare data
            foreach ($weeklyData as $week => $values) {
                $labels[] = $week;
                $data[] = round($values['sum'] / $values['count'], 2);
            }
        } else {
            // Group by month and year
            $monthlyData = [];
            foreach ($results as $row) {
                $monthYear = getMonthYear($row['date_created']);
                
                if (!isset($monthlyData[$monthYear])) {
                    $monthlyData[$monthYear] = [
                        'sum' => 0,
                        'count' => 0
                    ];
                }
                
                $monthlyData[$monthYear]['sum'] += floatval($row['value']);
                $monthlyData[$monthYear]['count']++;
            }
            
            // Calculate averages and prepare data
            foreach ($monthlyData as $month => $values) {
                $labels[] = $month;
                $data[] = round($values['sum'] / $values['count'], 2);
            }
        }

        echo json_encode([
            'success' => true,
            'labels' => $labels,
            'values' => $data
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required parameters'
    ]);
}
?> 