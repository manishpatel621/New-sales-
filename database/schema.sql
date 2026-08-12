-- =====================================================================
-- FILE: database/schema.sql
-- Complete Order Management System - Database Schema
-- Import this file first in phpMyAdmin / MySQL before running the site
-- =====================================================================

CREATE DATABASE IF NOT EXISTS order_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE order_management;

-- ---------------------------------------------------------------------
-- TABLE: admins
-- ---------------------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin login -> username: admin | password: admin123
INSERT INTO admins (username, password, full_name) VALUES
('admin', '$2b$10$h3SPAPKzh9Dlmy4CZr4zNu.faeBVllwcVM8SXEmla2QKao8Dtxveq', 'Administrator');
-- NOTE: hash above corresponds to "admin123" (bcrypt, verified working with PHP password_verify)

-- ---------------------------------------------------------------------
-- TABLE: categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    whatsapp_number VARCHAR(20) DEFAULT NULL,   -- optional: overrides global WhatsApp number for this section
    telegram_chat_id VARCHAR(50) DEFAULT NULL,  -- optional: overrides global Telegram Chat ID for this section
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE: subcategories
-- ---------------------------------------------------------------------
CREATE TABLE subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE: products
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_id INT DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    brand VARCHAR(100),
    unit VARCHAR(50) DEFAULT 'pcs',
    size VARCHAR(50),
    color VARCHAR(50),
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    status ENUM('show','hide') DEFAULT 'show',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE: product_images (multiple images per product)
-- ---------------------------------------------------------------------
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE: customers
-- ---------------------------------------------------------------------
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(10) NOT NULL UNIQUE,   -- Auto: C0001, C0002...
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    password VARCHAR(255) NOT NULL,
    customer_type ENUM('regular','vip') DEFAULT 'regular',
    status ENUM('pending','approved','rejected','blacklist') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE: orders
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    status ENUM('pending','accepted','ready','delivered','cancelled') DEFAULT 'pending',
    customer_note TEXT,
    admin_note TEXT,
    order_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE: order_items
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE: settings (key-value store for Shop Name, WhatsApp Number, etc.)
-- ---------------------------------------------------------------------
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255)
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('shop_name', 'My Shop'),
('whatsapp_number', '919826293234'),
('currency_symbol', '₹'),
('low_stock_alert', '5'),
('telegram_bot_token', ''),
('telegram_chat_id', '');

-- Used to display shop contact numbers (4-5 or more) and any message
-- ---------------------------------------------------------------------
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT,
    phone_numbers VARCHAR(255),   -- comma separated, e.g. 9876543210,9123456780
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO announcements (title, message, phone_numbers, status) VALUES
('हमसे संपर्क करें', 'ऑर्डर से जुड़ी किसी भी जानकारी के लिए नीचे दिए नंबरों पर कॉल या WhatsApp करें।', '9876543210,9123456780', 'active');

-- =====================================================================
-- SAMPLE DATA
-- =====================================================================
INSERT INTO categories (name) VALUES ('Electronics'), ('Clothing'), ('Grocery');

INSERT INTO subcategories (category_id, name) VALUES
(1, 'Mobiles'), (1, 'Laptops'), (2, 'Men Wear'), (2, 'Women Wear'), (3, 'Snacks');

INSERT INTO products (category_id, subcategory_id, name, description, brand, unit, size, color, price, stock) VALUES
(1, 1, 'Smartphone X10', 'A great budget smartphone with 6GB RAM.', 'BrandX', 'pcs', '6.5 inch', 'Black', 15999.00, 25),
(2, 3, 'Cotton T-Shirt', 'Comfortable 100% cotton t-shirt.', 'StyleCo', 'pcs', 'L', 'Blue', 499.00, 100),
(3, 5, 'Potato Chips 100g', 'Crispy salted potato chips.', 'SnackTime', 'packet', '100g', 'N/A', 40.00, 500);

INSERT INTO customers (client_id, name, email, phone, address, password, customer_type, status) VALUES
('C0001', 'Ravi Kumar', 'ravi@example.com', '9876543210', 'Delhi, India', '$2b$10$h3SPAPKzh9Dlmy4CZr4zNu.faeBVllwcVM8SXEmla2QKao8Dtxveq', 'vip', 'approved');
-- password for sample customer is also "admin123" (for testing)
