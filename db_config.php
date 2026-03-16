<?php
/**
 * Toolify — Database Configuration
 * SQLite-based database connection via PDO
 */

// CORS headers for local development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database path
define('DB_PATH', __DIR__ . '/toolify.db');

/**
 * Get PDO database connection
 * 
 * @return PDO
 */
function getDB(): PDO {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("PRAGMA journal_mode=WAL");
        $db->exec("PRAGMA foreign_keys=ON");
        return $db;
    } catch (PDOException $e) {
        sendError("Database connection failed: " . $e->getMessage(), 500);
        exit();
    }
}

/**
 * Send JSON success response
 */
function sendJSON($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Send JSON success response with standard format
 */
function sendSuccess($message, $data = []) {
    sendJSON([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Send JSON error response
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}
?>
