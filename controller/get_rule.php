<?php
require_once 'conn.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM ccs_rules WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rule) {
            echo json_encode(['success' => true, 'rule' => $rule]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Rule not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID not provided']);
} 