# TrustShield VPN - 3x-ui Backend Integration Guide

## Overview
This guide explains how to configure the 3x-ui VPN panel integration for TrustShield VPN.

## Architecture

```
User Signup → TrustShield PHP App → 3x-ui API → Xray/VLESS Account
                ↓                        ↓
           MySQL Database          VPN Server
```

## Step 1: Database Migration

Run this SQL in phpMyAdmin to update your database schema:

```sql
-- File: database/migrations/add_3xui_support.sql

-- Add API columns to vpn_servers
ALTER TABLE vpn_servers 
ADD COLUMN country VARCHAR(2) NULL,
ADD COLUMN flag VARCHAR(10) NULL,
ADD COLUMN panel_type VARCHAR(20) DEFAULT '3x-ui',
ADD COLUMN api_url VARCHAR(255) NULL,
ADD COLUMN api_token VARCHAR(255) NULL,
ADD COLUMN api_username VARCHAR(100) NULL,
ADD COLUMN api_password VARCHAR(255) NULL,
ADD COLUMN inbound_id INT DEFAULT 1,
ADD COLUMN protocol VARCHAR(20) DEFAULT 'vless';

-- Add tracking columns to vpn_accounts
ALTER TABLE vpn_accounts 
ADD COLUMN uuid VARCHAR(36) NULL,
ADD COLUMN client_email VARCHAR(255) NULL,
ADD COLUMN traffic_used_gb DECIMAL(10,2) DEFAULT 0,
ADD COLUMN traffic_limit_gb INT DEFAULT 10,
ADD COLUMN expires_at DATETIME NULL,
ADD COLUMN last_connected_at DATETIME NULL;
```

## Step 2: Configure 3x-ui Panel

### Install 3x-ui (if not already installed)
```bash
# On your VPN server
bash <(curl -Ls https://raw.githubusercontent.com/mhsanaei/3x-ui/master/install.sh)
```

### Enable API Access
1. Login to 3x-ui panel: `http://your-server:54321`
2. Go to **Panel Settings**
3. Enable **API Access**
4. Generate/copy the **API Token**
5. Note the **Inbound ID** you want to use (usually 1)

### Configure Inbound
1. Create an inbound with protocol **VLESS** or **VMess**
2. Enable **Subscription** in inbound settings
3. Note the port and protocol settings

## Step 3: Add Server to Database

Insert your server into the `vpn_servers` table:

```sql
INSERT INTO vpn_servers 
(name, location, country, flag, ip_address, status, load_percent, 
 panel_type, api_url, api_token, inbound_id, protocol)
VALUES 
('USA-NYC-1', 'New York', 'US', '🇺🇸', '192.168.1.100', 'active', 45,
 '3x-ui', 'http://192.168.1.100:54321', 'your-api-token-here', 1, 'vless');
```

**Replace:**
- `192.168.1.100` with your server's IP
- `your-api-token-here` with the token from 3x-ui panel
- `inbound_id` with your inbound number

## Step 4: Test Connection

Run the test script:

```
http://localhost/trustshield-ai/src/VPN/test_panel.php
```

Expected output:
```
========================================
TRUSTSHIELD VPN - 3x-ui Panel Test
========================================

✓ Database connection: SUCCESS

Found 1 server(s):
--------------------------------------------------

Testing: USA-NYC-1 (New York)
  URL: http://192.168.1.100:54321
  Testing connection... ✓ SUCCESS
  Inbound: vless://443
  Protocol: vless

==================================================
Test complete.
```

## Step 5: User Flow Test

1. **Signup**: Go to `public/vpn_signup.php`
2. **Create Account**: Fill in email/password
3. **Automatic VPN Creation**: System will:
   - Create user in database
   - Call 3x-ui API to create client
   - Generate real VLESS/VMess subscription link
   - Store link in database
4. **Dashboard**: Shows:
   - Real subscription link (vless://...)
   - Connected server info
   - Traffic usage (from panel)
   - Expiry date

## Subscription Link Format

Working links look like:
```
vless://uuid@server-ip:port?security=tls&sni=domain#email
```

Example:
```
vless://a1b2c3d4-e5f6-7890-abcd-ef1234567890@192.168.1.100:443?security=tls&sni=vpn.example.com#user@example.com-TRUSTSHIELD
```

## Client Apps That Support These Links

- **Windows**: Nekoray, v2rayN, Nekoray
- **macOS**: V2RayXS, Qv2ray
- **iOS**: Shadowrocket, OneClick, V2Ray
- **Android**: v2rayNG, NekoBox
- **Linux**: Qv2ray, v2rayA

## Troubleshooting

### Connection Test Fails
- Check firewall (port 54321 must be open)
- Verify API URL includes port
- Confirm API token is valid
- Check if panel is running: `systemctl status x-ui`

### User Created But No VPN Account
- Check PHP error logs: `/var/log/apache2/error.log`
- Verify server is active in database
- Run test script to confirm API works

### Subscription Link Doesn't Work
- Verify inbound settings in 3x-ui panel
- Check TLS certificate if using TLS
- Ensure port is open on VPN server
- Test with different client app

## File Structure

```
src/VPN/
├── ThreeXUIAPI.php      # API client for 3x-ui panel
├── VPNService.php        # Business logic for VPN accounts
└── test_panel.php       # Connection test script
```

## Security Notes

1. **API Token**: Keep secret, never commit to git
2. **Server IP**: Use firewall to restrict access
3. **Database**: Store API tokens encrypted (recommended)
4. **HTTPS**: Use HTTPS for panel if exposed to internet

## Multiple Servers (Future)

After first server works, add more:

```sql
INSERT INTO vpn_servers 
(name, location, country, flag, panel_type, api_url, api_token, inbound_id, protocol, status)
VALUES 
('Germany-FRA-1', 'Frankfurt', 'DE', '🇩🇪', '3x-ui', 'http://ip:54321', 'token', 1, 'vless', 'active'),
('Singapore-SIN-1', 'Singapore', 'SG', '🇸🇬', '3x-ui', 'http://ip:54321', 'token', 1, 'vless', 'active');
```

The system will automatically load-balance or let users choose.

## Next Steps After Setup

1. ✅ Test signup creates real VPN account
2. ✅ Copy subscription link to client app
3. ✅ Verify internet traffic routes through VPN
4. ⬜ Add payment integration (Stripe/PayPal)
5. ⬜ Add admin panel to manage users
6. ⬜ Add traffic monitoring from panel
7. ⬜ Add auto-renewal system

## Support

If issues persist:
1. Check 3x-ui panel logs
2. Run test script and share output
3. Verify MySQL tables have new columns
4. Check PHP has cURL enabled
