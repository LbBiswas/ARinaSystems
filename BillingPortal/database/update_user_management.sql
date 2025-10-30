-- Update users table to support user management features
-- Run this if your users table doesn't have these columns

-- Add status column if it doesn't exist
ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'active';

-- Add updated_at column if it doesn't exist
ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL;

-- Add last_login column if it doesn't exist
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL;

-- Update existing users to have active status
UPDATE users SET status = 'active' WHERE status IS NULL;

-- Add index on status for better performance
CREATE INDEX idx_users_status ON users(status);

-- Add index on user_type for better performance
CREATE INDEX idx_users_type ON users(user_type);

-- Add index on created_at for better performance
CREATE INDEX idx_users_created ON users(created_at);

-- Sample data update - make sure demo users are active
UPDATE users SET status = 'active' WHERE username IN ('admin', 'demo');

-- Create activity_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_user (user_id),
    INDEX idx_activity_date (created_at),
    INDEX idx_activity_action (action)
);