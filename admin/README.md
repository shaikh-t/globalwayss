# GlobalWays Backend - Vanilla PHP

A lightweight, vanilla PHP backend for GlobalWays marketplace with no framework dependencies.

## Features

- **Object-Oriented MySQLi**: Modern PHP database operations using MySQLi OOP
- **Prepared Statements**: SQL injection prevention through parameterized queries
- **REST API**: Complete RESTful API endpoints for Users and Vendors
- **Error Handling**: Comprehensive error handling and logging
- **CORS Support**: Cross-origin request support for frontend integration
- **No Dependencies**: Pure PHP, no external frameworks required

## Project Structure

```
admin/
├── api/
│   ├── users.php         # Users API endpoint
│   └── vendors.php       # Vendors API endpoint
└── includes/
    ├── Database.php      # MySQLi database connection class
    ├── Response.php      # Standardized response handler
    ├── User.php          # User model with CRUD operations
    └── Vendor.php        # Vendor model with CRUD operations
```

## Database Setup

### Create Database

```sql
CREATE DATABASE gw_mainsite;
USE gw_mainsite;
```

### Create Tables

```sql
-- Users Table
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20),
  role ENUM('admin', 'vendor', 'customer') DEFAULT 'customer',
  status ENUM('active', 'inactive') DEFAULT 'active',
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (role),
  INDEX (status)
);

-- Vendors Table
CREATE TABLE vendors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20),
  category VARCHAR(100) NOT NULL,
  description TEXT,
  rating DECIMAL(3, 2) DEFAULT 0,
  status ENUM('pending', 'active', 'inactive') DEFAULT 'pending',
  verified TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (category),
  INDEX (status),
  INDEX (verified)
);
```

## Configuration

Edit `includes/Database.php` to configure your database connection:

```php
private $host = 'localhost';
private $user = 'root';
private $password = '';
private $database = 'gw_mainsite';
```

## API Endpoints

### Users API

#### Get All Users
```
GET /admin/api/users.php?limit=50&offset=0
```

#### Get Single User
```
GET /admin/api/users.php/1
```

#### Search Users
```
GET /admin/api/users.php?search=john
```

#### Create User
```
POST /admin/api/users.php
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+971501234567",
  "role": "customer",
  "password": "secure_password"
}
```

#### Update User
```
PUT /admin/api/users.php/1
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "status": "active"
}
```

#### Delete User
```
DELETE /admin/api/users.php/1
```

### Vendors API

#### Get All Vendors
```
GET /admin/api/vendors.php?limit=50&offset=0
```

#### Get Single Vendor
```
GET /admin/api/vendors.php/1
```

#### Get Verified Vendors
```
GET /admin/api/vendors.php?verified=1
```

#### Get Vendors by Category
```
GET /admin/api/vendors.php?category=legal_services
```

#### Search Vendors
```
GET /admin/api/vendors.php?search=lawyers
```

#### Create Vendor
```
POST /admin/api/vendors.php
Content-Type: application/json

{
  "name": "Legal Services Inc",
  "email": "legal@example.com",
  "phone": "+971501234567",
  "category": "legal_services",
  "description": "Professional legal services",
  "rating": 4.5
}
```

#### Update Vendor
```
PUT /admin/api/vendors.php/1
Content-Type: application/json

{
  "name": "Updated Legal Services",
  "verified": 1,
  "status": "active"
}
```

#### Delete Vendor
```
DELETE /admin/api/vendors.php/1
```

## Response Format

### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "timestamp": "2024-01-15 10:30:45"
}
```

### Error Response (400/500)
```json
{
  "success": false,
  "message": "Error message",
  "errors": null,
  "timestamp": "2024-01-15 10:30:45"
}
```

## Error Handling

- **400 Bad Request**: Invalid input data
- **401 Unauthorized**: Authentication required
- **404 Not Found**: Resource not found
- **405 Method Not Allowed**: HTTP method not supported
- **422 Unprocessable Entity**: Validation error
- **500 Internal Server Error**: Server-side error

## Security Features

1. **Prepared Statements**: All queries use parameterized statements to prevent SQL injection
2. **Type Binding**: Automatic type detection and binding for parameters
3. **CORS Headers**: Control cross-origin requests
4. **Error Logging**: Errors logged without exposing sensitive information to client
5. **Password Hashing**: Passwords hashed using bcrypt

## Usage Example

```php
<?php
require_once 'includes/Database.php';
require_once 'includes/User.php';
require_once 'includes/Response.php';

// Initialize database
$database = new Database();
$user = new User($database);

// Create user
$result = $user->create([
  'name' => 'John Doe',
  'email' => 'john@example.com',
  'phone' => '+971501234567',
  'role' => 'customer'
]);

if ($result['success']) {
  Response::success($result['data'], $result['message'], 201);
} else {
  Response::error($result['message'], 400);
}
?>
```

## Running the Backend

1. Ensure MySQL is running
2. Create the database and tables (see Database Setup section)
3. Update database credentials in `includes/Database.php`
4. Server should be running (Apache/Nginx with PHP)
5. Access API endpoints via HTTP requests

## Testing with curl

```bash
# Get all users
curl http://localhost/admin/api/users.php

# Create user
curl -X POST http://localhost/admin/api/users.php \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","phone":"+971501234567","role":"customer"}'

# Get single user
curl http://localhost/admin/api/users.php/1

# Update user
curl -X PUT http://localhost/admin/api/users.php/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane","status":"active"}'

# Delete user
curl -X DELETE http://localhost/admin/api/users.php/1
```

## Development Notes

- No framework dependencies - pure PHP
- Lightweight and fast
- Easy to understand and modify
- Suitable for small to medium projects
- Can be extended with additional models and endpoints

## License

This project is part of GlobalWays marketplace.
