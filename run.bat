@echo off
echo ==================================================
echo  CoreCart - Local Development Server
echo ==================================================
echo.

:: Check if Composer is installed
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Composer not found!
    echo Download it from: https://getcomposer.org
    pause
    exit /b 1
)

:: Install dependencies if vendor folder is missing
if not exist "vendor" (
    echo [INFO] Installing dependencies via Composer...
    call composer install
    echo.
)

echo [OK] Starting server at http://localhost:8000
echo [OK] Press Ctrl+C to stop
echo ==================================================
php -S localhost:8000 system/engine/router_builtin.php
