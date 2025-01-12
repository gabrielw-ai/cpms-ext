<?php
// Remove the session check since site_config.php handles it
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

$page_title = "Dashboard";

// Check if user is logged in
if (!isset($_SESSION['user_nik'])) {
    header('Location: ' . Router::url('login'));
    exit;
}

// Store the content
$content = '
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tachometer-alt mr-1"></i>
                    Dashboard Overview
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <!-- Empty dashboard content -->
                        <div class="text-center py-5">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Welcome to CPMS Dashboard</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';
