-- Migration: Add 3x-ui API support to vpn_servers and vpn_accounts tables
-- Run this in phpMyAdmin to update your existing tables

-- ============================================
-- UPDATE vpn_servers table for 3x-ui API
-- ============================================

-- Add new columns for API integration
ALTER TABLE vpn_servers 
ADD COLUMN country VARCHAR(2) NULL AFTER location,
ADD COLUMN flag VARCHAR(10) NULL AFTER country,
ADD COLUMN panel_type VARCHAR(20) NOT NULL DEFAULT '3x-ui' AFTER flag,
ADD COLUMN api_url VARCHAR(255) NULL AFTER panel_type,
ADD COLUMN api_token VARCHAR(255) NULL AFTER api_url,
ADD COLUMN api_username VARCHAR(100) NULL AFTER api_token,
ADD COLUMN api_password VARCHAR(255) NULL AFTER api_username,
ADD COLUMN inbound_id INT DEFAULT 1 AFTER api_password,
ADD COLUMN protocol VARCHAR(20) DEFAULT 'vless' AFTER inbound_id;

-- Update existing servers to be 3x-ui by default
UPDATE vpn_servers SET panel_type = '3x-ui' WHERE panel_type IS NULL;

-- ============================================
-- UPDATE vpn_accounts table for better tracking
-- ============================================

-- Add columns for detailed account tracking
ALTER TABLE vpn_accounts 
ADD COLUMN uuid VARCHAR(36) NULL AFTER subscription_link,
ADD COLUMN client_email VARCHAR(255) NULL AFTER uuid,
ADD COLUMN traffic_used_gb DECIMAL(10,2) DEFAULT 0 AFTER client_email,
ADD COLUMN traffic_limit_gb INT DEFAULT 10 AFTER traffic_used_gb,
ADD COLUMN expires_at DATETIME NULL AFTER created_at,
ADD COLUMN last_connected_at DATETIME NULL AFTER expires_at;

-- ============================================
-- SAMPLE DATA: Insert a demo 3x-ui server
-- ============================================

-- Note: Replace with your actual 3x-ui panel credentials
-- This is just a template - update with real values before running

-- INSERT INTO vpn_servers 
-- (name, location, country, flag, ip_address, status, load_percent, panel_type, api_url, api_token, inbound_id, protocol)
-- VALUES 
-- ('USA-1', 'New York', 'US', '🇺🇸', '192.168.1.100', 'active', 45, '3x-ui', 'http://your-server-ip:54321', 'your-api-token-here', 1, 'vless'),
-- ('Germany-1', 'Frankfurt', 'DE', '🇩🇪', '192.168.1.101', 'active', 38, '3x-ui', 'http://your-server-ip:54321', 'your-api-token-here', 1, 'vless'),
-- ('Singapore-1', 'Singapore', 'SG', '🇸🇬', '192.168.1.102', 'active', 52, '3x-ui', 'http://your-server-ip:54321', 'your-api-token-here', 1, 'vless');

-- ============================================
-- INDEXES for performance
-- ============================================

CREATE INDEX idx_vpn_accounts_uuid ON vpn_accounts(uuid);
CREATE INDEX idx_vpn_accounts_client_email ON vpn_accounts(client_email);
CREATE INDEX idx_vpn_accounts_expires_at ON vpn_accounts(expires_at);
CREATE INDEX idx_vpn_servers_panel_type ON vpn_servers(panel_type);
CREATE INDEX idx_vpn_servers_status ON vpn_servers(status);

-- ============================================
-- VERIFICATION
-- ============================================

-- Check vpn_servers structure
-- DESCRIBE vpn_servers;

-- Check vpn_accounts structure  
-- DESCRIBE vpn_accounts;

-- View active servers
-- SELECT * FROM vpn_servers WHERE status = 'active';

-- View VPN accounts with server info
-- SELECT va.*, vs.name as server_name, vs.country 
-- FROM vpn_accounts va 
-- LEFT JOIN vpn_servers vs ON va.server_id = vs.id 
-- WHERE va.status = 'active';
