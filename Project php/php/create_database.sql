-- ==============================================
-- myStore Database Creation Script
-- ==============================================

-- Create database
CREATE DATABASE IF NOT EXISTS myStore 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

USE myStore;

-- Drop existing tables in correct order (foreign keys)
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS order_groups;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS menu_items;

-- ==============================================
-- Table 1: users
-- Stores customer account information
-- ==============================================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Customer accounts';

-- ==============================================
-- Table 2: menu_items
-- Stores available food items for ordering
-- ==============================================
CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
  image_url VARCHAR(255) DEFAULT '',
  options_text VARCHAR(255) DEFAULT 'Regular',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_active (is_active),
  INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Food menu items';

-- ==============================================
-- Table 3: order_groups
-- Stores order headers (one per checkout)
-- Links to users table
-- ==============================================
CREATE TABLE order_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  security_code VARCHAR(32) NOT NULL UNIQUE,
  total_amount DECIMAL(10,2) NOT NULL CHECK (total_amount >= 0),
  status ENUM('active','canceled','completed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_security (security_code),
  INDEX idx_status (status),
  INDEX idx_created (created_at),
  INDEX idx_user_status (user_id, status),
  CONSTRAINT fk_order_group_user 
    FOREIGN KEY (user_id) 
    REFERENCES users(id) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Order groups (checkout sessions)';

-- ==============================================
-- Table 4: order_items
-- Stores individual items within each order
-- Links to order_groups table
-- ==============================================
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_group_id INT NOT NULL,
  item_id INT NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
  option_text VARCHAR(100) DEFAULT NULL,
  quantity INT NOT NULL DEFAULT 1 CHECK (quantity > 0),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order_group (order_group_id),
  INDEX idx_item (item_id),
  INDEX idx_created (created_at),
  CONSTRAINT fk_order_item_group 
    FOREIGN KEY (order_group_id) 
    REFERENCES order_groups(id) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Items within orders';

-- ==============================================
-- Sample Data: Menu Items
-- ==============================================
INSERT INTO menu_items (name, price, image_url, options_text, is_active) VALUES
('Pizza Margherita', 250.00, 'https://via.placeholder.com/400x200?text=Pizza+Margherita', 'Regular|Extra Cheese|Large', 1),
('Chole Bhature', 180.00, 'https://via.placeholder.com/400x200?text=Chole+Bhature', 'Regular|Extra Chole|Less Oil', 1),
('Veg Burger', 120.00, 'https://via.placeholder.com/400x200?text=Veg+Burger', 'Regular|Cheese|Spicy', 1),
('Paneer Tikka', 200.00, 'https://via.placeholder.com/400x200?text=Paneer+Tikka', 'Regular|Spicy|Mild', 1),
('Pasta Alfredo', 220.00, 'https://via.placeholder.com/400x200?text=Pasta+Alfredo', 'Regular|Extra Cheese|Spicy', 1),
('Masala Dosa', 150.00, 'https://via.placeholder.com/400x200?text=Masala+Dosa', 'Regular|Butter|Extra Crispy', 1),
('Biryani', 280.00, 'https://via.placeholder.com/400x200?text=Biryani', 'Veg|Paneer|Extra Spicy', 1),
('Spring Rolls', 100.00, 'https://via.placeholder.com/400x200?text=Spring+Rolls', 'Regular|Extra Crispy|Sweet & Sour', 1),
('Samosa', 40.00, 'https://via.placeholder.com/400x200?text=Samosa', 'Regular|Spicy|Sweet', 1),
('Tandoori Roti', 30.00, 'https://via.placeholder.com/400x200?text=Tandoori+Roti', 'Regular|Butter|Garlic', 1);

-- ==============================================
-- Sample Data: Test User
-- Password: test123 (hashed with bcrypt)
-- ==============================================
INSERT INTO users (name, email, password_hash) VALUES
('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ==============================================
-- Sample Data: Test Orders
-- ==============================================
-- Order 1 for user_id=1 (Test User)
INSERT INTO order_groups (user_id, security_code, total_amount, status) VALUES
(1, 'mkTEST001', 550.00, 'active');

INSERT INTO order_items (order_group_id, item_id, item_name, price, option_text, quantity) VALUES
(1, 1, 'Pizza Margherita', 250.00, 'Extra Cheese', 1),
(1, 2, 'Chole Bhature', 180.00, 'Regular', 1),
(1, 3, 'Veg Burger', 120.00, 'Cheese', 1);

-- Order 2 for user_id=2 (John Doe)
INSERT INTO order_groups (user_id, security_code, total_amount, status) VALUES
(2, 'mkTEST002', 430.00, 'active');

INSERT INTO order_items (order_group_id, item_id, item_name, price, option_text, quantity) VALUES
(2, 4, 'Paneer Tikka', 200.00, 'Spicy', 1),
(2, 5, 'Pasta Alfredo', 220.00, 'Regular', 1),
(2, 9, 'Samosa', 40.00, 'Spicy', 2);

-- ==============================================
-- Verification Queries
-- ==============================================

-- Show all tables
SHOW TABLES;

-- Show table structures
DESCRIBE users;
DESCRIBE menu_items;
DESCRIBE order_groups;
DESCRIBE order_items;

-- Show foreign key relationships
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    information_schema.KEY_COLUMN_USAGE
WHERE
    TABLE_SCHEMA = 'myStore'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Count records
SELECT 'users' AS table_name, COUNT(*) AS record_count FROM users
UNION ALL
SELECT 'menu_items', COUNT(*) FROM menu_items
UNION ALL
SELECT 'order_groups', COUNT(*) FROM order_groups
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items;

-- ==============================================
-- Useful Test Queries
-- ==============================================

-- View all orders with user info
SELECT 
    og.id AS order_id,
    og.security_code,
    u.name AS customer_name,
    u.email,
    og.total_amount,
    og.status,
    og.created_at
FROM order_groups og
JOIN users u ON og.user_id = u.id
ORDER BY og.created_at DESC;

-- View order items with details
SELECT 
    oi.id,
    og.security_code,
    u.name AS customer_name,
    oi.item_name,
    oi.option_text,
    oi.quantity,
    oi.price,
    (oi.price * oi.quantity) AS subtotal
FROM order_items oi
JOIN order_groups og ON oi.order_group_id = og.id
JOIN users u ON og.user_id = u.id
ORDER BY og.created_at DESC, oi.id;

-- View orders for specific user
SELECT 
    og.*,
    COUNT(oi.id) AS item_count
FROM order_groups og
LEFT JOIN order_items oi ON og.id = oi.order_group_id
WHERE og.user_id = 1
GROUP BY og.id
ORDER BY og.created_at DESC;

-- ==============================================
-- Database Setup Complete
-- ==============================================
SELECT 'Database myStore created successfully!' AS status;
