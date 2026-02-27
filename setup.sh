#!/bin/bash

# setup.sh - Auto-installation script for Horizon Europe BI Dashboard + phpMyAdmin
# Tested on Ubuntu 22.04 LTS / Debian 12

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}>>> Starting Installation for Horizon Europe BI Dashboard...${NC}"

# 1. Update System
echo -e "${BLUE}>>> Updating package lists...${NC}"
sudo apt-get update

# 2. Install Dependencies
echo -e "${BLUE}>>> Installing Nginx, PHP 8.1+, MySQL, and extensions...${NC}"
sudo apt-get install -y nginx php-fpm php-mysql php-sqlite3 php-xml php-gd php-mbstring php-curl php-zip unzip mysql-server git curl

# 3. Install Composer
if ! command -v composer &> /dev/null
then
    echo -e "${BLUE}>>> Installing Composer...${NC}"
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
else
    echo -e "${GREEN}>>> Composer already installed.${NC}"
fi

# 4. Configure Project Directory
PROJECT_DIR="/var/www/cordis-bi"
echo -e "${BLUE}>>> Setting up project in ${PROJECT_DIR}...${NC}"

# If app/ exists in current dir, copy it. Otherwise clone or create.
# Assuming this script is run from the repo root
if [ -d "app" ]; then
    sudo mkdir -p $PROJECT_DIR
    sudo cp -r app/* $PROJECT_DIR/
    sudo cp -r app/.gitignore $PROJECT_DIR/ 2>/dev/null || true
else
    echo -e "${RED}>>> 'app' directory not found. Please run this script from the repository root.${NC}"
    exit 1
fi

# 5. Set Permissions
echo -e "${BLUE}>>> Setting permissions...${NC}"
sudo chown -R www-data:www-data $PROJECT_DIR
sudo chmod -R 775 $PROJECT_DIR/data
sudo chmod -R 775 $PROJECT_DIR/database.sqlite 2>/dev/null || true

# 6. Install PHP Dependencies
echo -e "${BLUE}>>> Installing PHP dependencies...${NC}"
cd $PROJECT_DIR
sudo -u www-data composer install --no-dev --optimize-autoloader

# 6.5. Configure PHP Settings (Upload Limit)
echo -e "${BLUE}>>> Configuring PHP limits (100M)...${NC}"
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
PHP_INI="/etc/php/$PHP_VERSION/fpm/php.ini"

if [ -f "$PHP_INI" ]; then
    sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 100M/' $PHP_INI
    sudo sed -i 's/^post_max_size.*/post_max_size = 100M/' $PHP_INI
    sudo sed -i 's/^memory_limit.*/memory_limit = 256M/' $PHP_INI
    echo -e "${GREEN}>>> PHP FPM settings updated.${NC}"
    sudo systemctl restart php$PHP_VERSION-fpm
else
    echo -e "${RED}>>> php.ini not found at $PHP_INI. Please check PHP installation.${NC}"
fi

# 7. Database Setup (Interactive Choice)
echo -e "${BLUE}>>> Database Setup${NC}"
read -p "Do you want to use MySQL (m) or SQLite (s)? [Default: s]: " DB_CHOICE
DB_CHOICE=${DB_CHOICE:-s}

if [[ "$DB_CHOICE" == "m" ]]; then
    echo -e "${BLUE}>>> Configuring MySQL...${NC}"
    read -p "Enter MySQL Database Name [cordis_bi]: " DB_NAME
    DB_NAME=${DB_NAME:-cordis_bi}
    read -p "Enter MySQL User [cordis_user]: " DB_USER
    DB_USER=${DB_USER:-cordis_user}
    read -s -p "Enter MySQL Password: " DB_PASS
    echo ""

    # Create DB and User
    # We use ALTER USER to ensure password is set correctly even if user exists
    sudo mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
    sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    sudo mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    sudo mysql -e "FLUSH PRIVILEGES;"

    # Update config.php using sudo tee to handle permissions
    sudo tee config.php > /dev/null <<EOF
<?php
define('DB_DRIVER', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', '${DB_NAME}');
define('DB_USER', '${DB_USER}');
define('DB_PASS', '${DB_PASS}');
define('DB_PATH', '');
define('APP_NAME', 'Horizon Europe / CORDIS Mini BI Dashboard');
date_default_timezone_set('Europe/Athens');
EOF
    # Ensure config.php is readable by web server
    sudo chown www-data:www-data config.php

    echo -e "${GREEN}>>> MySQL configured.${NC}"
else
    echo -e "${BLUE}>>> Using SQLite (Default)...${NC}"
    # Ensure SQLite file exists and permissions
    touch database.sqlite
    sudo chown www-data:www-data database.sqlite
    echo -e "${GREEN}>>> SQLite configured.${NC}"
fi

# 8. Initialize Database Tables
echo -e "${BLUE}>>> Initializing Database Schema...${NC}"
sudo -u www-data php init_db.php

# 8.5. Install phpMyAdmin
echo -e "${BLUE}>>> Installing phpMyAdmin (pma)...${NC}"
PMA_DIR="$PROJECT_DIR/pma"
PMA_VERSION="5.2.1"
PMA_URL="https://files.phpmyadmin.net/phpMyAdmin/${PMA_VERSION}/phpMyAdmin-${PMA_VERSION}-all-languages.zip"

if [ ! -d "$PMA_DIR" ]; then
    echo "Downloading phpMyAdmin $PMA_VERSION..."
    curl -L -o phpmyadmin.zip "$PMA_URL"
    unzip -q phpmyadmin.zip
    sudo mv "phpMyAdmin-${PMA_VERSION}-all-languages" "$PMA_DIR"
    rm phpmyadmin.zip

    # Configure phpMyAdmin
    cd "$PMA_DIR"
    sudo cp config.sample.inc.php config.inc.php
    SECRET=$(openssl rand -base64 32 | tr -d '\n\r')
    sudo sed -i "s/\$cfg\['blowfish_secret'\] = '';/\$cfg\['blowfish_secret'\] = '$SECRET';/" config.inc.php
    sudo chown -R www-data:www-data "$PMA_DIR"
    echo -e "${GREEN}>>> phpMyAdmin installed at /pma/ (accessible via browser).${NC}"
else
    echo -e "${GREEN}>>> phpMyAdmin already installed.${NC}"
fi

# 9. Configure Nginx
echo -e "${BLUE}>>> Configuring Nginx...${NC}"
NGINX_CONF="/etc/nginx/sites-available/cordis-bi"
sudo tee $NGINX_CONF > /dev/null <<EOF
server {
    listen 80;
    server_name _; # Change to your domain
    root $PROJECT_DIR;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Handle phpMyAdmin explicitly if needed (standard PHP block below covers it usually)
    location ^~ /pma {
        alias $PROJECT_DIR/pma;
        index index.php;
        try_files \$uri \$uri/ /pma/index.php;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php$PHP_VERSION-fpm.sock;
            fastcgi_param SCRIPT_FILENAME \$request_filename;
        }
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHP_VERSION-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    # Protect sensitive files
    location ~ \.(sqlite|sql|log)$ {
        deny all;
    }

    location /vendor {
        deny all;
    }

    client_max_body_size 100M;
}
EOF

# Enable Site
if [ -f "/etc/nginx/sites-enabled/default" ]; then
    sudo rm /etc/nginx/sites-enabled/default
fi
sudo ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# 10. Generate Mock Data (Optional)
# (Actually, 'init_db.php' might be creating tables but not populating. Let's keep this choice.)
# But wait, init_db.php creates schema. Maybe we run migration too just in case?
# No, init_db usually handles full setup.
# But for the summary/converter feature, users might need fix_schema_summary.php if init_db doesn't include it yet.
# To be safe, let's run the schema fix as well.
echo -e "${BLUE}>>> Applying Summary/Converter Schema...${NC}"
sudo -u www-data php $PROJECT_DIR/fix_schema_summary.php

read -p "Do you want to generate mock data? (y/n) [y]: " GEN_MOCK
GEN_MOCK=${GEN_MOCK:-y}
if [[ "$GEN_MOCK" == "y" ]]; then
    if [ -f "$PROJECT_DIR/generate_mock_data.php" ]; then
        sudo -u www-data php $PROJECT_DIR/generate_mock_data.php
        echo -e "${GREEN}>>> Mock data generated in data/.${NC}"
    fi
fi

echo -e "${GREEN}>>> Installation Complete!${NC}"
echo -e "${GREEN}>>> Access the dashboard at http://<your-server-ip>/${NC}"
echo -e "${GREEN}>>> Access phpMyAdmin at http://<your-server-ip>/pma/${NC}"
