@echo off
echo ================================================
echo PaperVista - PHP Configuration Setup
echo ================================================
echo.
echo This script will help you configure PHP for large PDF handling.
echo.
echo IMPORTANT: You need to manually edit php.ini
echo.
echo Steps:
echo 1. Open Laragon
echo 2. Right-click Laragon tray icon
echo 3. Select: PHP ^> php.ini
echo 4. Find and change these values:
echo.
echo    memory_limit = 1024M
echo    max_execution_time = 300
echo    upload_max_filesize = 100M
echo    post_max_size = 100M
echo.
echo 5. Save the file
echo 6. Click: Laragon ^> Restart All
echo.
echo ================================================
echo.
pause
