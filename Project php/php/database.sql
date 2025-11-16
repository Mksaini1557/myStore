-- Database Setup for myStore
-- Run this in phpMyAdmin SQL tab

CREATE DATABASE IF NOT EXISTS myStore CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE myStore;

-- Drop existing tables (careful with production data)
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS order_groups;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS menu_items;

-- Users table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu items table
CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image_url VARCHAR(255) DEFAULT '',
  options_text VARCHAR(255) DEFAULT 'Regular',
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order groups table
CREATE TABLE order_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  security_code VARCHAR(32) UNIQUE NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('active','canceled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_security (security_code),
  CONSTRAINT fk_group_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_group_id INT NOT NULL,
  item_id INT NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  option_text VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_group (order_group_id),
  CONSTRAINT fk_item_group FOREIGN KEY (order_group_id) REFERENCES order_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data
INSERT INTO menu_items (name, price, image_url, options_text, is_active) VALUES
('Pizza Margherita', 250.00, 'https://placehold.co/400x200?text=Pizza+Margherita', 'Regular|Extra Cheese|Large', 1),
('Chole Bhature', 180.00, 'assets/images/placeholder.jpg', 'Regular|Extra Chole|Less Oil', 1),
('Veg Burger', 120.00, 'assets/images/placeholder.jpg', 'Regular|Cheese|Spicy', 1),
('Spring Rolls', 100.00, 'https://placehold.co/400x200?text=Spring+Rolls', 'Regular|Extra Crispy|Sweet & Sour', 1);