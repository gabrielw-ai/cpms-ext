<?php
session_start();
require_once 'conn.php';
require_once dirname(__DIR__) . '/routing.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Access denied');
        }

        // Check rate limiting
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt'] = time();
        }

        // Reset attempts if last attempt was more than 30 minutes ago
        if (time() - $_SESSION['last_attempt'] > 1800) {
            $_SESSION['login_attempts'] = 0;
        }

        // Check if user is blocked
        if ($_SESSION['login_attempts'] >= 5) {
            $timeLeft = 1800 - (time() - $_SESSION['last_attempt']);
            if ($timeLeft > 0) {
                throw new Exception('Please try again later');
            } else {
                $_SESSION['login_attempts'] = 0;
            }
        }

        // Update last attempt time
        $_SESSION['last_attempt'] = time();

        // Sanitize and validate inputs
        $nik = trim(filter_input(INPUT_POST, 'nik', FILTER_SANITIZE_NUMBER_INT));
        if (!$nik || !preg_match('/^\d+$/', $nik)) {
            throw new Exception('Invalid credentials');
        }

        $password = $_POST['password'] ?? '';
        if (empty($password)) {
            throw new Exception('Invalid credentials');
        }

        // Query should use lowercase column names
        $sql = "SELECT nik, employee_name, employee_email, role, project, password 
                FROM employee_active 
                WHERE nik = ?
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nik]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful - reset attempts
            $_SESSION['login_attempts'] = 0;
            
            // Set session variables
            $_SESSION['user_nik'] = $user['nik'];
            $_SESSION['user_name'] = $user['employee_name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Log successful login
            error_log("Login successful for user: " . $user['employee_name']);
            
            header('Location: ' . Router::url('dashboard'));
            exit;
        } else {
            // Increment failed attempts
            $_SESSION['login_attempts']++;
            
            // Generic error message for any login failure
            error_log("Login failed for NIK: " . $nik . " (Attempt " . $_SESSION['login_attempts'] . ")");
            throw new Exception('Invalid credentials');
        }
    } else {
        // If not POST request, redirect to login
        header('Location: ' . Router::url('login'));
        exit;
    }
} catch (Exception $e) {
    // Log the actual error for debugging
    error_log("Login error: " . $e->getMessage());
    
    // Show generic message to user
    $_SESSION['error'] = 'Invalid credentials';
    header('Location: ' . Router::url('login'));
    exit;
}

// Close connection
$conn = null;
?> 