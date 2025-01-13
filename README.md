# CPMS (Centralized Performance Management Systems) Beta V1

## About

CPMS stands for Centralized Performance Management Systems Beta V1.

## Project Details

1. **IDE**: VSCode with full assistance from Claude Sonnet 3.5 Pro.
2. **Language**: Written in native PHP without the use of frameworks.
3. **Integrations**: Integrates AdminLTE & PHPSpreadsheet.
4. **Development**: Design, concept, bug testing, and fixes fully created by the repository owner with over 200++ hours of involvement.

## Tested Under

- **Operating Systems**:
  - Ubuntu Desktop 22.04
  - AlmaLinux (as a replacement for CentOS)

- **Software**:
  - PHP 8.2
  - httpd as the main web server / PHP-FPM
  - MariaDB

## Requirements

1. PHP 7.xx or higher
2. httpd is required instead of NGINX since we are using `.htaccess` to manage routing easily.
3. MariaDB
4. Composer to install PHPSpreadsheet

## Project Structure

```
cpms/
├── controller/
├── view/
├── public/
│   ├── dist/
│   └── js/
├── uploads/
├── index.php
├── .htaccess
└── README.md
```

+ **Project Structure Definition:**
 
 1. **Controller**: Handles all actions including updates and deletes.
    - `c_xxxx`: Controllers for views to handle the deletes, updates, etc.
    - `get_xxx`: Controllers to fetch data or queries from the database based on user selection.
2. **Routes & Routing**: Manages the application's routing structure.
 3. **main_navbar.php**: Serves as the overall navigation bar across the application.
 
+ ## How to Install
 
1. Directly clone the repository using `git clone <repo-url>`.
2. Open `site_config.php` and configure the sub-directory and database settings.
3. **Mandatory**: In `.htaccess`, edit the `RewriteBase /sub-folder` accordingly to avoid routing errors.
 
+ ## What to Add Next
1. Year db fields
2. Chart generator for some of user 



