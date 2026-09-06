-- Add email verification fields for existing installations.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_email_verification_token (token),
    INDEX idx_email_verifications_user_id (user_id),
    INDEX idx_email_verifications_expires_at (expires_at)
);
