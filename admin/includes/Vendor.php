<?php
/**
 * Vendor Model - Handles vendor operations
 * GlobalWays Backend
 */

class Vendor {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all vendors
     */
    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT id, name, email, phone, category, description, rating, 
                         status, verified, created_at 
                  FROM vendors 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";

        $vendors = $this->db->select($query, [$limit, $offset]);
        return $vendors !== null ? $vendors : [];
    }

    /**
     * Get total vendor count
     */
    public function getCount() {
        $query = "SELECT COUNT(*) as total FROM vendors";
        $result = $this->db->select($query);
        return $result[0]['total'] ?? 0;
    }

    /**
     * Get vendor by ID
     */
    public function getById($id) {
        $query = "SELECT id, name, email, phone, category, description, rating, 
                         status, verified, created_at 
                  FROM vendors 
                  WHERE id = ?";

        $vendors = $this->db->select($query, [$id]);
        return !empty($vendors) ? $vendors[0] : null;
    }

    /**
     * Get vendors by category
     */
    public function getByCategory($category, $limit = 50) {
        $query = "SELECT id, name, email, phone, category, description, rating, 
                         status, verified, created_at 
                  FROM vendors 
                  WHERE category = ? AND status = 'active'
                  ORDER BY rating DESC, created_at DESC
                  LIMIT ?";

        $vendors = $this->db->select($query, [$category, $limit]);
        return $vendors !== null ? $vendors : [];
    }

    /**
     * Get verified vendors only
     */
    public function getVerified($limit = 50) {
        $query = "SELECT id, name, email, phone, category, description, rating, 
                         status, verified, created_at 
                  FROM vendors 
                  WHERE verified = 1 AND status = 'active'
                  ORDER BY rating DESC
                  LIMIT ?";

        $vendors = $this->db->select($query, [$limit]);
        return $vendors !== null ? $vendors : [];
    }

    /**
     * Create new vendor
     */
    public function create($data) {
        // Validate required fields
        $required = ['name', 'email', 'phone', 'category'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '{$field}' is required"];
            }
        }

        // Check if email already exists
        $existing = $this->db->select("SELECT id FROM vendors WHERE email = ?", [$data['email']]);
        if (!empty($existing)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $insertData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'category' => $data['category'],
            'description' => $data['description'] ?? '',
            'rating' => $data['rating'] ?? 0,
            'status' => $data['status'] ?? 'pending',
            'verified' => $data['verified'] ?? 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $this->db->insert('vendors', $insertData);

        if ($id === false) {
            return ['success' => false, 'message' => 'Failed to create vendor'];
        }

        return [
            'success' => true,
            'message' => 'Vendor created successfully',
            'data' => ['id' => $id]
        ];
    }

    /**
     * Update vendor
     */
    public function update($id, $data) {
        // Check if vendor exists
        if (!$this->getById($id)) {
            return ['success' => false, 'message' => 'Vendor not found'];
        }

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            // Check if new email is unique
            $existing = $this->db->select(
                "SELECT id FROM vendors WHERE email = ? AND id != ?",
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
        if (isset($data['category'])) {
            $updateData['category'] = $data['category'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['rating'])) {
            $updateData['rating'] = (float)$data['rating'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['verified'])) {
            $updateData['verified'] = (int)$data['verified'];
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $affected = $this->db->update('vendors', $updateData, 'id = ?', [$id]);

        if ($affected === false) {
            return ['success' => false, 'message' => 'Failed to update vendor'];
        }

        return ['success' => true, 'message' => 'Vendor updated successfully'];
    }

    /**
     * Delete vendor
     */
    public function delete($id) {
        if (!$this->getById($id)) {
            return ['success' => false, 'message' => 'Vendor not found'];
        }

        $affected = $this->db->delete('vendors', 'id = ?', [$id]);

        if ($affected === false) {
            return ['success' => false, 'message' => 'Failed to delete vendor'];
        }

        return ['success' => true, 'message' => 'Vendor deleted successfully'];
    }

    /**
     * Search vendors
     */
    public function search($term, $limit = 50) {
        $query = "SELECT id, name, email, phone, category, description, rating, 
                         status, verified, created_at 
                  FROM vendors 
                  WHERE name LIKE ? OR email LIKE ? OR category LIKE ?
                  ORDER BY rating DESC
                  LIMIT ?";

        $searchTerm = "%{$term}%";
        $vendors = $this->db->select($query, [$searchTerm, $searchTerm, $searchTerm, $limit]);
        return $vendors !== null ? $vendors : [];
    }
}
?>
