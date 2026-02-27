#!/bin/bash

# fix_mysql_access.sh
# Creates a MySQL admin user for phpMyAdmin access

set -e

# Ask for new admin credentials
read -p "Enter new MySQL Admin Username [pma_admin]: " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-pma_admin}

read -s -p "Enter new MySQL Admin Password: " ADMIN_PASS
echo ""

echo "Creating MySQL user '$ADMIN_USER' with full privileges..."

# Execute SQL commands as root (via sudo socket)
sudo mysql <<EOF
CREATE USER IF NOT EXISTS '${ADMIN_USER}'@'localhost' IDENTIFIED WITH mysql_native_password BY '${ADMIN_PASS}';
ALTER USER '${ADMIN_USER}'@'localhost' IDENTIFIED WITH mysql_native_password BY '${ADMIN_PASS}';
GRANT ALL PRIVILEGES ON *.* TO '${ADMIN_USER}'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

echo "Success! You can now log in to phpMyAdmin with:"
echo "Username: $ADMIN_USER"
echo "Password: (hidden)"
