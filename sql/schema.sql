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
    inr_wallet DECIMAL(12, 2) DEFAULT 0.00,
    silver_wallet DECIMAL(10, 4) DEFAULT 0.00,
    sip_active TINYINT(1) DEFAULT 0,
    sip_amount DECIMAL(10, 2) DEFAULT 0.00,
    sip_frequency ENUM('daily', 'monthly') DEFAULT 'monthly',
    sip_last_deducted DATETIME,
    referred_by INT,
    japsan_wallet DECIMAL(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- System Settings
CREATE TABLE system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
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

-- Silver rates table
CREATE TABLE silver_rates (
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
    type ENUM('buy', 'sell', 'delivery', 'deposit', 'sip_penalty') NOT NULL,
    amount_inr DECIMAL(12, 2),
    gold_grams DECIMAL(10, 4),
    gold_rate DECIMAL(10, 2),
    metal_type ENUM('gold', 'silver', 'fiat') DEFAULT 'gold',
    transaction_source ENUM('direct', 'sip', 'referral', 'wallet') DEFAULT 'direct',
    status ENUM('pending', 'completed', 'rejected', 'processing') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_id VARCHAR(100),
    notes TEXT,
    description TEXT,
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
    total_cost DECIMAL(10, 2) DEFAULT 0.00,
    metal_type ENUM('gold', 'silver') DEFAULT 'gold',
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

-- Support Tickets
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'pending', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- SIP Plans
CREATE TABLE sip_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(255) NOT NULL,
    min_amount DECIMAL(10, 2) DEFAULT 500,
    max_amount DECIMAL(10, 2) DEFAULT 999999,
    frequency ENUM('daily', 'monthly') DEFAULT 'monthly',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Lock-in Plans
CREATE TABLE lock_in_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100),
    months INT NOT NULL,
    return_percentage DECIMAL(5,2) NOT NULL,
    min_investment DECIMAL(10,2) DEFAULT 1,
    max_investment DECIMAL(10,2),
    penalty_percentage DECIMAL(5,2) DEFAULT 0,
    metal_type ENUM('gold', 'silver') DEFAULT 'gold',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Lock-ins
CREATE TABLE user_lock_ins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    gold_grams DECIMAL(10,4) DEFAULT 0.0000,
    silver_grams DECIMAL(10,4) DEFAULT 0.0000,
    metal_type ENUM('gold', 'silver') DEFAULT 'gold',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('active', 'completed', 'early_unlock', 'unlocked', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User gold summary view
CREATE OR REPLACE VIEW user_gold_summary AS
SELECT 
    u.id as user_id,
    u.name,
    u.mobile,
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' AND (t.metal_type = 'gold' OR t.metal_type IS NULL) THEN t.gold_grams ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN t.type IN ('sell', 'delivery') AND t.status = 'completed' AND (t.metal_type = 'gold' OR t.metal_type IS NULL) THEN t.gold_grams ELSE 0 END), 0) as total_gold_grams,
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' AND (t.metal_type = 'gold' OR t.metal_type IS NULL) THEN t.amount_inr ELSE 0 END), 0) as total_invested_inr
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
WHERE u.is_admin = 0
GROUP BY u.id, u.name, u.mobile;

-- User summary view
CREATE OR REPLACE VIEW user_summary AS
SELECT 
    u.id as user_id,
    u.name,
    u.mobile,
    u.inr_wallet,
    u.silver_wallet,
    u.japsan_wallet,
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' AND t.metal_type = 'gold' THEN t.gold_grams ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN t.type IN ('sell', 'delivery') AND t.status = 'completed' AND t.metal_type = 'gold' THEN t.gold_grams ELSE 0 END), 0) as total_gold_grams,
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' AND t.metal_type = 'silver' THEN t.gold_grams ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN t.type IN ('sell', 'delivery') AND t.status = 'completed' AND t.metal_type = 'silver' THEN t.gold_grams ELSE 0 END), 0) as total_silver_grams,
    COALESCE(SUM(CASE WHEN t.type = 'buy' AND t.status = 'completed' THEN t.amount_inr ELSE 0 END), 0) as total_invested_inr
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
WHERE u.is_admin = 0
GROUP BY u.id, u.name, u.mobile, u.inr_wallet, u.silver_wallet, u.japsan_wallet;

-- Insert default admin
INSERT IGNORE INTO users (name, mobile, is_active, is_admin) 
VALUES ('Admin', '9999999999', 1, 1);

-- Insert sample gold rate
INSERT IGNORE INTO gold_rates (rate_per_gram, rate_date) 
VALUES (6850.00, CURDATE());

-- Insert sample silver rate
INSERT IGNORE INTO silver_rates (rate_per_gram, rate_date) 
VALUES (85.00, CURDATE());

-- Default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
('delivery_charge', '150'),
('package_charge', '50'),
('forwarding_charge', '100'),
('sip_penalty_charge', '50');

-- Default lock-in plans
INSERT IGNORE INTO lock_in_plans (months, return_percentage, metal_type) VALUES 
(6, 5.00, 'gold'), 
(12, 8.00, 'gold'), 
(24, 10.00, 'gold'), 
(36, 12.00, 'gold'),
(6, 5.00, 'silver'), 
(12, 8.00, 'silver'), 
(24, 10.00, 'silver'), 
(36, 12.00, 'silver');
