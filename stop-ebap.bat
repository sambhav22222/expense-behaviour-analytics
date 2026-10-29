@echo off
setlocal EnableExtensions
title Expense Behaviour Analytical Platform (EBAP) - Stop Services
color 0E

echo ============================================================================
echo    STOPPING EXPENSE BEHAVIOUR ANALYTICAL PLATFORM (EBAP) SERVICES
echo ============================================================================
echo.

echo  [1/3] Stopping XAMPP Apache Server (httpd.exe)...
taskkill /F /IM httpd.exe >nul 2>&1
if errorlevel 1 (
    echo        Apache was not running.
) else (
    echo        Apache stopped.
)

echo  [2/3] Stopping PHP Loopback Server (php.exe)...
taskkill /F /IM php.exe >nul 2>&1
if errorlevel 1 (
    echo        PHP server was not running.
) else (
    echo        PHP server stopped.
)

echo  [3/3] Shutting down MySQL Server (mysqld.exe)...
if exist "C:\xampp\mysql\bin\mysqladmin.exe" (
    "C:\xampp\mysql\bin\mysqladmin.exe" -u root shutdown >nul 2>&1
    echo        MySQL shutdown signal sent cleanly.
) else (
    taskkill /F /IM mysqld.exe >nul 2>&1
    echo        MySQL stopped.
)

echo.
echo  [ OK ] All EBAP local servers have been stopped.
if /I not "%~1"=="--silent" (
    timeout /t 3
)
exit /b 0
