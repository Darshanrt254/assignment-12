#!/bin/bash

# Start MySQL/MariaDB service in the background
service mariadb start

# Wait for MySQL to be fully ready
sleep 3

# Initialize the databases from SQL files silently
echo "Initializing databases..."

# Setup main assignment database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS itc2026_ass10;"
mysql -u root itc2026_ass10 < /var/www/html/database.sql

# Setup secondary db for myapp2.php
mysql -u root -e "CREATE DATABASE IF NOT EXISTS test_db;"
mysql -u root test_db -e "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), email VARCHAR(100), phone VARCHAR(20), password VARCHAR(255));"

echo "Database initialization complete!"

# Fallback values for getenv if not passed (local MySQL)
export DB_HOST=${DB_HOST:-"localhost"}
export DB_USER=${DB_USER:-"root"}
export DB_PASS=${DB_PASS:-""}

# Render injects PORT, but fallback to 80 if not set
export PORT=${PORT:-80}

# Start Apache in the foreground
echo "Starting Apache..."
source /etc/apache2/envvars
exec apache2 -D FOREGROUND
