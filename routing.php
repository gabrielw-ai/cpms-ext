<?php
require_once __DIR__ . '/config.php';

class Router {
    private static $staticRoutes = [];
    private $instanceRoutes;
    private $baseUrl;
    private $basePath;
    
    public function __construct() {
        $this->instanceRoutes = require __DIR__ . '/routes.php';
        $this->baseUrl = getBaseUrl();
        $this->basePath = rtrim(parse_url($this->baseUrl, PHP_URL_PATH), '/');
    }
    
    public static function add($name, $path) {
        self::$staticRoutes[$name] = $path;
    }
    
    public function route($uri = null) {
        if ($uri === null) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        
        // Debug output
        error_log("Routing URI: " . $uri);
        error_log("Base Path: " . $this->basePath);
        
        // Remove query string
        $uri = explode('?', $uri)[0];
        
        // Remove base path from URI if it exists
        if ($this->basePath && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }
        
        // Clean the URI
        $uri = trim($uri, '/');
        
        error_log("Cleaned URI: " . $uri);
        error_log("Available Routes: " . print_r($this->instanceRoutes, true));
        
        // Handle root URL
        if ($uri === '') {
            $uri = 'dashboard';
        }
        
        // Check defined routes
        if (isset($this->instanceRoutes[$uri])) {
            $file = __DIR__ . '/' . $this->instanceRoutes[$uri];
            
            if (file_exists($file)) {
                define('SCRIPT_DIR', dirname($file));
                define('ROUTING_INCLUDE', true);
                
                // For login and auth routes, don't include the main navbar
                if (in_array($uri, ['login', 'auth/login'])) {
                    require $file;
                    return;
                }
                
                $page_title = "";
                $content = "";
                $additional_css = "";
                $additional_js = "";
                
                require $file;
                require __DIR__ . '/main_navbar.php';
                return;
            }
        }
        
        $this->notFound();
    }
    
    private function notFound() {
        header("HTTP/1.0 404 Not Found");
        $page_title = "404 Not Found";
        $content = require __DIR__ . '/view/404.php';
        require __DIR__ . '/main_navbar.php';
        exit;
    }
    
    public static function url($route = '') {
        $baseUrl = getBaseUrl();
        
        // If route exists in our static routes, use that path
        if (isset(self::$staticRoutes[$route])) {
            return $baseUrl . self::$staticRoutes[$route];
        }
        
        // Otherwise use the route as-is
        return $baseUrl . '/' . trim($route, '/');
    }
} 

// Define routes - Map URLs to actual file paths
Router::add('logout', '/controller/c_logout.php');
Router::add('dashboard', '/dashboard');  // URL path
Router::add('kpi/metrics', '/kpi/metrics');  // URL path
Router::add('kpi/viewer', '/kpi/viewer');  // URL path
Router::add('kpi/individual', '/kpi/individual');
Router::add('kpi/charts', '/kpi/charts');  // URL path
Router::add('employees', '/employees');  // Main employee list route
Router::add('ccs/rules', '/ccs/rules');  // URL path
Router::add('ccs/viewer', '/ccs/viewer');  // URL path
Router::add('projects', '/projects');  // URL path
Router::add('roles', '/roles');  // URL path
Router::add('uac', '/uac');  // URL path 
Router::add('role/uac', '/role/uac');  // Role UAC path
Router::add('user/mass-reset', '/user/mass-reset');  // Mass Reset Password path

// Add routes for employee management
Router::add('employee/add', '/controller/c_employeelist.php');
Router::add('employee/edit', '/controller/c_employeelist.php');
Router::add('employee/delete', '/controller/c_employeelist.php');
Router::add('employee/import', '/controller/c_import_employee.php');
Router::add('employee/export', '/controller/c_export_employee.php'); 

// Add Project Management routes
Router::add('project/add', '/controller/c_project_namelist.php');
Router::add('project/edit', '/controller/c_project_namelist.php');
Router::add('project/delete', '/controller/c_project_namelist.php');
Router::add('project/get', 'controller/c_project_namelist.php'); 

// Add User Settings route
Router::add('user/settings', '/user/settings');  // URL path 

// Add this with the other Router::add calls
Router::add('kpi/delete', '/controller/c_viewer_del.php');

// In the static routes section
Router::add('login', '/login');  // URL path
Router::add('auth/login', '/auth/login');  // URL path

// Add this with the other Router::add calls
Router::add('project/kpi', '/controller/get_project_kpi.php');

// Add this with the other Router::add calls
Router::add('project/employees', '/controller/get_project_employees.php');

// Add this with other routes
Router::add('employee/details', 'controller/get_employee_details.php');

