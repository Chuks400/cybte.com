# TrustShield AI - Professional Deployment Guide for www.cybte.com

## Overview

This guide provides step-by-step instructions for deploying TrustShield AI to your custom domain `www.cybte.com` with professional-grade security, SSL/HTTPS, and performance optimization.

## Prerequisites

- Domain name registered: `cybte.com`
- VPS or dedicated server with root access
- Ubuntu 20.04/22.04 LTS or CentOS 8
- Basic knowledge of Linux command line

## Server Requirements

- **OS**: Ubuntu 22.04 LTS (recommended) or CentOS 8
- **Web Server**: Apache 2.4+ with mod_rewrite
- **PHP**: 8.1 or higher with extensions:
  - PDO
  - PDO_MySQL
  - GD
  - cURL
  - OpenSSL
  - mbstring
  - xml
  - json
  - zip
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **SSL**: Let's Encrypt (free) or commercial certificate
- **Memory**: Minimum 2GB RAM (4GB recommended)
- **Storage**: 20GB SSD minimum

## Deployment Options

### Option 1: VPS/Cloud Server (Recommended)
**Providers**: DigitalOcean, AWS EC2, Linode, Vultr, or Hetzner

**Cost**: $5-20/month depending on traffic

**Pros**:
- Full control over server
- Can handle high traffic
- Easy to scale
- Professional-grade security

### Option 2: Shared Hosting (Budget Option)
**Providers**: SiteGround, Hostinger, Bluehost

**Cost**: $3-10/month

**Pros**:
- No server management required
- Cheap and easy to set up
- Good for low traffic

**Cons**:
- Limited resources
- Less control
- May not support all PHP extensions

### Option 3: PaaS (Platform as a Service)
**Providers**: Heroku, Railway, Platform.sh

**Pros**:
- Automatic deployments
- Easy scaling
- Built-in SSL

**Cons**:
- More expensive at scale
- Less control over environment

---

## Step 1: Server Setup

### 1.1 Update Server
```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

### 1.2 Install Apache
```bash
# Ubuntu/Debian
sudo apt install -y apache2 apache2-utils

# CentOS/RHEL
sudo yum install -y httpd httpd-tools
sudo systemctl enable httpd
sudo systemctl start httpd
```

### 1.3 Install PHP 8.1
```bash
# Ubuntu/Debian
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-gd php8.1-curl php8.1-zip php8.1-mbstring php8.1-xml php8.1-json

# Enable PHP-FPM for Apache
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.1-fpm
sudo systemctl restart apache2
```

### 1.4 Install MySQL
```bash
# Ubuntu/Debian
sudo apt install -y mysql-server mysql-client
sudo mysql_secure_installation

# CentOS/RHEL
sudo yum install -y mysql-server
sudo systemctl enable mysqld
sudo systemctl start mysqld
sudo mysql_secure_installation
```

---

## Step 2: Domain DNS Configuration

### 2.1 Point Domain to Server

1. **Get your server IP address**:
   ```bash
   curl ifconfig.me
   ```

2. **Configure DNS A Records** at your domain registrar:
   
   | Type | Name | Value | TTL |
   |------|------|-------|-----|
   | A | @ | YOUR_SERVER_IP | 3600 |
   | A | www | YOUR_SERVER_IP | 3600 |
   
3. **Wait for DNS propagation** (5 minutes to 48 hours, usually within 1 hour)

4. **Verify DNS**:
   ```bash
   nslookup cybte.com
   nslookup www.cybte.com
   ```

---

## Step 3: SSL/HTTPS Configuration (Let's Encrypt)

### 3.1 Install Certbot
```bash
# Ubuntu/Debian
sudo apt install -y certbot python3-certbot-apache

# CentOS/RHEL
sudo yum install -y certbot python3-certbot-apache
```

### 3.2 Obtain SSL Certificate
```bash
sudo certbot --apache -d cybte.com -d www.cybte.com
```

### 3.3 Auto-renewal Setup
```bash
# Test auto-renewal
sudo certbot renew --dry-run

# The systemd timer or cron job is usually set up automatically
```

---

## Step 4: Application Deployment

### 4.1 Create Application Directory
```bash
sudo mkdir -p /var/www/cybte.com
sudo chown -R $USER:$USER /var/www/cybte.com
```

### 4.2 Deploy Application Code
```bash
# Clone from GitHub
cd /var/www/cybte.com
git clone https://github.com/Chuks400/cybte.com.git .

# Or upload via SCP/SFTP from local
scp -r /local/path/to/trustshield-ai/* user@server:/var/www/cybte.com/
```

### 4.3 Set Proper Permissions
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/cybte.com

# Set file permissions
sudo find /var/www/cybte.com -type f -exec chmod 644 {} \;
sudo find /var/www/cybte.com -type d -exec chmod 755 {} \;

# Make upload directories writable
sudo chmod -R 775 /var/www/cybte.com/public/uploads
sudo chmod -R 775 /var/www/cybte.com/logs
```

### 4.4 Create Environment Configuration
```bash
cd /var/www/cybte.com
cp .env.example .env
sudo nano .env
```

Edit the `.env` file with your production values (see `.env.example` for template).

---

## Step 5: Database Setup

### 5.1 Create Database and User
```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE trustshield CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'trustshield_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON trustshield.* TO 'trustshield_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5.2 Import Database Schema
```bash
cd /var/www/cybte.com
mysql -u trustshield_user -p trustshield < database/trustshield.sql
```

### 5.3 Run Migrations
```bash
# Run any additional migrations
mysql -u trustshield_user -p trustshield < database/migrations/create_payments_table.sql
mysql -u trustshield_user -p trustshield < database/migrations/add_https_support.sql
mysql -u trustshield_user -p trustshield < database/migrations/add_3xui_support.sql
```

---

## Step 6: Apache Virtual Host Configuration

### 6.1 Create Virtual Host File
```bash
sudo nano /etc/apache2/sites-available/cybte.com.conf
```

Paste the configuration from `deployment/apache/cybte.com.conf`

### 6.2 Enable Site and Modules
```bash
# Enable required Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod expires
sudo a2enmod deflate

# Enable the site
sudo a2ensite cybte.com.conf

# Disable default site (optional)
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

---

## Step 7: Security Hardening

### 7.1 Configure Firewall
```bash
# UFW (Ubuntu)
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable

# FirewallD (CentOS)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 7.2 Secure PHP Configuration
Edit `/etc/php/8.1/fpm/php.ini`:
```ini
; Security settings
expose_php = Off
display_errors = Off
log_errors = On
allow_url_fopen = Off
allow_url_include = Off

; Performance settings
memory_limit = 256M
max_execution_time = 30
max_input_time = 60
post_max_size = 8M
upload_max_filesize = 8M
```

### 7.3 Secure Apache Headers
Already included in virtual host configuration via `.htaccess` and Apache config.

### 7.4 Hide Server Information
```bash
# Edit Apache config
sudo nano /etc/apache2/conf-available/security.conf

# Set:
ServerTokens Prod
ServerSignature Off
```

---

## Step 8: Performance Optimization

### 8.1 Enable Gzip Compression
Already enabled in virtual host configuration.

### 8.2 Browser Caching
Already configured via `.htaccess` and Apache expires module.

### 8.3 OPcache (PHP)
Edit `/etc/php/8.1/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

---

## Step 9: Monitoring and Maintenance

### 9.1 Install Log Rotation
```bash
sudo apt install -y logrotate
```

Create `/etc/logrotate.d/cybte`:
```
/var/www/cybte.com/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
}
```

### 9.2 Set Up Automated Backups
```bash
# Create backup script
sudo nano /usr/local/bin/backup-cybte.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/cybte"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u trustshield_user -p'YOUR_PASSWORD' trustshield > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C /var/www cybte.com

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-cybte.sh

# Add to crontab (daily at 2 AM)
echo "0 2 * * * /usr/local/bin/backup-cybte.sh" | sudo crontab -
```

---

## Step 10: Post-Deployment Verification

### 10.1 Test Website Access
- Visit `https://www.cybte.com` (should redirect to HTTPS)
- Visit `https://cybte.com` (should work)
- Check SSL certificate: `https://www.ssllabs.com/ssltest/analyze.html?d=cybte.com`

### 10.2 Test Application Features
- [ ] User registration/login
- [ ] VPN service pages
- [ ] Payment integration (test mode)
- [ ] Admin dashboard
- [ ] API endpoints

### 10.3 Performance Testing
```bash
# Install Apache Bench
sudo apt install -y apache2-utils

# Test performance
ab -n 1000 -c 10 https://www.cybte.com/
```

---

## Troubleshooting

### Issue: 500 Internal Server Error
**Solution**:
```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Check PHP error logs
sudo tail -f /var/log/php8.1-fpm.log

# Verify file permissions
sudo chown -R www-data:www-data /var/www/cybte.com
```

### Issue: Database Connection Failed
**Solution**:
1. Verify `.env` database credentials
2. Check MySQL is running: `sudo systemctl status mysql`
3. Test connection: `mysql -u trustshield_user -p -h localhost trustshield`

### Issue: SSL Certificate Not Working
**Solution**:
```bash
# Check certificate status
sudo certbot certificates

# Renew manually if needed
sudo certbot renew --force-renewal

# Restart Apache
sudo systemctl restart apache2
```

---

## Maintenance Tasks

### Weekly
- Check server logs for errors
- Monitor disk space: `df -h`
- Review Apache access logs for suspicious activity

### Monthly
- Update server packages: `sudo apt update && sudo apt upgrade`
- Review and rotate logs
- Check SSL certificate expiry
- Backup verification

### Quarterly
- Security audit
- Performance review
- Update PHP if new version available
- Database optimization: `mysqlcheck -o trustshield`

---

## Support and Resources

- **GitHub Repository**: https://github.com/Chuks400/cybte.com
- **Documentation**: See `docs/` directory
- **VPN Setup**: See `docs/VPN_SETUP_3XUI.md`

## Contact

For professional deployment assistance, contact your system administrator or hosting provider.

---

**Last Updated**: March 2026  
**Version**: 1.0  
**Domain**: www.cybte.com
