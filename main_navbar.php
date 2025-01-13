<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config file for URL functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controller/c_uac.php';  // UAC class
require_once __DIR__ . '/controller/conn.php';  // Add database connection

// Check if user is logged in
if (!isset($_SESSION['user_nik'])) {
    header('Location: ' . getBaseUrl() . '/view/login.php');
    exit;
}

// Get user's role and privilege level from database
try {
    $stmt = $conn->prepare("
        SELECT ea.role, rm.privileges 
        FROM employee_active ea
        JOIN role_mgmt rm ON ea.role = rm.role
        WHERE ea.nik = ?
    ");
    $stmt->execute([$_SESSION['user_nik']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $userRole = $userData['role'] ?? '';
    $userPrivilege = $userData['privileges'] ?? 2; // Default to 2 if not found
    $isAgent = ($userRole === 'Agent');

    // Store in session for future use
    $_SESSION['user_role'] = $userRole;
    $_SESSION['user_privilege'] = $userPrivilege;
} catch (PDOException $e) {
    // Handle database error - fallback to default values
    $userRole = $_SESSION['user_role'] ?? '';
    $userPrivilege = $_SESSION['user_privilege'] ?? 2;
    $isAgent = ($userRole === 'Agent');
}

// Initialize UAC with user's privilege level
$uac = new UserAccessControl($userPrivilege);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo getAssetUrl('plugins/fontawesome-free/css/all.min.css'); ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo getAssetUrl('dist/css/adminlte.min.css'); ?>">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?php echo getAssetUrl('plugins/select2/css/select2.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css'); ?>">
    <?php if (isset($additional_css)) echo $additional_css; ?>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>

        <!-- Add this to your navbar for logout -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="<?php echo Router::url('logout'); ?>" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?php echo Router::url('dashboard'); ?>" class="brand-link">
            <img src="<?php echo getAssetUrl('dist/img/AdminLTELogo.png'); ?>" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">CPMS</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <?php
                    $menuItems = $uac->getMenuItems();
                    foreach ($menuItems as $item):
                        if (isset($item['header'])): ?>
                            <li class="nav-header"><?php echo htmlspecialchars($item['header']); ?></li>
                        <?php else: ?>
                            <li class="nav-item<?php echo isset($item['submenu']) ? ' has-treeview' : ''; ?>">
                                <?php if (isset($item['submenu'])): ?>
                                    <a href="#" class="nav-link">
                                        <i class="nav-icon <?php echo htmlspecialchars($item['icon']); ?>"></i>
                                        <p>
                                            <?php echo htmlspecialchars($item['text']); ?>
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <?php foreach ($item['submenu'] as $subitem): ?>
                                            <li class="nav-item">
                                                <a href="<?php echo Router::url($subitem['url']); ?>" class="nav-link">
                                                    <i class="nav-icon <?php echo htmlspecialchars($subitem['icon']); ?>"></i>
                                                    <p><?php echo htmlspecialchars($subitem['text']); ?></p>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <a href="<?php echo Router::url($item['url']); ?>" class="nav-link">
                                        <i class="nav-icon <?php echo htmlspecialchars($item['icon']); ?>"></i>
                                        <p><?php echo htmlspecialchars($item['text']); ?></p>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endif;
                    endforeach; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo $page_title; ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo $content; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2024 <a href="#">CPMS</a>.</strong>
        All rights reserved.
    </footer>
</div>

<!-- REQUIRED SCRIPTS -->
<script src="<?php echo getAssetUrl('plugins/jquery/jquery.min.js'); ?>"></script>
<script src="<?php echo getAssetUrl('plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo getAssetUrl('dist/js/adminlte.min.js'); ?>"></script>
<script>
$(document).ready(function() {
    // Initialize AdminLTE sidebar
    if (typeof $.fn.overlayScrollbars !== "undefined") {
        $(".sidebar").overlayScrollbars({ 
            className: "os-theme-light",
            scrollbars: {
                autoHide: "l",
                clickScrolling: true
            }
        });
    }
    
    // Initialize active menu item
    $('a.nav-link').each(function() {
        if (this.href === window.location.href) {
            $(this).addClass('active');
            $(this).parents('.nav-item').addClass('menu-open');
            $(this).parents('.nav-treeview').prev().addClass('active');
        }
    });

    // Initialize Bootstrap dropdowns
    $('.dropdown-toggle').dropdown();
    
    // Ensure dropdowns work on hover
    $('.nav-item.dropdown').hover(
        function() { $(this).find('.dropdown-menu').stop(true, true).delay(200).fadeIn(300); },
        function() { $(this).find('.dropdown-menu').stop(true, true).delay(200).fadeOut(300); }
    );
});
</script>
<?php if (isset($additional_js)) echo $additional_js; ?>
</body>
</html>
