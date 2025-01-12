<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- User Menu Dropdown -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-user"></i>
                <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <!-- User image -->
                <li class="user-header bg-primary">
                    <i class="fas fa-user-circle fa-3x"></i>
                    <p>
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
                        <small>NIK: <?php echo htmlspecialchars($_SESSION['user_nik'] ?? ''); ?></small>
                    </p>
                </li>
                <!-- Menu Footer-->
                <li class="user-footer">
                    <a href="<?php echo Router::url('user/settings'); ?>" class="btn btn-default btn-flat">Profile</a>
                    <a href="<?php echo Router::url('logout'); ?>" class="btn btn-default btn-flat float-right">Sign out</a>
                </li>
            </ul>
        </li>
    </ul>
</nav>
<!-- /.navbar --> 