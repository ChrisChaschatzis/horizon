#!/bin/bash
# setup_phpmyadmin.sh

# Directory where phpMyAdmin will be installed
PMA_DIR="app/pma"

# URL of the latest phpMyAdmin release (change version if needed)
PMA_VERSION="5.2.1"
PMA_URL="https://files.phpmyadmin.net/phpMyAdmin/${PMA_VERSION}/phpMyAdmin-${PMA_VERSION}-all-languages.zip"

echo "Downloading phpMyAdmin $PMA_VERSION..."
if command -v curl &> /dev/null; then
    curl -L -o phpmyadmin.zip "$PMA_URL"
elif command -v wget &> /dev/null; then
    wget -O phpmyadmin.zip "$PMA_URL"
else
    echo "Error: curl or wget is required."
    exit 1
fi

echo "Extracting..."
unzip -q phpmyadmin.zip

# Create directory
if [ -d "$PMA_DIR" ]; then
    echo "Directory $PMA_DIR already exists. Renaming to backup..."
    mv "$PMA_DIR" "${PMA_DIR}_backup_$(date +%s)"
fi

mv "phpMyAdmin-${PMA_VERSION}-all-languages" "$PMA_DIR"
rm phpmyadmin.zip

# Create config file
echo "Configuring..."
cd "$PMA_DIR" || exit
cp config.sample.inc.php config.inc.php

# Generate blowfish_secret (random string)
SECRET=$(openssl rand -base64 32 | tr -d '\n\r')
# Use sed safely (macOS vs Linux)
if [[ "$OSTYPE" == "darwin"* ]]; then
  sed -i '' "s/\$cfg\['blowfish_secret'\] = '';/\$cfg\['blowfish_secret'\] = '$SECRET';/" config.inc.php
else
  sed -i "s/\$cfg\['blowfish_secret'\] = '';/\$cfg\['blowfish_secret'\] = '$SECRET';/" config.inc.php
fi

echo "phpMyAdmin setup complete!"
echo "You can access it at: /app/pma/"
