-- Cybte AI enterprise refresh migration
-- Apply to the existing Cybte database before deploying this branch.

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
    CONSTRAINT fk_refresh_ev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_refresh_ev_token (token),
    INDEX idx_refresh_ev_user (user_id),
    INDEX idx_refresh_ev_expires (expires_at)
);

CREATE TABLE IF NOT EXISTS enterprise_contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    company VARCHAR(190) NULL,
    role VARCHAR(120) NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    source_ip_hash CHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_enterprise_contacts_status_created (status, created_at),
    INDEX idx_enterprise_contacts_email (email)
);

CREATE TABLE IF NOT EXISTS vault_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(190) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    encryption_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vault_documents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_vault_stored_name (stored_name),
    INDEX idx_vault_documents_user_created (user_id, created_at)
);

CREATE TABLE IF NOT EXISTS vault_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_id BIGINT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    detail VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vault_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_vault_audit_document FOREIGN KEY (document_id) REFERENCES vault_documents(id) ON DELETE SET NULL,
    INDEX idx_vault_audit_user_created (user_id, created_at),
    INDEX idx_vault_audit_document (document_id)
);
