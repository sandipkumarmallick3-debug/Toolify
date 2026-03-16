<?php
/**
 * Toolify — Application Runner
 * Usage: php run.php
 * This script ensures the database is set up and starts the built-in PHP web server.
 */

$port = 8000;
$host = '127.0.0.1';

echo "=== Toolify Application Runner ===\n\n";

// 1. Run the database setup script
echo "[1] Checking database setup...\n";
$phpBinary = PHP_BINARY;
$iniPath = __DIR__ . '/php.ini';
passthru("\"$phpBinary\" -c \"$iniPath\" \"" . __DIR__ . "/setup_database.php\"");

// 2. Start the built-in development server
echo "\n[2] Starting the development server on $host:$port...\n";
echo "    App URL: http://$host:$port/templates/index.html\n";
echo "    Admin Panel: http://$host:$port/admin/\n";
echo "    Press Ctrl+C to stop the server.\n\n";

// Execute the PHP built-in server command, passing through the output
passthru("\"$phpBinary\" -c \"$iniPath\" -S $host:$port");
