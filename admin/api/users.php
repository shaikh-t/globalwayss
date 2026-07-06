<?php
/**
 * Users API Endpoint
 * GlobalWays Backend
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Response.php';
require_once __DIR__ . '/../includes/User.php';

try {
    // Initialize database and user model
    $database = new Database();
    $userModel = new User($database);

    // Get request method and parameters
    $method = $_SERVER['REQUEST_METHOD'];
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uriParts = array_filter(explode('/', $requestUri));
    $resourceId = end($uriParts);

    // Check if resourceId is a number (user ID)
    if (!is_numeric($resourceId)) {
        $resourceId = null;
    }

    // Get query parameters
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    $search = $_GET['search'] ?? null;

    // Sanitize inputs
    $limit = min((int)$limit, 100);
    $offset = (int)$offset;

    // Route handling
    switch ($method) {
        case 'GET':
            if ($resourceId) {
                // Get single user
                $user = $userModel->getById($resourceId);
                if (!$user) {
                    Response::notFound('User not found');
                }
                Response::success($user, 'User retrieved successfully');
            } else {
                // Get all users or search
                if ($search) {
                    $users = $userModel->search($search, $limit);
                    Response::success([
                        'users' => $users,
                        'count' => count($users),
                        'search' => $search
                    ], 'Search results');
                } else {
                    $users = $userModel->getAll($limit, $offset);
                    $total = $userModel->getCount();
                    Response::success([
                        'users' => $users,
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset
                    ], 'Users retrieved successfully');
                }
            }
            break;

        case 'POST':
            // Create new user
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::validationError(['body' => 'Invalid JSON']);
            }

            $result = $userModel->create($input);

            if (!$result['success']) {
                Response::error($result['message'], 400);
            }

            Response::success($result['data'], $result['message'], 201);
            break;

        case 'PUT':
            // Update user
            if (!$resourceId) {
                Response::error('User ID required', 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::validationError(['body' => 'Invalid JSON']);
            }

            $result = $userModel->update($resourceId, $input);

            if (!$result['success']) {
                Response::error($result['message'], 400);
            }

            Response::success(null, $result['message']);
            break;

        case 'DELETE':
            // Delete user
            if (!$resourceId) {
                Response::error('User ID required', 400);
            }

            $result = $userModel->delete($resourceId);

            if (!$result['success']) {
                Response::error($result['message'], 400);
            }

            Response::success(null, $result['message']);
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>
