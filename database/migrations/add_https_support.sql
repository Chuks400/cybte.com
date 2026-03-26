-- Add HTTPS support columns to vpn_servers table
-- Run this SQL to update your database for HTTPS domain support

-- Add domain column
ALTER TABLE vpn_servers 
ADD COLUMN IF NOT EXISTS domain VARCHAR(255) NULL COMMENT 'Domain name for HTTPS access (e.g., cybte.com)';

-- Add use_https column
ALTER TABLE vpn_servers 
ADD COLUMN IF NOT EXISTS use_https TINYINT(1) DEFAULT 0 COMMENT 'Enable HTTPS for panel and subscription links';

-- Add panel_port column
ALTER TABLE vpn_servers 
ADD COLUMN IF NOT EXISTS panel_port VARCHAR(10) DEFAULT '54321' COMMENT '3x-ui panel port';

-- Add web_base_path column
ALTER TABLE vpn_servers 
ADD COLUMN IF NOT EXISTS web_base_path VARCHAR(255) DEFAULT '/JE2fu7rGygZsRGQwEW/' COMMENT 'Web base path for panel access';

-- Update existing server to use HTTPS with your domain
UPDATE vpn_servers 
SET domain = 'cybte.com', 
    use_https = 1,
    panel_port = '54321',
    web_base_path = '/JE2fu7rGygZsRGQwEW/'
WHERE id = 1;

-- Verify the update
SELECT id, name, ip_address, domain, use_https, panel_port, web_base_path, status 
FROM vpn_servers;
