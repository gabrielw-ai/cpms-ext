<?php
// Define routes and their handlers
return [
    // Default routes - make sure these are first
    '' => 'view/dashboard.php',
    'dashboard' => 'view/dashboard.php',
    'home' => 'view/dashboard.php',
    
    // Authentication routes
    'login' => 'view/login.php',
    'auth/login' => 'controller/c_login.php',
    'logout' => 'controller/c_logout.php',
    
    // KPI Management routes
    'kpi/metrics' => 'view/tbl_metrics.php',
    'kpi/viewer' => 'view/kpi_viewer.php',
    'kpi/individual' => 'view/kpi_individual.php',
    'kpi/charts' => 'view/chart_generator.php',
    
    // Employee routes
    'employees' => 'view/employee_list.php',
    
    // CCS Rules routes
    'ccs/rules' => 'view/ccs_rules_mgmt.php',
    'ccs/viewer' => 'view/ccs_viewer.php',
    'ccs/delete' => 'controller/c_ccs_rules.php',
    'ccs/update' => 'controller/c_ccs_rules.php',
    
    // Project routes
    'projects' => 'view/project_namelist.php',
    'project/add' => 'controller/c_project_namelist.php',
    'project/edit' => 'controller/c_project_namelist.php',
    'project/delete' => 'controller/c_project_namelist.php',
    'project/get' => 'controller/c_project_namelist.php',
    
    // Role Management
    'roles' => 'view/role_mgmt.php',
    'role/manage' => 'controller/c_role_mgmt.php',
    
    // Role UAC routes
    'role/uac' => 'view/role_uac.php',
    'role/uac/manage' => 'controller/c_role_uac.php',
    'user/mass-reset' => 'view/mass_reset.php',
    'user/mass-reset/manage' => 'controller/c_mass_reset.php',
    
    // UAC routes
    'uac' => 'view/uac.php',
    
    // API routes (for AJAX calls)
    'api/employees' => 'controller/c_employee.php',
    'api/kpi' => 'controller/c_kpi.php',

    // additional routes
    'controller/get_rule.php' => 'controller/get_rule.php',
    'controller/c_ccs_rules.php' => 'controller/c_ccs_rules.php',
    'public/dist/js/ccs_viewer.js' => 'public/dist/js/ccs_viewer.js',
    'controller/get_project_employees.php' => 'controller/get_project_employees.php',
    'controller/get_employee_role.php' => 'controller/get_employee_role.php',
    'controller/get_rules.php' => 'controller/get_rules.php',
    'controller/c_project_namelist.php' => 'controller/c_project_namelist.php',
    
    // KPI Individual routes
    'kpi/individual' => 'view/kpi_individual.php',
    'controller/c_kpi_individual.php' => 'controller/c_kpi_individual.php',
    'controller/c_export_kpi_individual.php' => 'controller/c_export_kpi_individual.php',
    'controller/c_import_kpi_individual.php' => 'controller/c_import_kpi_individual.php',
    'controller/get_project_kpi.php' => 'controller/get_project_kpi.php',
    'controller/get_project_queues.php' => 'controller/get_project_queues.php',
    
    // KPI Individual API routes
    'kpi/individual/save' => 'controller/c_kpi_individual.php',
    'kpi/individual/export' => 'controller/c_export_kpi_individual.php',
    'kpi/individual/import' => 'controller/c_import_kpi_individual.php',
    'project/kpi' => 'controller/get_project_kpi.php',
    'project/queues' => 'controller/get_project_queues.php',
    'project/employees' => 'controller/get_project_employees.php',
    'kpi/individual/process' => 'controller/c_kpi_individual_process.php',
    
    // User Settings route
    'user/settings' => 'view/user_settings.php',
    'user/settings/update' => 'controller/c_user_settings.php',
    
    // KPI Delete route
    'kpi/delete' => 'controller/c_viewer_del.php',
    'employee/details' => 'controller/get_employee_details.php',
];