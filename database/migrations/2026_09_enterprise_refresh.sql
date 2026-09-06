-- Cybte AI enterprise refresh migration
-- Apply to the existing Cybte database before deploying this branch.
-- Compatible with MySQL 8.x and safe to re-run.

DROP PROCEDURE IF EXISTS cybte_add_refresh_user_columns;
DELIMITER //
CREATE PROCEDURE cybte_add_refresh_user_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified'
    ) THEN
        ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at'
    ) THEN
        ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL;
    END IF;
END//
DELIMITER ;
CALL cybte_add_refresh_user_columns();
DROP PROCEDURE cybte_add_refresh_user_columns;

DROP PROCEDURE IF EXISTS cybte_add_fraud_user_scope;
DELIMITER //
CREATE PROCEDURE cybte_add_fraud_user_scope()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fraud_scores' AND COLUMN_NAME = 'user_id'
    ) THEN
        ALTER TABLE fraud_scores ADD COLUMN user_id INT NULL AFTER id;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fraud_scores' AND INDEX_NAME = 'idx_fraud_scores_user_created'
    ) THEN
        CREATE INDEX idx_fraud_scores_user_created ON fraud_scores (user_id, created_at);
    END IF;
END//
DELIMITER ;
CALL cybte_add_fraud_user_scope();
DROP PROCEDURE cybte_add_fraud_user_scope;

DROP PROCEDURE IF EXISTS cybte_add_scan_user_scope;
DELIMITER //
CREATE PROCEDURE cybte_add_scan_user_scope()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scan_results' AND COLUMN_NAME = 'user_id'
    ) THEN
        ALTER TABLE scan_results ADD COLUMN user_id INT NULL AFTER id;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scan_results' AND INDEX_NAME = 'idx_scan_results_user_created'
    ) THEN
        CREATE INDEX idx_scan_results_user_created ON scan_results (user_id, created_at);
    END IF;
END//
DELIMITER ;
CALL cybte_add_scan_user_scope();
DROP PROCEDURE cybte_add_scan_user_scope;

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
