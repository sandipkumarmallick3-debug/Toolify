<?php
/**
 * Toolify — User Authentication API
 */

require_once __DIR__ . '/db_config.php';
session_start();

$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            sendError("Invalid input data.");
        }

        try {
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$data['username'], $data['email'], $hashed]);
            
            // Auto login after registration
            $userId = $db->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $data['username'];
            
            sendSuccess("Registration successful.", [
                'user' => [
                    'id' => $userId,
                    'username' => $data['username']
                ]
            ]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                sendError("Username or email already exists.");
            }
            sendError("Registration failed: " . $e->getMessage());
        }
        break;

    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['email']) || empty($data['password'])) {
            sendError("Email and password are required.");
        }

        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            sendSuccess("Login successful.", [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username']
                ]
            ]);
        } else {
            sendError("Invalid email or password.");
        }
        break;

    case 'logout':
        session_destroy();
        sendSuccess("Logged out successfully.");
        break;

    case 'status':
        if (isset($_SESSION['user_id'])) {
            sendSuccess("Authenticated", [
                'loggedIn' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username']
                ]
            ]);
        } else {
            sendSuccess("Not authenticated", ['loggedIn' => false]);
        }
        break;

    default:
        sendError("Invalid action.");
}
?>
