<?php
/**
 * Auth API Endpoint - Login/Logout/Profile
 * GlobalWays Backend
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Response.php';
require_once __DIR__ . '/../includes/Auth.php';

try {
    $database = new Database();
    $auth = new Auth($database);

    $method = $_SERVER['REQUEST_METHOD'];
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $action = end(array_filter(explode('/', $requestUri)));

    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if ($action === 'login' || $action === 'auth') {
                // Login endpoint
                if (empty($input['email']) || empty($input['password'])) {
                    Response::validationError([
                        'email' => 'Email is required',
                        'password' => 'Password is required'
                    ]);
                }

                $result = $auth->login($input['email'], $input['password']);

                if (!$result['success']) {
                    Response::error($result['message'], 401);
                }

                Response::success($result['data'], $result['message'], 200);
            } elseif ($action === 'register') {
                // Register new user
                if (empty($input['name']) || empty($input['email']) || empty($input['password'])) {
                    Response::validationError([
                        'name' => 'Name is required',
                        'email' => 'Email is required',
                        'password' => 'Password is required'
                    ]);
                }

                // Check if email exists
                $existing = $database->select(
                    "SELECT id FROM users WHERE email = ?",
                    [$input['email']]
                );

                if (!empty($existing)) {
                    Response::error('Email already registered', 400);
                }

                $userData = [
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'phone' => $input['phone'] ?? '',
                    'password' => password_hash($input['password'], PASSWORD_BCRYPT),
                    'role' => 'customer', // Default role
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $userId = $database->insert('users', $userData);

                if (!$userId) {
                    Response::serverError('Failed to create account');
                }

                $result = $auth->login($input['email'], $input['password']);
                Response::success($result['data'], 'Account created successfully', 201);
            }
            break;

        case 'GET':
            // Get current user profile
            $auth->init();
            $auth->requireAuth();

            $user = $auth->getUser();
            Response::success($user, 'Profile retrieved successfully');
            break;

        case 'DELETE':
            // Logout
            $auth->logout();
            Response::success(null, 'Logged out successfully');
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('Auth Error: ' . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>
