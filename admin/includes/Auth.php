<?php
/**
 * Authentication Middleware
 * GlobalWays Backend - Role-Based Access Control
 */

class Auth {
    private $db;
    private $user;
    private $token;

    public function __construct($database) {
        $this->db = $database;
        $this->user = null;
        $this->token = null;
    }

    /**
     * Initialize authentication
     * Check if user has valid session or token
     */
    public function init() {
        // Check session
        if (isset($_SESSION['user_id'])) {
            $this->loadUserFromSession();
            return true;
        }

        // Check authorization header (Bearer token)
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $this->validateToken($headers['Authorization']);
            if ($this->user) {
                return true;
            }
        }

        return false;
    }

    /**
     * Login user - Create session
     */
    public function login($email, $password) {
        try {
            // Find user by email
            $users = $this->db->select(
                "SELECT * FROM users WHERE email = ?",
                [$email]
            );

            if (empty($users)) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            $user = $users[0];

            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                return ['success' => false, 'message' => 'Account is inactive'];
            }

            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            // Generate token for API
            $token = $this->generateToken($user['id']);

            return [
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'token' => $token
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Generate JWT token
     */
    private function generateToken($userId) {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]));
        
        $signature = base64_encode(hash_hmac(
            'sha256',
            "$header.$payload",
            'your_secret_key',
            true
        ));

        return "$header.$payload.$signature";
    }

    /**
     * Validate JWT token
     */
    private function validateToken($authHeader) {
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return false;
        }

        $token = $matches[1];
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($header, $payload, $signature) = $parts;

        // Verify signature
        $expected = base64_encode(hash_hmac(
            'sha256',
            "$header.$payload",
            'your_secret_key',
            true
        ));

        if ($signature !== $expected) {
            return false;
        }

        // Decode payload
        $decoded = json_decode(base64_decode($payload), true);

        // Check expiration
        if ($decoded['exp'] < time()) {
            return false;
        }

        // Load user
        $this->loadUser($decoded['user_id']);
        return true;
    }

    /**
     * Load user from session
     */
    private function loadUserFromSession() {
        $users = $this->db->select(
            "SELECT id, name, email, phone, role, status FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );

        if (!empty($users)) {
            $this->user = $users[0];
        }
    }

    /**
     * Load user by ID
     */
    private function loadUser($userId) {
        $users = $this->db->select(
            "SELECT id, name, email, phone, role, status FROM users WHERE id = ?",
            [$userId]
        );

        if (!empty($users)) {
            $this->user = $users[0];
        }
    }

    /**
     * Get current user
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Get current user ID
     */
    public function getUserId() {
        return $this->user['id'] ?? null;
    }

    /**
     * Get current user role
     */
    public function getRole() {
        return $this->user['role'] ?? null;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        if (is_array($role)) {
            return in_array($this->user['role'] ?? null, $role);
        }
        return ($this->user['role'] ?? null) === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is vendor
     */
    public function isVendor() {
        return $this->hasRole('vendor');
    }

    /**
     * Check if user is customer
     */
    public function isCustomer() {
        return $this->hasRole('customer');
    }

    /**
     * Check if authenticated
     */
    public function isAuthenticated() {
        return $this->user !== null;
    }

    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            Response::unauthorized('Authentication required');
        }
    }

    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();

        if (!$this->hasRole($role)) {
            Response::error('Insufficient permissions', 403);
        }
    }

    /**
     * Require admin access
     */
    public function requireAdmin() {
        $this->requireRole('admin');
    }

    /**
     * Require vendor access
     */
    public function requireVendor() {
        $this->requireRole('vendor');
    }

    /**
     * Check if user can access resource
     */
    public function canAccess($resourceUserId) {
        if ($this->isAdmin()) {
            return true; // Admin can access everything
        }

        return $this->getUserId() === $resourceUserId;
    }
}

// Enable sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
