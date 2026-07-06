<?php
/**
 * Vendors API Endpoint
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
require_once __DIR__ . '/../includes/Vendor.php';

try {
    // Initialize database and vendor model
    $database = new Database();
    $vendorModel = new Vendor($database);

    // Get request method and parameters
    $method = $_SERVER['REQUEST_METHOD'];
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uriParts = array_filter(explode('/', $requestUri));
    $resourceId = end($uriParts);

    // Check if resourceId is a number (vendor ID)
    if (!is_numeric($resourceId)) {
        $resourceId = null;
    }

    // Get query parameters
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    $category = $_GET['category'] ?? null;
    $verified = $_GET['verified'] ?? null;
    $search = $_GET['search'] ?? null;

    // Sanitize inputs
    $limit = min((int)$limit, 100);
    $offset = (int)$offset;

    // Route handling
    switch ($method) {
        case 'GET':
            if ($resourceId) {
                // Get single vendor
                $vendor = $vendorModel->getById($resourceId);
                if (!$vendor) {
                    Response::notFound('Vendor not found');
                }
                Response::success($vendor, 'Vendor retrieved successfully');
            } else {
                // Get all vendors with filters or search
                if ($search) {
                    $vendors = $vendorModel->search($search, $limit);
                    Response::success([
                        'vendors' => $vendors,
                        'count' => count($vendors),
                        'search' => $search
                    ], 'Search results');
                } elseif ($verified === '1') {
                    $vendors = $vendorModel->getVerified($limit);
                    Response::success([
                        'vendors' => $vendors,
                        'count' => count($vendors),
                        'filter' => 'verified'
                    ], 'Verified vendors retrieved');
                } elseif ($category) {
                    $vendors = $vendorModel->getByCategory($category, $limit);
                    Response::success([
                        'vendors' => $vendors,
                        'count' => count($vendors),
                        'category' => $category
                    ], 'Vendors by category retrieved');
                } else {
                    $vendors = $vendorModel->getAll($limit, $offset);
                    $total = $vendorModel->getCount();
                    Response::success([
                        'vendors' => $vendors,
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset
                    ], 'Vendors retrieved successfully');
                }
            }
            break;

        case 'POST':
            // Create new vendor
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::validationError(['body' => 'Invalid JSON']);
            }

            $result = $vendorModel->create($input);

            if (!$result['success']) {
                Response::error($result['message'], 400);
            }

            Response::success($result['data'], $result['message'], 201);
            break;

        case 'PUT':
            // Update vendor
            if (!$resourceId) {
                Response::error('Vendor ID required', 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::validationError(['body' => 'Invalid JSON']);
            }

            $result = $vendorModel->update($resourceId, $input);

            if (!$result['success']) {
                Response::error($result['message'], 400);
            }

            Response::success(null, $result['message']);
            break;

        case 'DELETE':
            // Delete vendor
            if (!$resourceId) {
                Response::error('Vendor ID required', 400);
            }

            $result = $vendorModel->delete($resourceId);

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
