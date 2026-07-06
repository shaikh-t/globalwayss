<?php
/**
 * User Model - Handles user operations
 * GlobalWays Backend
 */

class User {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all users
     */
    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT id, name, email, phone, role, status, created_at 
                  FROM users 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";

        $users = $this->db->select($query, [$limit, $offset]);
        return $users !== null ? $users : [];
    }

    /**
     * Get total user count
     */
    public function getCount() {
        $query = "SELECT COUNT(*) as total FROM users";
        $result = $this->db->select($query);
        return $result[0]['total'] ?? 0;
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT id, name, email, phone, role, status, created_at 
                  FROM users 
                  WHERE id = ?";

        $users = $this->db->select($query, [$id]);
        return !empty($users) ? $users[0] : null;
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $query = "SELECT id, name, email, phone, role, status, created_at 
                  FROM users 
                  WHERE email = ?";

        $users = $this->db->select($query, [$email]);
        return !empty($users) ? $users[0] : null;
    }

    /**
     * Create new user
     */
    public function create($data) {
        // Validate required fields
        $required = ['name', 'email', 'phone', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '{$field}' is required"];
            }
        }

        // Check if email already exists
        if ($this->getByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $insertData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
            'password' => !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : '',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $this->db->insert('users', $insertData);

        if ($id === false) {
            return ['success' => false, 'message' => 'Failed to create user'];
        }

        return [
            'success' => true,
            'message' => 'User created successfully',
            'data' => ['id' => $id]
        ];
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        // Check if user exists
        if (!$this->getById($id)) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            // Check if new email is unique
            $existing = $this->db->select(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$data['email'], $id]
            );
            if (!empty($existing)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            $updateData['email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $affected = $this->db->update('users', $updateData, 'id = ?', [$id]);

        if ($affected === false) {
            return ['success' => false, 'message' => 'Failed to update user'];
        }

        return ['success' => true, 'message' => 'User updated successfully'];
    }

    /**
     * Delete user
     */
    public function delete($id) {
        if (!$this->getById($id)) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $affected = $this->db->delete('users', 'id = ?', [$id]);

        if ($affected === false) {
            return ['success' => false, 'message' => 'Failed to delete user'];
        }

        return ['success' => true, 'message' => 'User deleted successfully'];
    }

    /**
     * Search users
     */
    public function search($term, $limit = 50) {
        $query = "SELECT id, name, email, phone, role, status, created_at 
                  FROM users 
                  WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
                  ORDER BY created_at DESC 
                  LIMIT ?";

        $searchTerm = "%{$term}%";
        $users = $this->db->select($query, [$searchTerm, $searchTerm, $searchTerm, $limit]);
        return $users !== null ? $users : [];
    }
}
?>
