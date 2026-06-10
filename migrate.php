<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

$queries = [
    // Add columns to users table individually (avoids all-or-nothing failures if some exist)
    "ALTER TABLE users ADD COLUMN inr_wallet DECIMAL(12, 2) DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN silver_wallet DECIMAL(10, 4) DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN sip_active TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN sip_amount DECIMAL(10, 2) DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN sip_frequency ENUM('daily', 'monthly') DEFAULT 'monthly'",
    "ALTER TABLE users ADD COLUMN sip_last_deducted DATETIME",
    "ALTER TABLE users ADD COLUMN referred_by INT",
    "ALTER TABLE users ADD COLUMN japsan_wallet DECIMAL(12, 2) DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN fcm_token VARCHAR(255)",
     
    // Add columns to transactions table individually
    "ALTER TABLE transactions ADD COLUMN metal_type ENUM('gold', 'silver') DEFAULT 'gold'",
    "ALTER TABLE transactions ADD COLUMN transaction_source ENUM('direct', 'sip', 'referral', 'wallet') DEFAULT 'direct'",

    "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('open', 'pending', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",

    "CREATE TABLE IF NOT EXISTS silver_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rate_per_gram DECIMAL(10, 2) NOT NULL,
        rate_date DATE NOT NULL UNIQUE,
        updated_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id)
    )",

    "CREATE TABLE IF NOT EXISTS lock_in_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        months INT NOT NULL,
        return_percentage DECIMAL(5,2) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS user_lock_ins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_id INT NOT NULL,
        gold_grams DECIMAL(10,4) NOT NULL,
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        status ENUM('active', 'completed', 'early_unlock') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
        ('delivery_charge', '150'),
        ('package_charge', '50'),
        ('forwarding_charge', '100'),
        ('sip_penalty_charge', '50')",

    "INSERT IGNORE INTO lock_in_plans (months, return_percentage) VALUES (6, 5.00), (12, 8.00), (24, 10.00), (36, 12.00)",

    "INSERT IGNORE INTO silver_rates (rate_per_gram, rate_date) VALUES (85.00, CURDATE())",

    "CREATE OR REPLACE VIEW user_summary AS
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
    GROUP BY u.id, u.name, u.mobile, u.inr_wallet, u.silver_wallet, u.japsan_wallet",

    // Clean up empty dummy table/view and recreate user_gold_summary as a dynamic view
    "DROP TABLE IF EXISTS user_gold_summary",
    "DROP VIEW IF EXISTS user_gold_summary",
    "CREATE VIEW user_gold_summary AS
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
    GROUP BY u.id, u.name, u.mobile"
];

foreach ($queries as $query) {
    try {
        $conn->exec($query);
        echo "Executed successfully: " . substr(trim(preg_replace('/\s+/', ' ', $query)), 0, 60) . "...\n";
    } catch (PDOException $e) {
        // Suppress "Duplicate column name" (1060) or "Duplicate key name" (1061) or "Table already exists" (1050)
        if (in_array($e->errorInfo[1], [1060, 1061, 1050])) {
            echo "Already exists (Skipped): " . substr(trim(preg_replace('/\s+/', ' ', $query)), 0, 60) . "...\n";
        } else {
            echo "Error: " . $e->getMessage() . " on query: " . substr($query, 0, 60) . "...\n";
        }
    }
}
echo "Migration complete.\n";
