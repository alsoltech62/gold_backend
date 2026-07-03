<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    
    try {
        $db->exec("ALTER TABLE users 
            ADD COLUMN bank_name VARCHAR(100) DEFAULT NULL,
            ADD COLUMN account_number VARCHAR(50) DEFAULT NULL,
            ADD COLUMN ifsc_code VARCHAR(20) DEFAULT NULL,
            ADD COLUMN account_holder_name VARCHAR(100) DEFAULT NULL;
        ");
        echo "Added bank details to users table.\n";
    } catch(Exception $e) {
        echo "Column might already exist: " . $e->getMessage() . "\n";
    }

    // 2. Create withdrawal_requests table
    $db->exec("CREATE TABLE IF NOT EXISTS withdrawal_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(12, 2) NOT NULL,
        bank_name VARCHAR(100),
        account_number VARCHAR(50),
        ifsc_code VARCHAR(20),
        account_holder_name VARCHAR(100),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Created withdrawal_requests table.\n";

    // 3. Create referral_rewards table to track referral history
    $db->exec("CREATE TABLE IF NOT EXISTS referral_rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        referrer_id INT NOT NULL,
        referred_user_id INT NOT NULL,
        reward_type ENUM('silver', 'gold', 'inr') DEFAULT 'silver',
        reward_amount DECIMAL(10, 4) NOT NULL,
        transaction_id INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created referral_rewards table.\n";

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
