@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Expense Behaviour Analytical Platform (EBAP) - Launcher
color 0B

:: ============================================================================
:: Expense Behaviour Analytical Platform (EBAP) - Universal One-Click Launcher
:: Can be run from the project folder OR copied/placed anywhere on your PC.
:: ============================================================================

:: 1. Resolve Project Directory (works even if this .bat file is copied to Desktop)
set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

if exist "%SCRIPT_DIR%\index.php" (
    set "PROJECT_DIR=%SCRIPT_DIR%"
) else if exist "C:\Users\sargu\.gemini\antigravity\scratch\expense-behaviour-analytics\index.php" (
    set "PROJECT_DIR=C:\Users\sargu\.gemini\antigravity\scratch\expense-behaviour-analytics"
) else if exist "C:\xampp\htdocs\expense-behaviour-analytics\index.php" (
    set "PROJECT_DIR=C:\xampp\htdocs\expense-behaviour-analytics"
) else (
    color 0C
    echo [ERROR] Could not locate the EBAP project directory.
    pause
    exit /b 1
)

:: 2. Configure XAMPP & Tool Paths
set "XAMPP_DIR=C:\xampp"
set "MYSQLD_EXE=%XAMPP_DIR%\mysql\bin\mysqld.exe"
set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
set "MYSQL_INI=%XAMPP_DIR%\mysql\bin\my.ini"
set "HTTPD_EXE=%XAMPP_DIR%\apache\bin\httpd.exe"
set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
set "HTDOCS_LINK=%XAMPP_DIR%\htdocs\expense-behaviour-analytics"

if not exist "%PHP_EXE%" (
    set "PHP_EXE=php"
)

echo ============================================================================
echo    EXPENSE BEHAVIOUR ANALYTICAL PLATFORM (EBAP) - LOCALHOST LAUNCHER
echo ============================================================================
echo.
echo  [INFO] Project Path : %PROJECT_DIR%

:: 3. Ensure XAMPP htdocs directory junction exists for Apache
if exist "%XAMPP_DIR%\htdocs" (
    if not exist "%HTDOCS_LINK%\index.php" (
        echo  [INIT] Creating XAMPP htdocs link...
        mklink /J "%HTDOCS_LINK%" "%PROJECT_DIR%" >nul 2>&1
    )
)

:: 4. Start MySQL Database Server (Port 3306) if not running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo  [WAIT] Starting MySQL Database Server on port 3306...
    if exist "%MYSQLD_EXE%" (
        start "" /B "%MYSQLD_EXE%" --defaults-file="%MYSQL_INI%" --standalone >nul 2>&1
        timeout /t 3 /nobreak >nul
    ) else (
        echo  [WARN] XAMPP MySQL executable not found at %MYSQLD_EXE%
    )
) else (
    echo  [ OK ] MySQL Database Server is already running.
)

:: 5. Ensure Database & Seed Data Exist
if exist "%MYSQL_EXE%" (
    "%MYSQL_EXE%" -u root -e "USE expense_behaviour_analytics;" >nul 2>&1
    if errorlevel 1 (
        echo  [INIT] Initializing database schema and sample data...
        "%MYSQL_EXE%" -u root < "%PROJECT_DIR%\database\schema.sql" >nul 2>&1
        "%MYSQL_EXE%" -u root < "%PROJECT_DIR%\database\seed.sql" >nul 2>&1
        echo  [ OK ] Database initialized with default categories and demo accounts.
    ) else (
        echo  [ OK ] Database 'expense_behaviour_analytics' is ready.
    )
)

:: 6. Start XAMPP Apache Server (Port 80) if not running
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 (
    if exist "%HTTPD_EXE%" (
        echo  [WAIT] Starting XAMPP Apache Web Server on port 80...
        start "" /B "%HTTPD_EXE%" >nul 2>&1
        timeout /t 2 /nobreak >nul
    )
) else (
    echo  [ OK ] XAMPP Apache Web Server is already running.
)

:: 7. Start Loopback-Isolated PHP Server (127.0.0.1:8000) if not running
netstat -ano | findstr /R /C:":8000 .*LISTENING" >nul 2>&1
if errorlevel 1 (
    echo  [WAIT] Starting Safe Loopback PHP Server on http://127.0.0.1:8000 ...
    start "" /B "%PHP_EXE%" -S 127.0.0.1:8000 -t "%PROJECT_DIR%" >nul 2>&1
    timeout /t 1 /nobreak >nul
) else (
    echo  [ OK ] Safe Loopback PHP Server on 127.0.0.1:8000 is already running.
)

set "APP_URL=http://localhost/expense-behaviour-analytics/"
set "SAFE_URL=http://127.0.0.1:8000/"

echo.
echo ============================================================================
echo   SERVICES ARE LIVE AND READY!
echo ============================================================================
echo.
echo   Primary URL (Apache)   : %APP_URL%
echo   Loopback URL (Safe)    : %SAFE_URL%
echo   Login Page             : %APP_URL%login.php
echo   phpMyAdmin             : http://localhost/phpmyadmin/
echo.
echo   Demo Account (Full)    : demo@ebap.local    /  Demo@123
echo   Student Account (Empty): student@ebap.local /  Student@123
echo ============================================================================
echo.

:: If called with --no-browser, exit cleanly after starting services
if /I "%~1"=="--no-browser" exit /b 0

:: 8. Open the application in the default browser automatically
echo  [OPEN] Launching %APP_URL% in your default browser...
start "" "%APP_URL%"

if /I "%~1"=="--auto-exit" exit /b 0

:MENU
echo.
echo  Select an action:
echo    [1] Open Main Application (%APP_URL%)
echo    [2] Open Loopback Server  (%SAFE_URL%)
echo    [3] Open Login Page       (%APP_URL%login.php)
echo    [4] Open phpMyAdmin       (http://localhost/phpmyadmin/)
echo    [5] Run Automated Verification Suite (57 Tests)
echo    [6] Stop All EBAP Servers and Exit
echo    [0] Close Launcher Window (Keep Servers Running)
echo.
set /p "CHOICE=Enter choice [0-6]: "

if "%CHOICE%"=="1" (
    start "" "%APP_URL%"
    goto MENU
)
if "%CHOICE%"=="2" (
    start "" "%SAFE_URL%"
    goto MENU
)
if "%CHOICE%"=="3" (
    start "" "%APP_URL%login.php"
    goto MENU
)
if "%CHOICE%"=="4" (
    start "" "http://localhost/phpmyadmin/"
    goto MENU
)
if "%CHOICE%"=="5" (
    echo.
    "%PHP_EXE%" "%PROJECT_DIR%\tests\run_tests.php"
    echo.
    goto MENU
)
if "%CHOICE%"=="6" (
    echo.
    echo  [STOP] Stopping Apache, PHP Loopback Server, and MySQL...
    taskkill /F /IM httpd.exe >nul 2>&1
    taskkill /F /IM php.exe >nul 2>&1
    if exist "%XAMPP_DIR%\mysql\bin\mysqladmin.exe" (
        "%XAMPP_DIR%\mysql\bin\mysqladmin.exe" -u root shutdown >nul 2>&1
    ) else (
        taskkill /F /IM mysqld.exe >nul 2>&1
    )
    echo  [ OK ] All services stopped cleanly.
    timeout /t 2 >nul
    exit /b 0
)
if "%CHOICE%"=="0" (
    exit /b 0
)

goto MENU
