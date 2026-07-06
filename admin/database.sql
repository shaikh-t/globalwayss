-- GlobalWays Database Schema
-- MySQL 5.7+

-- Create Database
CREATE DATABASE IF NOT EXISTS gw_mainsite;
USE gw_mainsite;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20),
  role ENUM('admin', 'vendor', 'customer') DEFAULT 'customer',
  status ENUM('active', 'inactive') DEFAULT 'active',
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendors Table
CREATE TABLE IF NOT EXISTS vendors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20),
  category VARCHAR(100) NOT NULL,
  description LONGTEXT,
  rating DECIMAL(3, 2) DEFAULT 0,
  status ENUM('pending', 'active', 'inactive') DEFAULT 'pending',
  verified TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_category (category),
  INDEX idx_status (status),
  INDEX idx_verified (verified),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data (Optional)
 INSERT INTO users (name, email, phone, role, status, password) VALUES
 ('Admin User', 'admin@globalways.com', '+971501234567', 'admin', 'active', '$2y$10$abcdefghijklmnopqrstuvwxyz'),
 ('John Vendor', 'john@vendor.com', '+971509876543', 'vendor', 'active', '$2y$10$abcdefghijklmnopqrstuvwxyz'),
 ('Jane Customer', 'jane@customer.com', '+971505555555', 'customer', 'active', '$2y$10$abcdefghijklmnopqrstuvwxyz');

 INSERT INTO vendors (name, email, phone, category, description, rating, status, verified) VALUES
 ('Legal Services LLC', 'legal@services.com', '+971501111111', 'legal', 'Professional legal services for UAE businesses', 4.5, 'active', 1),
 ('Golden Visa Consultants', 'visa@consultants.com', '+971502222222', 'visa', 'Expert guidance for Golden Visa applications', 4.8, 'active', 1),
 ('Business Setup Pro', 'setup@business.com', '+971503333333', 'business', 'Complete business setup and registration services', 4.2, 'active', 1);
