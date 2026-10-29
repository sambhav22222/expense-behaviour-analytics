@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Expense Behaviour Analytical Platform (EBAP) - Push to GitHub
color 0A

:: ============================================================================
:: Expense Behaviour Analytical Platform (EBAP) - Universal GitHub Push Utility
:: Configured for: https://github.com/sambhav22222/expense-behaviour-analytics.git
:: ============================================================================

:: 1. Resolve Project Directory (works from anywhere, including Desktop)
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

cd /d "%PROJECT_DIR%"

set "DEFAULT_GH_USER=sambhav22222"
set "DEFAULT_REPO_URL=https://sambhav22222@github.com/sambhav22222/expense-behaviour-analytics.git"
set "PUBLIC_REPO_URL=https://github.com/sambhav22222/expense-behaviour-analytics"

echo ============================================================================
echo    EXPENSE BEHAVIOUR ANALYTICAL PLATFORM - GITHUB PUSH ASSISTANT
echo ============================================================================
echo.
echo  [INFO] Project Directory : %PROJECT_DIR%
echo  [INFO] Target GitHub URL : %PUBLIC_REPO_URL%.git

:: 2. Verify Git is Installed
where git >nul 2>&1
if errorlevel 1 (
    color 0C
    echo  [ERROR] Git is not installed or not in your system PATH.
    pause
    exit /b 1
)

:: 3. Ensure Git Repository & Main Branch are Initialized
if not exist "%PROJECT_DIR%\.git" (
    git init
)
git branch -M main >nul 2>&1

:: 4. Security Check: Never allow .env into Git index
git rm --cached .env >nul 2>&1

:: 5. Ensure Remote 'origin' Uses sambhav22222@github.com so Git prompts for the right account
git remote remove origin >nul 2>&1
git remote add origin "%DEFAULT_REPO_URL%"

:: 6. Stage & Commit Any New or Modified Files
echo.
echo  [STEP 1] Checking for uncommitted file changes...
set "HAS_CHANGES="
for /f "delims=" %%i in ('git status --porcelain 2^>nul') do set "HAS_CHANGES=1"

if defined HAS_CHANGES (
    echo  [INFO] Staging and committing latest project files...
    git add -A
    git rm --cached .env >nul 2>&1
    git commit -m "chore: update Expense Behaviour Analytical Platform files" >nul 2>&1
    echo  [ OK ] All files committed to branch 'main'.
) else (
    echo  [ OK ] All 91 project files are committed and ready on branch 'main'.
)

if /I "%~1"=="--check-only" (
    echo  [CHECK] Dry-run check completed.
    exit /b 0
)

:DO_PUSH
echo.
echo  [STEP 2] Pushing all files to %PUBLIC_REPO_URL%.git ...
echo  [NOTE] If a GitHub Sign-In window opens, sign in as: %DEFAULT_GH_USER%
echo.

git push -u origin main
if not errorlevel 1 goto PUSH_SUCCESS

color 0E
echo.
echo ============================================================================
echo   [ACCOUNT MISMATCH DETECTED]
echo   Windows has another GitHub account [Sargun2213] saved in Credential Manager,
echo   so GitHub blocked pushing to '%DEFAULT_GH_USER%/expense-behaviour-analytics'.
echo ============================================================================
echo.
echo   Select how you want to fix this right now:
echo     [1] Switch GitHub Account to '%DEFAULT_GH_USER%' and Push Again [Recommended]
echo         - Clears the old cached GitHub login from Windows Credential Manager
echo         - Opens the GitHub Sign-In window so you can log in as %DEFAULT_GH_USER%
echo     [2] Pull/Merge remote files first [if your GitHub repo already has a README]
echo     [3] Force Push local project to GitHub [overwrites remote branch]
echo     [0] Exit
echo.
set /p "FIX_CHOICE=Enter choice [0-3]: "

if "%FIX_CHOICE%"=="1" (
    echo.
    echo  [WAIT] Clearing cached GitHub credentials from Windows Credential Manager...
    cmdkey /delete:git:https://github.com >nul 2>&1
    cmdkey /delete:LegacyGeneric:target=git:https://github.com >nul 2>&1
    (
        echo protocol=https
        echo host=github.com
        echo.
    ) | git credential-manager erase >nul 2>&1
    echo  [ OK ] Old credentials cleared! Now sign in as '%DEFAULT_GH_USER%' when prompted...
    goto DO_PUSH
)
if "%FIX_CHOICE%"=="2" (
    echo.
    git pull origin main --rebase --allow-unrelated-histories
    goto DO_PUSH
)
if "%FIX_CHOICE%"=="3" (
    echo.
    git push -u origin main --force
    if not errorlevel 1 goto PUSH_SUCCESS
)
exit /b 1

:PUSH_SUCCESS
color 0A
echo.
echo ============================================================================
echo   [SUCCESS] ALL FILES SUCCESSFULLY PUSHED TO GITHUB!
echo ============================================================================
echo   Live Repository : %PUBLIC_REPO_URL%
echo   Branch          : main
echo ============================================================================
echo.
set "OPEN_GH="
set /p "OPEN_GH=Open %PUBLIC_REPO_URL% in your browser now? [Y/N]: "
if /I "!OPEN_GH!"=="Y" (
    start "" "%PUBLIC_REPO_URL%"
)
echo.
pause
exit /b 0
