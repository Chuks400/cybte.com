# SSL/HTTPS Setup Guide for www.cybte.com

## Overview

This guide covers setting up SSL/TLS certificates for your TrustShield AI application to enable secure HTTPS connections.

## Why SSL/HTTPS is Essential

1. **Security**: Encrypts data between browser and server
2. **SEO**: Google ranks HTTPS sites higher
3. **Trust**: Users trust websites with the padlock icon
4. **Compliance**: Required for payment processing
5. **VPN Integration**: Your VPN service requires HTTPS for subscription links

## Option 1: Let's Encrypt (Free - Recommended)

### Prerequisites
- Domain pointing to your server (DNS A records configured)
- Apache web server running
- Port 80 and 443 open in firewall

### Step 1: Install Certbot

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install -y certbot python3-certbot-apache
```

**CentOS/RHEL:**
```bash
sudo yum install -y certbot python3-certbot-apache
```

### Step 2: Obtain Certificate

**Automatic Apache Configuration:**
```bash
sudo certbot --apache -d cybte.com -d www.cybte.com
```

Follow the prompts:
1. Enter your email address
2. Accept the terms of service
3. Choose whether to share your email with EFF
4. Select whether to redirect HTTP to HTTPS (choose Yes - recommended)

### Step 3: Verify Installation

```bash
# Check certificate status
sudo certbot certificates

# Test automatic renewal
sudo certbot renew --dry-run
```

### Step 4: Auto-Renewal

Certbot automatically sets up a cron job or systemd timer. Verify it's working:

```bash
# Check systemd timer
sudo systemctl list-timers | grep certbot

# Or check cron
sudo cat /etc/cron.d/certbot
```

Certificates auto-renew 30 days before expiration.

---

## Option 2: Commercial SSL Certificate

For wildcard certificates or Extended Validation (EV), consider:
- **DigiCert**
- **Sectigo (formerly Comodo)**
- **GeoTrust**
- **SSL.com**

### Installation Steps:

1. **Generate CSR (Certificate Signing Request):**
```bash
openssl req -new -newkey rsa:2048 -nodes -keyout cybte.com.key -out cybte.com.csr
```

2. **Submit CSR to Certificate Authority**

3. **Download Certificate Files:**
   - Certificate (cybte.com.crt)
   - Intermediate CA (ca-bundle.crt)

4. **Install on Server:**
```bash
# Copy certificates
sudo cp cybte.com.crt /etc/ssl/certs/
sudo cp cybte.com.key /etc/ssl/private/
sudo cp ca-bundle.crt /etc/ssl/certs/

# Set permissions
sudo chmod 644 /etc/ssl/certs/cybte.com.crt
sudo chmod 600 /etc/ssl/private/cybte.com.key
```

---

## Apache SSL Configuration

### Virtual Host Configuration

```apache
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName cybte.com
    ServerAlias www.cybte.com
    
    DocumentRoot /var/www/cybte.com/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/cybte.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/cybte.com/privkey.pem
    
    # Modern SSL Configuration
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder on
    SSLCompression off
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/cybte-error.log
    CustomLog ${APACHE_LOG_DIR}/cybte-access.log combined
</VirtualHost>
</IfModule>

# HTTP to HTTPS Redirect
<VirtualHost *:80>
    ServerName cybte.com
    ServerAlias www.cybte.com
    
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>
```

---

## SSL Test and Verification

### 1. Browser Test
- Visit `https://www.cybte.com`
- Check for padlock icon
- Verify no mixed content warnings

### 2. SSL Labs Test
Test your SSL configuration:
```
https://www.ssllabs.com/ssltest/analyze.html?d=cybte.com
```

**Target Grade**: A+ or A

### 3. Command Line Test
```bash
# Check certificate expiry
echo | openssl s_client -servername cybte.com -connect cybte.com:443 2>/dev/null | openssl x509 -noout -dates

# Test SSL configuration
nmap --script ssl-enum-ciphers -p 443 cybte.com

# Check HTTPS redirect
curl -I http://cybte.com
```

---

## VPN Service SSL Configuration

Since your VPN service uses HTTPS for subscription links, ensure:

1. **Domain is properly configured** in database:
```sql
UPDATE vpn_servers 
SET domain = 'cybte.com', 
    use_https = 1 
WHERE id = 1;
```

2. **Subscription links use HTTPS:**
The VPN service automatically generates HTTPS links when `use_https` is enabled.

3. **Panel Access:**
Configure your 3x-ui panel to use the same domain for consistency.

---

## Troubleshooting

### Certificate Not Valid Error
**Cause**: Wrong domain or certificate not installed
**Solution**:
```bash
# Check certificate domain
sudo certbot certificates

# Reinstall if needed
sudo certbot install --cert-name cybte.com
```

### Mixed Content Warnings
**Cause**: HTTP resources on HTTPS page
**Solution**:
- Update all URLs to use `https://`
- Use protocol-relative URLs: `//domain.com/resource`

### Certificate Expired
**Cause**: Auto-renewal failed
**Solution**:
```bash
# Force renewal
sudo certbot renew --force-renewal

# Check renewal timer
sudo systemctl status certbot.timer

# Test renewal process
sudo certbot renew --dry-run
```

### Apache Won't Start
**Cause**: SSL module not enabled or config error
**Solution**:
```bash
# Enable SSL module
sudo a2enmod ssl

# Test config
sudo apache2ctl configtest

# Check error logs
sudo tail -f /var/log/apache2/error.log
```

---

## Renewal and Maintenance

### Manual Renewal
```bash
# Renew all certificates
sudo certbot renew

# Restart Apache after renewal
sudo systemctl restart apache2
```

### Pre and Post Hooks
Create `/etc/letsencrypt/renewal-hooks/deploy/restart-apache.sh`:
```bash
#!/bin/bash
systemctl restart apache2
```

```bash
sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/restart-apache.sh
```

### Monitoring
Set up certificate expiry monitoring:
```bash
# Add to cron for daily check
echo "0 6 * * * /usr/bin/certbot renew --quiet --deploy-hook 'systemctl restart apache2'" | sudo crontab -
```

---

## Best Practices

1. **Always redirect HTTP to HTTPS**
2. **Use HSTS header** (included in config above)
3. **Keep certificates updated**
4. **Monitor expiration dates**
5. **Use strong cipher suites**
6. **Disable old protocols** (SSLv2, SSLv3, TLS 1.0, TLS 1.1)
7. **Enable OCSP stapling** for better performance

---

## Resources

- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [SSL Labs Best Practices](https://github.com/ssllabs/research/wiki/SSL-and-TLS-Deployment-Best-Practices)
- [Mozilla SSL Configuration Generator](https://ssl-config.mozilla.org/)
