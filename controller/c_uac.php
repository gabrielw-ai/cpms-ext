<?php
class UserAccessControl {
    public $userPrivilege;
    
    public function __construct($privilege) {
        $this->userPrivilege = $privilege;
    }
    
    public function canAccessUserSettings() {
        return true;
    }
    
    public function hasAccess($page) {
        // Special check for role UAC - only privilege 6 can access
        if (($page === 'role_uac.php' || $page === 'role/uac') && $this->userPrivilege !== 6) {
            return false;
        }

        if ($this->userPrivilege === 6) {
            return true; // Full access for privilege 6
        }
        
        switch ($this->userPrivilege) {
            case 1: // Basic user - most restricted
                $allowedPages = [
                    'kpi_individual.php',
                    'user_settings.php',
                    'ccs_viewer.php'
                ];
                return in_array($page, $allowedPages);
                
            case 2: // Team Leader
                $allowedPages = [
                    'kpi_viewer.php',
                    'kpi_individual.php',
                    'ccs_viewer.php',
                    'user_settings.php'
                ];
                return in_array($page, $allowedPages);

            case 3: // Project-specific KPI manager
                $allowedPages = [
                    'tbl_metrics.php',
                    'user_settings.php',
                    'kpi_viewer.php',
                    'kpi_individual.php',
                    'employee_list.php',
                    'ccs_viewer.php'
                ];
                return in_array($page, $allowedPages);

            case 4: // Extended access manager
                $allowedPages = [
                    'tbl_metrics.php',
                    'kpi_viewer.php',
                    'kpi_individual.php',
                    'employee_list.php',
                    'ccs_rules_mgmt.php',
                    'ccs_viewer.php',
                    'user_settings.php'
                ];
                return in_array($page, $allowedPages);
                
            default:
                return true;
        }
    }
    
    public function canExport() {
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2]);
    }
    
    public function canImport() {
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2]);
    }
    
    public function canDelete() {
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2]);
    }
    
    public function canEdit() {
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2]);
    }
    
    public function canAdd() {
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2]);
    }
    
    public function getProjectFilter() {
        if ($this->userPrivilege === 6) {
            return ""; // No filter for privilege 6
        }
        
        if ($this->userPrivilege === 1) {
            return " WHERE project IN (SELECT project FROM employee_active WHERE nik = ?) AND nik = ?";
        } elseif ($this->userPrivilege === 2) {
            return " WHERE project IN (SELECT project FROM employee_active WHERE nik = ?)";
        } elseif ($this->userPrivilege === 3 || $this->userPrivilege === 4) {
            // Both privilege 3 and 4 see only their project
            return " WHERE project IN (SELECT project FROM employee_active WHERE nik = ?)";
        }
        return "";
    }
    
    public function showFullMenu() {
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2, 3, 4]);
    }
    
    public function getMenuItems() {
        $menuItems = [];

        if ($this->userPrivilege === 6) {
            // Full menu for privilege 6 with submenus
            $menuItems = [
                [
                    'url' => 'dashboard',
                    'icon' => 'fas fa-tachometer-alt',
                    'text' => 'Dashboard'
                ],
                [
                    'text' => 'KPI Management',
                    'icon' => 'fas fa-chart-line',
                    'url' => '#',
                    'submenu' => [
                        ['url' => 'kpi/metrics', 'icon' => 'far fa-circle', 'text' => 'KPI Metrics'],
                        ['url' => 'kpi/viewer', 'icon' => 'far fa-circle', 'text' => 'KPI Viewer'],
                        ['url' => 'kpi/individual', 'icon' => 'far fa-circle', 'text' => 'KPI Individual'],
                        ['url' => 'kpi/charts', 'icon' => 'far fa-circle', 'text' => 'KPI Chart Generator']
                    ]
                ],
                [
                    'url' => 'employees',
                    'icon' => 'fas fa-users',
                    'text' => 'Employee List'
                ],
                [
                    'text' => 'CCS Rules Management',
                    'icon' => 'fas fa-cogs',
                    'url' => '#',
                    'submenu' => [
                        ['url' => 'ccs/rules', 'icon' => 'far fa-circle', 'text' => 'Add CCS Rules'],
                        ['url' => 'ccs/viewer', 'icon' => 'far fa-circle', 'text' => 'CCS Rules Viewer']
                    ]
                ],
                [
                    'url' => 'projects',
                    'icon' => 'fas fa-project-diagram',
                    'text' => 'Project Namelist'
                ],
                [
                    'text' => 'User Settings',
                    'icon' => 'fas fa-user-cog',
                    'url' => '#',
                    'submenu' => [
                        ['url' => 'roles', 'icon' => 'fas fa-user-tag', 'text' => 'Role Management'],
                        ['url' => 'role/uac', 'icon' => 'fas fa-user-shield', 'text' => 'Role UAC'],
                        ['url' => 'user/mass-reset', 'icon' => 'fas fa-key', 'text' => 'Mass Reset Password'],
                        ['url' => 'user/settings', 'icon' => 'fas fa-cog', 'text' => 'My Settings']
                    ]
                ]
            ];
        } else {
            switch ($this->userPrivilege) {
                case 1:
                    $menuItems = [
                        ['url' => 'kpi/individual', 'icon' => 'fas fa-chart-line', 'text' => 'KPI Individual'],
                        ['url' => 'ccs/viewer', 'icon' => 'fas fa-eye', 'text' => 'CCS Rules Viewer'],
                        ['url' => 'user/settings', 'icon' => 'fas fa-user-cog', 'text' => 'User Settings']
                    ];
                    break;
                
                case 2:
                    $menuItems = [
                        [
                            'text' => 'KPI Management',
                            'icon' => 'fas fa-chart-line',
                            'url' => '#',
                            'submenu' => [
                                ['url' => 'kpi/viewer', 'icon' => 'far fa-circle', 'text' => 'KPI Viewer'],
                                ['url' => 'kpi/individual', 'icon' => 'far fa-circle', 'text' => 'KPI Individual']
                            ]
                        ],
                        ['url' => 'ccs/viewer', 'icon' => 'fas fa-eye', 'text' => 'CCS Rules Viewer'],
                        ['url' => 'user/settings', 'icon' => 'fas fa-user-cog', 'text' => 'User Settings']
                    ];
                    break;

                case 3:
                    $menuItems = [
                        [
                            'text' => 'KPI Management',
                            'icon' => 'fas fa-chart-line',
                            'url' => '#',
                            'submenu' => [
                                ['url' => 'kpi/metrics', 'icon' => 'far fa-circle', 'text' => 'KPI Metrics'],
                                ['url' => 'kpi/viewer', 'icon' => 'far fa-circle', 'text' => 'KPI Viewer'],
                                ['url' => 'kpi/individual', 'icon' => 'far fa-circle', 'text' => 'KPI Individual']
                            ]
                        ],
                        ['url' => 'employees', 'icon' => 'fas fa-users', 'text' => 'Employee List'],
                        ['url' => 'ccs/viewer', 'icon' => 'fas fa-eye', 'text' => 'CCS Rules Viewer'],
                        ['url' => 'user/settings', 'icon' => 'fas fa-user-cog', 'text' => 'User Settings']
                    ];
                    break;

                case 4:
                    $menuItems = [
                        [
                            'text' => 'KPI Management',
                            'icon' => 'fas fa-chart-line',
                            'url' => '#',
                            'submenu' => [
                                ['url' => 'kpi/metrics', 'icon' => 'far fa-circle', 'text' => 'KPI Metrics'],
                                ['url' => 'kpi/viewer', 'icon' => 'far fa-circle', 'text' => 'KPI Viewer'],
                                ['url' => 'kpi/individual', 'icon' => 'far fa-circle', 'text' => 'KPI Individual']
                            ]
                        ],
                        ['url' => 'employees', 'icon' => 'fas fa-users', 'text' => 'Employee List'],
                        [
                            'text' => 'CCS Management',
                            'icon' => 'fas fa-shield-alt',
                            'url' => '#',
                            'submenu' => [
                                ['url' => 'ccs/viewer', 'icon' => 'far fa-circle', 'text' => 'CCS Rules Viewer'],
                                ['url' => 'ccs/rules', 'icon' => 'far fa-circle', 'text' => 'CCS Rules Management']
                            ]
                        ],
                        [
                            'text' => 'User Settings',
                            'icon' => 'fas fa-user-cog',
                            'url' => '#',
                            'submenu' => [
                                ['url' => 'roles', 'icon' => 'fas fa-user-tag', 'text' => 'Role Management'],
                                ['url' => 'user/mass-reset', 'icon' => 'fas fa-key', 'text' => 'Mass Reset Password'],
                                ['url' => 'user/settings', 'icon' => 'fas fa-cog', 'text' => 'My Settings']
                            ]
                        ]
                    ];
                    break;
            }
        }
        
        return $menuItems;
    }
    
    public function getUserProject($conn, $nik) {
        try {
            $stmt = $conn->prepare("SELECT project FROM employee_active WHERE nik = ? LIMIT 1");
            $stmt->execute([$nik]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['project'] : null;
        } catch (PDOException $e) {
            error_log("Error getting user project: " . $e->getMessage());
            return null;
        }
    }
    
    public function canManageRules() {
        // Only privilege level 6 and above can manage CCS rules
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2]);
    }
    
    public function canViewRules() {
        // All privilege levels can view rules
        return true;
    }
    
    public function getEmployeeListAccess($conn, $userNik) {
        if ($this->userPrivilege === 6) {
            return ""; // No restrictions for super users
        }
        
        // Get user's project
        $project = $this->getUserProject($conn, $userNik);
        
        if ($this->userPrivilege === 3 || $this->userPrivilege === 4) {
            // For privilege level 3 and 4, show only employees in their project
            return " AND project = '$project'";
        } elseif ($this->userPrivilege === 2) {
            // Team leaders can see all employees in their project
            return " AND project = '$project'";
        } elseif ($this->userPrivilege === 1) {
            // Basic users can only see their own data
            return " AND nik = '$userNik'";
        }
        
        return "";
    }
    
    public function canAccessCCSRules() {
        // Check if user can access CCS rules management
        return $this->userPrivilege === 6 || !in_array($this->userPrivilege, [1, 2]);
    }
    
    public function getRuleAccessFilter($conn, $userNik) {
        if ($this->userPrivilege === 6) {
            return ""; // No restrictions for super users
        }

        $project = $this->getUserProject($conn, $userNik);
        
        if ($this->userPrivilege === 1) {
            // Basic users can only see their own rules
            return " AND cr.nik = '$userNik' AND cr.project = '$project'";
        } elseif ($this->userPrivilege === 2) {
            // Get user's role and its privilege level
            $stmt = $conn->prepare("
                SELECT ea.role, rm.privileges 
                FROM employee_active ea
                JOIN role_mgmt rm ON ea.role = rm.role
                WHERE ea.NIK = ?
            ");
            $stmt->execute([$userNik]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $userRole = $result['role'];
            $userRolePrivilege = $result['privileges'];

            return " AND (
                -- For records with roles that have privilege 2, only show their own
                (cr.role IN (
                    SELECT ea2.role 
                    FROM employee_active ea2 
                    JOIN role_mgmt rm2 ON ea2.role = rm2.role 
                    WHERE rm2.privileges = 2
                ) AND cr.nik = '$userNik' AND cr.project = '$project')
                OR 
                -- For records with roles that have privilege 1, show all in their project
                (cr.role IN (
                    SELECT rm3.role 
                    FROM role_mgmt rm3 
                    WHERE rm3.privileges = 1
                ) AND cr.project = '$project')
            )";
        } elseif ($this->userPrivilege === 3 || $this->userPrivilege === 4) {
            // For privilege level 3 and 4, show only their project and roles with privileges 1, 2, or 3
            return " AND cr.project = '$project' AND cr.role IN (
                SELECT rm.role 
                FROM role_mgmt rm 
                WHERE rm.privileges IN (1, 2, 3)
            )";
        }
        
        return "";
    }
}  