#!/bin/bash
set -e

echo "  CoreCart - Local Development Server"

# Check PHP
if ! command -v php &> /dev/null; then
    echo "[ERROR] PHP not found!"
    echo "Install it with: sudo apt install php php-cli php-mysql php-mbstring php-curl"
    exit 1
fi

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "[ERROR] Composer not found!"
    echo "Install it from: https://getcomposer.org"
    exit 1
fi

# Install dependencies if missing
if [ ! -d "vendor" ]; then
    echo "[INFO] Installing dependencies via Composer..."
    composer install
    echo ""
fi

echo "[OK] Starting server at http://localhost:8000"
echo "[OK] Press Ctrl+C to stop"
php -S localhost:8000 system/engine/router_builtin.php
