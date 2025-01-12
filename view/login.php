<?php
// Remove the session check since site_config.php handles it
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// Include routing
require_once dirname(__DIR__) . '/routing.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_nik'])) {
    header('Location: ' . Router::url('dashboard'));
    exit;
}

// Only check for ROUTING_INCLUDE if not accessing directly through the login route
if (!defined('ROUTING_INCLUDE') && $_SERVER['REQUEST_URI'] !== Router::url('login')) {
    header('Location: ' . Router::url('login'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CPMS Login</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo Router::url('adminlte/plugins/fontawesome-free/css/all.min.css'); ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo Router::url('adminlte/dist/css/adminlte.min.css'); ?>">

    <style>
        .login-page {
            background: #f4f6f9;
            height: 100vh;
        }
        .login-box {
            margin-top: 0;
            padding-top: 100px;
        }
        .login-logo img {
            max-width: 200px;
            height: auto;
        }
        /* Add some additional styling for better appearance */
        .login-card-body {
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .input-group-text {
            background-color: transparent;
        }
        .alert {
            margin-bottom: 1rem;
            border: none;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <b>CPMS</b> Login
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo Router::url('auth/login'); ?>" method="post" id="loginForm">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="nik" placeholder="NIK" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
