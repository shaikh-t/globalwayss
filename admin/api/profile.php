<?php
/**
 * Profile API Endpoint - Role-Based Profiles
 * GlobalWays Backend
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
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
    $auth->init();
    $auth->requireAuth();

    $method = $_SERVER['REQUEST_METHOD'];
    $currentUser = $auth->getUser();

    switch ($method) {
        case 'GET':
            // Get profile based on role
            $profile = getProfileByRole($database, $auth, $currentUser);
            Response::success($profile, 'Profile retrieved successfully');
            break;

        case 'PUT':
            // Update profile
            $input = json_decode(file_get_contents('php://input'), true);
            $result = updateProfile($database, $auth, $currentUser, $input);

            if (!$result['success']) {
                Response::error($result['message'], 400);
            }

            Response::success(null, $result['message']);
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('Profile Error: ' . $e->getMessage());
    Response::serverError($e->getMessage());
}

/**
 * Get profile based on user role
 */
function getProfileByRole($database, $auth, $user) {
    $role = $auth->getRole();

    // Base user info
    $profile = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => $user['role'],
        'status' => $user['status'],
        'created_at' => $user['created_at'],
    ];

    switch ($role) {
        case 'admin':
            // Admin profile - show admin-specific data
            $profile['adminData'] = [
                'totalUsers' => getTotalUsers($database),
                'totalVendors' => getTotalVendors($database),
                'pendingVendors' => getPendingVendors($database),
                'recentActivity' => getRecentActivity($database)
            ];
            break;

        case 'vendor':
            // Vendor profile - show vendor business info
            $vendorData = getVendorProfile($database, $user['id']);
            $profile = array_merge($profile, $vendorData);
            break;

        case 'customer':
            // Customer profile - show customer info
            $profile['customerData'] = [
                'bookings' => getCustomerBookings($database, $user['id']),
                'reviews' => getCustomerReviews($database, $user['id'])
            ];
            break;
    }

    return $profile;
}

/**
 * Update profile based on role
 */
function updateProfile($database, $auth, $user, $input) {
    $userId = $auth->getUserId();
    $role = $auth->getRole();

    // Only allow updating own profile or admins can update anyone
    if ($userId !== $user['id'] && !$auth->isAdmin()) {
        return ['success' => false, 'message' => 'Cannot update other profiles'];
    }

    $updateData = [];

    if (isset($input['name'])) {
        $updateData['name'] = $input['name'];
    }
    if (isset($input['phone'])) {
        $updateData['phone'] = $input['phone'];
    }

    // Role-specific updates
    if ($role === 'vendor' && isset($input['category'])) {
        $updateData['category'] = $input['category'];
    }
    if ($role === 'vendor' && isset($input['description'])) {
        $updateData['description'] = $input['description'];
    }

    if (empty($updateData)) {
        return ['success' => false, 'message' => 'No fields to update'];
    }

    $updateData['updated_at'] = date('Y-m-d H:i:s');

    $affected = $database->update('users', $updateData, 'id = ?', [$userId]);

    if ($affected === false) {
        return ['success' => false, 'message' => 'Failed to update profile'];
    }

    return ['success' => true, 'message' => 'Profile updated successfully'];
}

// Helper functions

function getTotalUsers($database) {
    $result = $database->select("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    return $result[0]['count'] ?? 0;
}

function getTotalVendors($database) {
    $result = $database->select("SELECT COUNT(*) as count FROM vendors");
    return $result[0]['count'] ?? 0;
}

function getPendingVendors($database) {
    $result = $database->select("SELECT COUNT(*) as count FROM vendors WHERE status = 'pending'");
    return $result[0]['count'] ?? 0;
}

function getRecentActivity($database) {
    return $database->select(
        "SELECT * FROM users ORDER BY created_at DESC LIMIT 5"
    );
}

function getVendorProfile($database, $userId) {
    // Get vendor-specific data
    $vendors = $database->select(
        "SELECT * FROM vendors WHERE created_by = ? OR email = (SELECT email FROM users WHERE id = ?)",
        [$userId, $userId]
    );

    if (!empty($vendors)) {
        $vendor = $vendors[0];
        return [
            'vendorData' => [
                'category' => $vendor['category'] ?? '',
                'description' => $vendor['description'] ?? '',
                'rating' => $vendor['rating'] ?? 0,
                'verified' => $vendor['verified'] ?? 0,
                'totalBookings' => getTotalVendorBookings($database, $userId)
            ]
        ];
    }

    return [
        'vendorData' => [
            'category' => '',
            'description' => '',
            'rating' => 0,
            'verified' => 0,
            'totalBookings' => 0
        ]
    ];
}

function getTotalVendorBookings($database, $userId) {
    // Placeholder - add bookings table later
    return 0;
}

function getCustomerBookings($database, $userId) {
    // Placeholder - add bookings table later
    return [];
}

function getCustomerReviews($database, $userId) {
    // Placeholder - add reviews table later
    return [];
}
?>
