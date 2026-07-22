@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0"

set "PHP_DIR=%CD%\php_core"
set "PHP_ZIP=%TEMP%\corecart-php.zip"
set "PHP_URL=https://windows.php.net/downloads/releases/latest/php-8.4-Win32-vs17-x64-latest.zip"
set "HOST=127.0.0.1"
set "PORT=8000"

echo.
echo  CoreCart - One-Click Shop Setup
echo.

if exist "%PHP_DIR%\php.exe" goto php_ready

echo [1/4] Downloading PHP 8.4 for Windows...

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference = 'Stop';" ^
    "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12;" ^
    "Invoke-WebRequest -UseBasicParsing -Uri '%PHP_URL%' -OutFile '%PHP_ZIP%'"

if errorlevel 1 (
    echo.
    echo [ERROR] PHP download failed.
    echo Check your internet connection and DNS settings.
    echo URL: %PHP_URL%
    goto failure
)

if not exist "%PHP_ZIP%" (
    echo [ERROR] PHP archive was not downloaded.
    goto failure
)

echo [2/4] Extracting PHP...

if exist "%PHP_DIR%" rmdir /s /q "%PHP_DIR%"

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference = 'Stop';" ^
    "Expand-Archive -LiteralPath '%PHP_ZIP%' -DestinationPath '%PHP_DIR%' -Force"

if errorlevel 1 (
    echo [ERROR] PHP extraction failed.
    goto failure
)

if not exist "%PHP_DIR%\php.exe" (
    echo [ERROR] php.exe was not found after extraction.
    goto failure
)

del /q "%PHP_ZIP%" >nul 2>&1

echo [3/4] Configuring PHP...

if not exist "%PHP_DIR%\php.ini-development" (
    echo [ERROR] php.ini-development was not found.
    goto failure
)

copy /y "%PHP_DIR%\php.ini-development" "%PHP_DIR%\php.ini" >nul

(
    echo.
    echo extension_dir="ext"
    echo extension=curl
    echo extension=mbstring
    echo extension=intl
    echo extension=mysqli
    echo extension=pdo_mysql
    echo extension=openssl
) >> "%PHP_DIR%\php.ini"

:php_ready

echo [4/4] Checking PHP...

"%PHP_DIR%\php.exe" -v
if errorlevel 1 (
    echo.
    echo [ERROR] PHP could not start.
    echo Install Microsoft Visual C++ Redistributable 2015-2022 x64.
    goto failure
)

if not exist "vendor\autoload.php" (
    echo.
    echo [ERROR] Composer dependencies are not installed.
    echo Run: composer install
    goto failure
)

if not exist ".env" (
    if exist ".env.example" (
        copy /y ".env.example" ".env" >nul
        echo [INFO] Created .env from .env.example
    ) else (
        echo [ERROR] Neither .env nor .env.example exists.
        goto failure
    )
)

echo.
echo  CoreCart Shop is ready!
echo  Open in browser: http://%HOST%:%PORT%
echo  Press Ctrl+C to stop the server
echo.

"%PHP_DIR%\php.exe" -S %HOST%:%PORT% system/engine/router_builtin.php
exit /b %errorlevel%

:failure
if exist "%PHP_ZIP%" del /q "%PHP_ZIP%" >nul 2>&1
echo.
echo  CoreCart setup failed. The server was not started.
pause
exit /b 1