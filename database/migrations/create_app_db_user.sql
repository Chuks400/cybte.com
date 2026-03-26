-- Create least-privileged database user for TrustShield application
-- Run this in phpMyAdmin or MySQL CLI

-- ============================================
-- CREATE DATABASE USER
-- ============================================

CREATE USER IF NOT EXISTS 'trustshield_app'@'localhost' IDENTIFIED BY 'SecurePass123!';

-- Grant only necessary privileges on trustshield database
GRANT SELECT, INSERT, UPDATE, DELETE ON trustshield.* TO 'trustshield_app'@'localhost';

-- No GRANT OPTION (cannot grant privileges to others)
-- No DROP privilege (cannot delete tables/database)
-- No CREATE/DROP USER privileges

-- ============================================
-- VERIFY PRIVILEGES
-- ============================================

-- Show grants for the new user
SHOW GRANTS FOR 'trustshield_app'@'localhost';

-- ============================================
-- UPDATE APPLICATION CONFIG
-- ============================================

-- After running this SQL, update src/config/database.php:
-- private $username = "trustshield_app";
-- private $password = "SecurePass123!";

-- Or set environment variables:
-- DB_USER=trustshield_app
-- DB_PASS=SecurePass123!

-- ============================================
-- REVOKE ROOT ACCESS (optional, after testing)
-- ============================================

-- Once confirmed working, you can revoke root access from application:
-- REVOKE ALL PRIVILEGES ON trustshield.* FROM 'root'@'localhost';
