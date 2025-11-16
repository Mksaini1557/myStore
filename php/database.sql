-- SETUP NOTES (no logic changes):
-- 1. In phpMyAdmin: create database myStore then run this script.
-- 2. Ensure user 'root' has no password or adjust in config.php.
-- 3. After import, verify tables: users, menu_items, order_groups, order_items.
-- 4. For existing data do NOT re-run drops (remove DROP lines if preserving data).

-- Drop legacy tables if present
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS order_groups;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS menu_items;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image_url VARCHAR(255) DEFAULT '',
  options_text VARCHAR(255) DEFAULT 'Regular',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  security_code VARCHAR(32) NOT NULL UNIQUE,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('cooking','cooked','canceled') NOT NULL DEFAULT 'cooking',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  CONSTRAINT fk_group_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_group_id INT NOT NULL,
  item_id INT NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  option_text VARCHAR(100),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_group (order_group_id),
  CONSTRAINT fk_item_group FOREIGN KEY (order_group_id) REFERENCES order_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO menu_items (name, price, image_url, options_text, is_active) VALUES
('Pizza Margherita', 250.00, 'https://via.placeholder.com/400x200?text=Pizza+Margherita', 'Regular|Extra Cheese|Large', 1),
('Chole Bhature', 180.00, 'https://via.placeholder.com/400x200?text=Chole+Bhature', 'Regular|Extra Chole|Less Oil', 1),
('Veg Burger', 120.00, 'https://via.placeholder.com/400x200?text=Veg+Burger', 'Regular|Cheese|Spicy', 1),
('Spring Rolls', 100.00, 'https://via.placeholder.com/400x200?text=Spring+Rolls', 'Regular|Extra Crispy|Sweet & Sour', 1);

-- Add admin user (password: admin123)
INSERT INTO admins (name, email, password_hash, role) VALUES
('Admin', 'admin@mystore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Remove old admin from users table (optional cleanup)
-- DELETE FROM users WHERE email = 'admin@mystore.com';
-- END SETUP NOTES