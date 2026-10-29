@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Expense Behaviour Analytical Platform (EBAP) - Push to GitHub
color 0A

:: ============================================================================
:: Expense Behaviour Analytical Platform (EBAP) - Universal GitHub Push Utility
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

cd /d "%PROJECT_DIR%"

echo ============================================================================
echo    EXPENSE BEHAVIOUR ANALYTICAL PLATFORM - GITHUB PUSH ASSISTANT
echo ============================================================================
echo.
echo  [INFO] Project Directory : %PROJECT_DIR%

:: 2. Verify Git is Installed
where git >nul 2>&1
if errorlevel 1 (
    color 0C
    echo  [ERROR] Git is not installed or not in your system PATH.
    echo          Download Git from: https://git-scm.com/download/win
    pause
    exit /b 1
)

:: 3. Ensure Git Repository & Main Branch are Initialized
if not exist "%PROJECT_DIR%\.git" (
    echo  [INIT] Initializing local Git repository...
    git init
)
git branch -M main >nul 2>&1

:: 4. Security Check: Never allow .env or sensitive local files into Git index
git rm --cached .env >nul 2>&1

:: 5. Ensure Git Author Identity is Set
for /f "delims=" %%i in ('git config user.name 2^>nul') do set "GIT_USER=%%i"
for /f "delims=" %%i in ('git config user.email 2^>nul') do set "GIT_EMAIL=%%i"

if "%GIT_USER%"=="" (
    echo.
    set /p "INPUT_NAME=Enter your Git Name [default: EBAP Developer]: "
    if "!INPUT_NAME!"=="" set "INPUT_NAME=EBAP Developer"
    git config user.name "!INPUT_NAME!"
)
if "%GIT_EMAIL%"=="" (
    echo.
    set /p "INPUT_EMAIL=Enter your Git Email [default: developer@ebap.local]: "
    if "!INPUT_EMAIL!"=="" set "INPUT_EMAIL=developer@ebap.local"
    git config user.email "!INPUT_EMAIL!"
)

:: 6. Show Repository Status & Commit Any Pending Changes
echo.
echo  [STEP 1] Checking for uncommitted file changes...
git status --short

set "HAS_CHANGES="
for /f "delims=" %%i in ('git status --porcelain 2^>nul') do set "HAS_CHANGES=1"

if defined HAS_CHANGES (
    echo.
    echo  [INFO] New or modified files detected.
    if /I "%~1"=="--check-only" (
        echo  [CHECK] Dry-run check completed.
        exit /b 0
    )
    set "COMMIT_MSG="
    set /p "COMMIT_MSG=Enter commit message [Press Enter for default]: "
    if "!COMMIT_MSG!"=="" set "COMMIT_MSG=chore: update Expense Behaviour Analytical Platform files"
    echo  [WAIT] Staging and committing all project files...
    git add -A
    git rm --cached .env >nul 2>&1
    git commit -m "!COMMIT_MSG!"
    echo  [ OK ] Changes committed to branch 'main'.
) else (
    echo  [ OK ] Working tree is clean. All project files are already committed.
    if /I "%~1"=="--check-only" (
        echo  [CHECK] Dry-run check completed.
        exit /b 0
    )
)

:: 7. Check or Configure Remote GitHub Repository URL
echo.
echo  [STEP 2] Checking GitHub remote repository configuration...
set "REMOTE_URL="
for /f "delims=" %%i in ('git remote get-url origin 2^>nul') do set "REMOTE_URL=%%i"

if not "%REMOTE_URL%"=="" (
    echo  [ OK ] Current GitHub Remote: %REMOTE_URL%
    echo.
    set "CHANGE_REMOTE="
    set /p "CHANGE_REMOTE=Press [Enter] to push to this repository, or type [C] to change URL: "
    if /I "!CHANGE_REMOTE!"=="C" set "REMOTE_URL="
)

:ASK_REMOTE
if "%REMOTE_URL%"=="" (
    echo.
    echo  --------------------------------------------------------------------------
    echo   How to get your GitHub Repository URL:
    echo   1. Go to https://github.com/new
    echo   2. Repository name: expense-behaviour-analytics
    echo   3. Keep it empty [do not add README/license] and click 'Create repository'
    echo   4. Copy the HTTPS URL: https://github.com/YOUR-USERNAME/expense-behaviour-analytics.git
    echo  --------------------------------------------------------------------------
    echo.
    set /p "NEW_REMOTE=Paste your GitHub Repository URL here [or type O to open GitHub in browser]: "
    if /I "!NEW_REMOTE!"=="O" (
        start "" "https://github.com/new"
        goto ASK_REMOTE
    )
    if "!NEW_REMOTE!"=="" (
        echo  [WARN] Repository URL cannot be empty.
        goto ASK_REMOTE
    )
    git remote remove origin >nul 2>&1
    git remote add origin "!NEW_REMOTE!"
    set "REMOTE_URL=!NEW_REMOTE!"
    echo  [ OK ] Remote 'origin' set to: !REMOTE_URL!
)

:: 8. Push Branch 'main' to GitHub
echo.
echo  [STEP 3] Pushing all commits and files to GitHub ('origin/main')...
echo.
git push -u origin main

if errorlevel 1 (
    color 0E
    echo.
    echo  ==========================================================================
    echo   [NOTICE] Standard push encountered an issue.
    echo   Common causes:
    echo     1. The remote GitHub repo already has an initial README or License commit.
    echo     2. You need to sign in via the Git Credential Manager pop-up window.
    echo     3. The repository URL does not exist yet on your GitHub account.
    echo  ==========================================================================
    echo.
    echo   Choose how to resolve:
    echo     [1] Pull and merge remote changes, then push [Recommended if repo has README]
    echo     [2] Force push local project to overwrite remote [git push -u origin main --force]
    echo     [3] Change GitHub Repository URL
    echo     [0] Exit
    echo.
    set /p "RETRY_OPT=Select option [0-3]: "
    if "!RETRY_OPT!"=="1" (
        git pull origin main --rebase --allow-unrelated-histories
        git push -u origin main
    )
    if "!RETRY_OPT!"=="2" (
        git push -u origin main --force
    )
    if "!RETRY_OPT!"=="3" (
        set "REMOTE_URL="
        goto ASK_REMOTE
    )
) else (
    color 0A
    echo.
    echo ============================================================================
    echo   [SUCCESS] ALL FILES SUCCESSFULLY PUSHED TO GITHUB!
    echo ============================================================================
    echo   Repository : %REMOTE_URL%
    echo   Branch     : main
    echo ============================================================================
    echo.
    set "OPEN_GH="
    set /p "OPEN_GH=Open your GitHub repository in the browser now? [Y/N]: "
    if /I "!OPEN_GH!"=="Y" (
        set "BROWSER_URL=%REMOTE_URL:.git=%"
        start "" "!BROWSER_URL!"
    )
)

echo.
pause
exit /b 0
