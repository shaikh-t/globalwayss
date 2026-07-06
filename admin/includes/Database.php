<?php
/**
 * Database Connection Class - MySQLi Object-Oriented
 * GlobalWays Backend - Lightweight vanilla PHP
 */

class Database {
    private $host = 'localhost';
    private $user = 'root';
    private $password = 'lefkedev77';
    private $database = 'gw_mainsite';
    private $connection;
    private $error;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->connect();
    }

    /**
     * Connect to MySQL database using MySQLi OOP
     */
    private function connect() {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->database
            );

            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            // Set charset to utf8
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->handleError();
        }
    }

    /**
     * Get the mysqli connection object
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute SELECT query with prepared statement
     */
    public function select($query, $params = []) {
        if (!$this->connection) { return null; }
        try {
            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            // Bind parameters if provided
            if (!empty($params)) {
                $types = '';
                $values = [];

                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $values[] = $param;
                }

                // Bind all parameters at once
                $stmt->bind_param($types, ...$values);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            $stmt->close();
            return $data;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    /**
     * Execute INSERT query with prepared statement
     */
    public function insert($table, $data) {
        if (!$this->connection) { return false; }
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";

            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            // Build type string and values array
            $types = '';
            $values = [];

            foreach ($data as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $value;
            }

            // Bind parameters
            $stmt->bind_param($types, ...$values);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $lastId = $this->connection->insert_id;
            $stmt->close();
            return $lastId;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Execute UPDATE query with prepared statement
     */
    public function update($table, $data, $where, $whereParams = []) {
        if (!$this->connection) { return false; }
        try {
            $set = [];
            $values = [];

            foreach ($data as $column => $value) {
                $set[] = "$column = ?";
                $values[] = $value;
            }

            $setString = implode(', ', $set);
            $query = "UPDATE $table SET $setString WHERE $where";

            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            // Merge all values for binding
            $allValues = array_merge($values, $whereParams);
            $types = '';

            foreach ($allValues as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }

            $stmt->bind_param($types, ...$allValues);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affectedRows = $this->connection->affected_rows;
            $stmt->close();
            return $affectedRows;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Execute DELETE query with prepared statement
     */
    public function delete($table, $where, $params = []) {
        if (!$this->connection) { return false; }
        try {
            $query = "DELETE FROM $table WHERE $where";
            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            if (!empty($params)) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                }
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affectedRows = $this->connection->affected_rows;
            $stmt->close();
            return $affectedRows;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get last error
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Handle errors
     */
    private function handleError() {
        error_log($this->error);
    }

    /**
     * Destructor - Close connection
     */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>
