<<<<<<< HEAD
=======
-- Add email verification columns to users table
>>>>>>> b9e31d2449ffc6d9f4c81f1e61f200ba68fe3f45
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN email_verified_at DATETIME NULL;

<<<<<<< HEAD
=======
-- Create email verifications table
>>>>>>> b9e31d2449ffc6d9f4c81f1e61f200ba68fe3f45
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
);
