<?php

// Database Configuration
// Use 'sqlite' for this demo environment. Change to 'mysql' for production if needed.
define('DB_DRIVER', 'sqlite');
define('DB_HOST', 'localhost');
define('DB_NAME', 'cordis_bi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PATH', __DIR__ . '/database.sqlite');

// App Configuration
define('APP_NAME', 'Horizon Europe / CORDIS Mini BI Dashboard');

// Ensure correct timezone
date_default_timezone_set('Europe/Athens');
