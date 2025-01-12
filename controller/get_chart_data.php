<?php
require_once 'conn.php';

header('Content-Type: application/json');

if (isset($_GET['project']) && isset($_GET['period']) && isset($_GET['metric'])) {
    try {
        $tableName = $_GET['project'];
        $period = $_GET['period'];
        $metric = $_GET['metric'];
        
        if ($period === 'monthly') {
            if (!str_ends_with($tableName, '_mon')) {
                $tableName = $tableName . '_mon';
            }
            
            $sql = "SELECT month, value 
                    FROM `{$tableName}_values` v 
                    JOIN `{$tableName}` k ON v.kpi_id = k.id 
                    WHERE k.kpi_metrics = ? 
                    AND month BETWEEN 1 AND 12 
                    ORDER BY month ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$metric]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            $values = array_fill(0, 12, 0); // Initialize with zeros
            foreach ($data as $row) {
                $monthIndex = (int)$row['month'] - 1;
                $values[$monthIndex] = floatval($row['value']);
            }

            echo json_encode([
                'success' => true,
                'labels' => $monthNames,
                'values' => $values
            ]);
        } else {
            $sql = "SELECT k.queue, k.kpi_metrics, k.target, k.target_type, ";
            
            // Add week columns dynamically
            $weekColumns = [];
            for ($i = 1; $i <= 52; $i++) {
                $weekNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                $weekColumns[] = "MAX(CASE WHEN v.week = $i THEN v.value END) as WK$weekNum";
            }
            $sql .= implode(", ", $weekColumns);
            
            $sql .= " FROM `$tableName` k
                     LEFT JOIN `{$tableName}_values` v ON k.id = v.kpi_id
                     WHERE k.kpi_metrics = ?
                     GROUP BY k.id, k.queue, k.kpi_metrics, k.target, k.target_type";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$metric]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            // Generate labels and values for all 52 weeks
            $labels = [];
            $values = [];
            for ($i = 1; $i <= 52; $i++) {
                $weekNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                $labels[] = "Week " . $weekNum;
                $values[] = floatval($data["WK$weekNum"] ?? 0);
            }

            echo json_encode([
                'success' => true,
                'labels' => $labels,
                'values' => $values
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
}
?> 