#!/bin/bash
set -e

echo "  CoreCart - One-Click Shop Setup"

OS="$(uname -s)"

case "$OS" in
    Linux*)
        PKG_MANAGER="apt-get"
        if ! command -v php &> /dev/null; then
            echo "[1/2] Installing PHP via apt..."
            sudo $PKG_MANAGER update -qq
            sudo $PKG_MANAGER install -y -qq php php-cli php-mysql php-mbstring php-curl php-intl php-zip php-gd
        else
            echo "[OK] PHP already installed."
        fi
        ;;
    Darwin*)
        if ! command -v php &> /dev/null; then
            echo "[1/2] Installing PHP via Homebrew..."
            brew install php
        else
            echo "[OK] PHP already installed."
        fi
        ;;
    *)
        echo "[ERROR] Unsupported OS: $OS"
        echo "Please install PHP 8.4+ manually."
        exit 1
        ;;
esac

# Install Composer if missing
if ! command -v composer &> /dev/null; then
    echo "[INFO] Installing Composer..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# Install project dependencies
if [ ! -d "vendor" ]; then
    echo "[2/2] Installing project dependencies..."
    composer install
fi

echo "  CoreCart Shop is ready!"
echo "  Open in browser: http://localhost:8000"
echo "  Press Ctrl+C to stop"
php -S localhost:8000 system/engine/router_builtin.php
