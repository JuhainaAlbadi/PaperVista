@echo off
REM Admin Setup Script for Windows

echo ================================
echo PaperVista Admin Setup
echo ================================
echo.

cd /d "%~dp0"

echo Current admin status:
php includes/admin_check.php

echo.
echo.
echo OPTIONS:
echo 1 = Set new admin password
echo 2 = Create new admin user
echo 3 = Check database
echo 4 = Test admin access
echo.

set /p choice="Select option (1-4): "

if "%choice%"=="1" (
    echo.
    set /p password="Enter new password for admin@university.edu: "
    
    php includes/admin_set_password.php "%password%"
    
    echo.
    echo You can now login at: http://localhost/php_summarizer/login.php
    echo Email: admin@university.edu
    echo Password: %password%
)

if "%choice%"=="2" (
    echo.
    set /p new_email="Enter new admin email: "
    set /p first_name="Enter first name: "
    set /p last_name="Enter last name: "
    set /p new_password="Enter password: "
    
    php includes/admin_create_user.php "%new_email%" "%first_name%" "%last_name%" "%new_password%"
)

if "%choice%"=="3" (
    echo.
    php includes/admin_check_db.php
)

if "%choice%"=="4" (
    php test_admin_access.php
)

echo.
pause
