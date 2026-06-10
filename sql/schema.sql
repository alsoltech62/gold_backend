-- Gold Saving Platform Database Schema
-- Run this on your MySQL server

CREATE DATABASE IF NOT EXISTS gold_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gold_platform;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(10),
    aadhar_number VARCHAR(20),
    pan_number VARCHAR(20),
    profile_photo VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    is_admin TINYINT(1) DEFAULT 0,
    otp VARCHAR(10),
    otp_expires_at DATETIME,
    fcm_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Gold rates table
CREATE TABLE gold_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_per_gram DECIMAL(10, 2) NOT NULL,
    rate_date DATE NOT NULL UNIQUE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Transactions table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('buy', 'sell', 'delivery') NOT NULL,
    amount_inr DECIMAL(12, 2),
    gold_grams DECIMAL(10, 4),
    gold_rate DECIMAL(10, 2),
    status ENUM('pending', 'completed', 'rejected', 'processing') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_id VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Delivery requests table
CREATE TABLE delivery_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_id INT,
    gold_grams DECIMAL(10, 4) NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_city VARCHAR(100),
    delivery_state VARCHAR(100),
    delivery_pincode VARCHAR(10),
    status ENUM('pending', 'processing', 'dispatched', 'delivered', 'cancelled') DEFAULT 'pending',
    tracking_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('transaction', 'gold_rate', 'delivery', 'general') DEFAULT 'general',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User gold summary view
CREATE VIEW user_gold_summary AS
SELECT 
    u.id as user_id,
    u.name,
    u.mobile,
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' THEN t.gold_grams ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN t.type IN ('sell', 'delivery') AND t.status = 'completed' THEN t.gold_grams ELSE 0 END), 0) as total_gold_grams,
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' THEN t.amount_inr ELSE 0 END), 0) as total_invested_inr
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
WHERE u.is_admin = 0
GROUP BY u.id, u.name, u.mobile;

-- Insert default admin
INSERT INTO users (name, mobile, is_active, is_admin) 
VALUES ('Admin', '9999999999', 1, 1);

-- Insert sample gold rate
INSERT INTO gold_rates (rate_per_gram, rate_date) 
VALUES (6850.00, CURDATE());

