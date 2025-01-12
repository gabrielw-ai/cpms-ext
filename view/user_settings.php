<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "User Settings";
ob_start();

// Include routing and database connection
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
require_once dirname(__DIR__) . '/controller/c_uac.php';

global $conn;
$uac = new UserAccessControl($_SESSION['user_privilege'] ?? 0);

// Only check if user is logged in, allow all privilege levels
if (!isset($_SESSION['user_nik'])) {
    header('Location: ' . Router::url('login'));
    exit;
}

// Verify access (though all privileges can access this)
if (!$uac->canAccessUserSettings()) {
    $_SESSION['error'] = "Access Denied";
    header('Location: ' . Router::url('dashboard'));
    exit;
}

// Add required CSS for notifications
$additional_css = '
<style>
    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
        max-width: 350px;
        animation: slideIn 0.5s ease-in-out;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .alert {
        margin-bottom: 1rem;
        border: none;
        border-radius: 4px;
    }

    .alert-success {
        background-color: #28a745;
        color: #fff;
    }

    .alert-danger {
        background-color: #dc3545;
        color: #fff;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>';

// Check for success/error messages
if (isset($_GET['success']) || isset($_GET['error'])) {
    $message = '';
    $type = 'success';
    
    if (isset($_GET['success'])) {
        $message = 'Password updated successfully';
    } else {
        $message = $_GET['error'];
        $type = 'error';
    }
    
    echo "<script>
        window.history.replaceState({}, document.title, window.location.pathname);
        document.addEventListener('DOMContentLoaded', function() {
            showNotification(" . json_encode($message) . ", '$type');
        });
    </script>";
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">User Settings</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo Router::url('user/settings/update'); ?>" method="POST">
            <div class="form-group">
                <label>User</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>NIK</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_nik']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Role</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_role']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" class="form-control" name="new_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Password</button>
        </form>
    </div>
</div>

<script>
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    $('.floating-alert').remove();
    
    // Create the notification element
    const alert = $('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show floating-alert">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        message +
        '</div>');

    // Add to body
    $('body').append(alert);

    // Auto dismiss after 3 seconds
    setTimeout(function() {
        alert.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<?php
$content = ob_get_clean();
?>
