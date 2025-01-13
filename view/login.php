<?php
// Remove the session check since site_config.php handles it
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// Include routing
require_once dirname(__DIR__) . '/routing.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com;');

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

// Set rate limiting
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
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
        .password-toggle {
            cursor: pointer;
        }
        /* Hide number input spinners */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
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
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo Router::url('auth/login'); ?>" method="post" id="loginForm">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="input-group mb-3">
                        <input type="number" class="form-control" name="nik" placeholder="NIK" required 
                               oninput="javascript: if (this.value.length > 20) this.value = this.value.slice(0, 20);"
                               onkeydown="return event.keyCode !== 69 && event.keyCode !== 189 && event.keyCode !== 187"
                               style="appearance: textfield;" autocomplete="off">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" id="password" 
                               placeholder="Password" required>
                        <div class="input-group-append">
                            <div class="input-group-text password-toggle" onclick="togglePassword()">
                                <span class="fas fa-eye"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block" 
                                    <?php echo ($_SESSION['login_attempts'] >= 5) ? 'disabled' : ''; ?>>
                                Sign In
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="<?php echo Router::url('adminlte/plugins/jquery/jquery.min.js'); ?>"></script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo Router::url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo Router::url('adminlte/dist/js/adminlte.min.js'); ?>"></script>
    
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const icon = document.querySelector('.password-toggle .fas');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Add client-side CSRF token to AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    </script>
</body>
</html>
