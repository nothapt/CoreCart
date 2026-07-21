@echo off
chcp 65001 >nul
echo  CoreCart - One-Click Shop Setup
echo.

:: Check if portable PHP is already downloaded
if not exist "php_core" (
    echo [1/3] Downloading PHP 8.4 for Windows...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/php-8.4.10-Win32-vs16-x64.zip' -OutFile 'php.zip'"

    echo [2/3] Extracting PHP...
    powershell -Command "Expand-Archive -Path 'php.zip' -DestinationPath 'php_core' -Force"
    del php.zip

    echo [3/3] Configuring PHP extensions...
    copy php_core\php.ini-development php_core\php.ini >nul
    echo extension=pdo_mysql >> php_core\php.ini
    echo extension=curl >> php_core\php.ini
    echo extension=mbstring >> php_core\php.ini
    echo extension=intl >> php_core\php.ini
    echo.
) else (
    echo [OK] PHP already installed.
)

echo  CoreCart Shop is ready!
echo  Open in browser: http://localhost:8000
echo  Press Ctrl+C to stop the server

php_core\php.exe -S localhost:8000 system/engine/router_builtin.php
