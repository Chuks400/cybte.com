CREATE DATABASE cybte;

USE cybte;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(255),
    role VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE identity_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    document_type VARCHAR(50),
    document_number VARCHAR(100),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fraud_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100),
    risk_score INT,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE scan_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_url VARCHAR(255),
    vulnerabilities TEXT,
    severity VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(50) NOT NULL,
    service_type VARCHAR(50) NOT NULL,
    start_date DATETIME NULL,
    expiry_date DATETIME NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subscriptions_user_id (user_id),
    INDEX idx_subscriptions_service_type (service_type),
    CONSTRAINT fk_subscriptions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE vpn_servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'online',
    load_percent INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vpn_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    server_id INT NULL,
    subscription_link TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vpn_accounts_user_id (user_id),
    INDEX idx_vpn_accounts_server_id (server_id),
    CONSTRAINT fk_vpn_accounts_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_vpn_accounts_server_id FOREIGN KEY (server_id) REFERENCES vpn_servers(id) ON DELETE SET NULL
);