#!/bin/bash
#
# TrustShield AI - Automated Deployment Script for www.cybte.com
# 
# This script automates the deployment of TrustShield AI to a production server
# Run this script on your VPS/server after initial setup
#
# Usage: sudo bash deploy.sh

set -e  # Exit on error

# ============================================================================
# CONFIGURATION VARIABLES (Update these before running)
# ============================================================================

DOMAIN="cybte.com"
WWW_DOMAIN="www.cybte.com"
APP_DIR="/var/www/cybte.com"
DB_NAME="trustshield"
DB_USER="trustshield_user"
DB_PASS="CHANGE_THIS_PASSWORD"  # IMPORTANT: Change this!
GIT_REPO="https://github.com/Chuks400/cybte.com.git"
PHP_VERSION="8.1"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ============================================================================
# PRE-DEPLOYMENT CHECKS
# ============================================================================

check_root() {
    if [[ $EUID -ne 0 ]]; then
       log_error "This script must be run as root (use sudo)"
       exit 1
    fi
}

check_variables() {
    if [[ "$DB_PASS" == "CHANGE_THIS_PASSWORD" ]]; then
        log_error "Please change the DB_PASS variable in this script before running!"
        exit 1
    fi
}

# ============================================================================
# SYSTEM UPDATE AND PACKAGE INSTALLATION
# ============================================================================

update_system() {
    log_info "Updating system packages..."
    apt update && apt upgrade -y
    log_success "System updated"
}

install_apache() {
    log_info "Installing Apache..."
    apt install -y apache2 apache2-utils
    systemctl enable apache2
    systemctl start apache2
    log_success "Apache installed and started"
}

install_php() {
    log_info "Installing PHP $PHP_VERSION and extensions..."
    apt install -y php${PHP_VERSION} php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql php${PHP_VERSION}-gd php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-json php${PHP_VERSION}-sqlite3
    
    # Enable PHP-FPM
    a2enmod proxy_fcgi setenvif
    a2enconf php${PHP_VERSION}-fpm
    systemctl restart apache2
    log_success "PHP installed and configured"
}

install_mysql() {
    log_info "Installing MySQL..."
    apt install -y mysql-server mysql-client
    systemctl enable mysql
    systemctl start mysql
    
    # Run secure installation
    mysql_secure_installation
    log_success "MySQL installed and secured"
}

install_certbot() {
    log_info "Installing Certbot for SSL..."
    apt install -y certbot python3-certbot-apache
    log_success "Certbot installed"
}

install_utils() {
    log_info "Installing utility packages..."
    apt install -y git curl wget unzip logrotate
    log_success "Utilities installed"
}

# ============================================================================
# DATABASE SETUP
# ============================================================================

setup_database() {
    log_info "Setting up database..."
    
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    log_success "Database created"
}

import_database() {
    log_info "Importing database schema..."
    
    if [[ -f "${APP_DIR}/database/trustshield.sql" ]]; then
        mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < ${APP_DIR}/database/trustshield.sql
        log_success "Database schema imported"
    else
        log_warning "Database schema file not found, skipping import"
    fi
    
    # Run migrations
    for migration in ${APP_DIR}/database/migrations/*.sql; do
        if [[ -f "$migration" ]]; then
            log_info "Running migration: $(basename $migration)"
            mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < "$migration" || true
        fi
    done
}

# ============================================================================
# APPLICATION DEPLOYMENT
# ============================================================================

create_app_directory() {
    log_info "Creating application directory..."
    mkdir -p ${APP_DIR}
    chown -R www-data:www-data ${APP_DIR}
    log_success "Application directory created"
}

deploy_application() {
    log_info "Deploying application..."
    
    # Clone from GitHub (or copy from local)
    if [[ -d "${APP_DIR}/.git" ]]; then
        log_info "Updating existing repository..."
        cd ${APP_DIR}
        sudo -u www-data git pull origin main
    else
        log_info "Cloning repository..."
        rm -rf ${APP_DIR}/*
        sudo -u www-data git clone ${GIT_REPO} ${APP_DIR}
    fi
    
    log_success "Application deployed"
}

set_permissions() {
    log_info "Setting file permissions..."
    
    # Set ownership
    chown -R www-data:www-data ${APP_DIR}
    
    # Set file permissions
    find ${APP_DIR} -type f -exec chmod 644 {} \;
    find ${APP_DIR} -type d -exec chmod 755 {} \;
    
    # Make specific directories writable
    mkdir -p ${APP_DIR}/logs
    chmod -R 775 ${APP_DIR}/logs
    
    log_success "Permissions set"
}

create_env_file() {
    log_info "Creating environment configuration..."
    
    if [[ ! -f "${APP_DIR}/.env" ]]; then
        cp ${APP_DIR}/.env.example ${APP_DIR}/.env
        
        # Update environment variables
        sed -i "s/DB_NAME=trustshield/DB_NAME=${DB_NAME}/g" ${APP_DIR}/.env
        sed -i "s/DB_USER=trustshield_user/DB_USER=${DB_USER}/g" ${APP_DIR}/.env
        sed -i "s/DB_PASS=your_strong_database_password_here/DB_PASS=${DB_PASS}/g" ${APP_DIR}/.env
        sed -i "s|APP_URL=https://www.cybte.com|APP_URL=https://${WWW_DOMAIN}|g" ${APP_DIR}/.env
        
        log_success "Environment file created"
    else
        log_warning ".env file already exists, skipping creation"
    fi
}

# ============================================================================
# APACHE CONFIGURATION
# ============================================================================

configure_apache() {
    log_info "Configuring Apache..."
    
    # Copy virtual host configuration
    if [[ -f "${APP_DIR}/deployment/apache/cybte.com.conf" ]]; then
        cp ${APP_DIR}/deployment/apache/cybte.com.conf /etc/apache2/sites-available/cybte.com.conf
    else
        log_warning "Virtual host config not found, creating basic config..."
        create_basic_vhost
    fi
    
    # Enable required modules
    a2enmod rewrite ssl headers expires deflate
    
    # Enable site
    a2ensite cybte.com.conf
    
    # Disable default site
    a2dissite 000-default.conf 2>/dev/null || true
    
    # Test configuration
    apache2ctl configtest
    
    # Restart Apache
    systemctl restart apache2
    
    log_success "Apache configured"
}

create_basic_vhost() {
    cat > /etc/apache2/sites-available/cybte.com.conf <<EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias ${WWW_DOMAIN}
    DocumentRoot ${APP_DIR}/public
    
    <Directory ${APP_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/cybte-error.log
    CustomLog \${APACHE_LOG_DIR}/cybte-access.log combined
</VirtualHost>
EOF
}

# ============================================================================
# SSL CERTIFICATE SETUP
# ============================================================================

setup_ssl() {
    log_info "Setting up SSL certificate with Let's Encrypt..."
    
    # Check if domain is pointing to this server
    SERVER_IP=$(curl -s ifconfig.me)
    DOMAIN_IP=$(dig +short ${DOMAIN} | head -n 1)
    
    if [[ "$SERVER_IP" != "$DOMAIN_IP" ]]; then
        log_warning "Domain ${DOMAIN} does not point to this server!"
        log_warning "Server IP: ${SERVER_IP}"
        log_warning "Domain IP: ${DOMAIN_IP}"
        log_warning "Please update DNS records before continuing"
        read -p "Continue anyway? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    # Obtain certificate
    certbot --apache -d ${DOMAIN} -d ${WWW_DOMAIN} --non-interactive --agree-tos --email admin@${DOMAIN}
    
    log_success "SSL certificate installed"
}

# ============================================================================
# FIREWALL CONFIGURATION
# ============================================================================

configure_firewall() {
    log_info "Configuring firewall..."
    
    # Install and configure UFW
    apt install -y ufw
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow OpenSSH
    ufw allow 'Apache Full'
    
    # Enable firewall
    ufw --force enable
    
    log_success "Firewall configured"
}

# ============================================================================
# BACKUP SETUP
# ============================================================================

setup_backups() {
    log_info "Setting up automated backups..."
    
    # Create backup directory
    mkdir -p /var/backups/cybte
    
    # Create backup script
    cat > /usr/local/bin/backup-cybte.sh <<'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/cybte"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="trustshield"
DB_USER="trustshield_user"
APP_DIR="/var/www/cybte.com"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p'${DB_PASS}' $DB_NAME > $BACKUP_DIR/db_$DATE.sql 2>/dev/null || true

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $(dirname $APP_DIR) $(basename $APP_DIR) 2>/dev/null || true

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

# Log
logger "TrustShield backup completed: $DATE"
EOF
    
    chmod +x /usr/local/bin/backup-cybte.sh
    
    # Add to crontab (daily at 2 AM)
    (crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-cybte.sh") | crontab -
    
    log_success "Backup system configured"
}

# ============================================================================
# POST-DEPLOYMENT
# ============================================================================

verify_deployment() {
    log_info "Verifying deployment..."
    
    # Check Apache is running
    if systemctl is-active --quiet apache2; then
        log_success "Apache is running"
    else
        log_error "Apache is not running!"
        systemctl status apache2
    fi
    
    # Check MySQL is running
    if systemctl is-active --quiet mysql; then
        log_success "MySQL is running"
    else
        log_error "MySQL is not running!"
    fi
    
    # Check website is accessible
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost || echo "000")
    if [[ "$HTTP_STATUS" == "200" ]] || [[ "$HTTP_STATUS" == "301" ]] || [[ "$HTTP_STATUS" == "302" ]]; then
        log_success "Website is responding (HTTP ${HTTP_STATUS})"
    else
        log_warning "Website returned HTTP ${HTTP_STATUS}"
    fi
    
    log_info "Deployment verification complete"
}

print_summary() {
    echo
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo
    echo -e "${BLUE}Website:${NC} https://${WWW_DOMAIN}"
    echo -e "${BLUE}Document Root:${NC} ${APP_DIR}"
    echo -e "${BLUE}Database:${NC} ${DB_NAME}"
    echo -e "${BLUE}Logs:${NC} /var/log/apache2/"
    echo -e "${BLUE}Backups:${NC} /var/backups/cybte/"
    echo
    echo -e "${YELLOW}Next Steps:${NC}"
    echo "1. Update your .env file with production values: nano ${APP_DIR}/.env"
    echo "2. Test all application features"
    echo "3. Set up monitoring (optional)"
    echo "4. Configure CDN (optional)"
    echo
    echo -e "${YELLOW}SSL Certificate:${NC}"
    echo "Certificate will auto-renew. To test: sudo certbot renew --dry-run"
    echo
    echo -e "${YELLOW}Useful Commands:${NC}"
    echo "- Restart Apache: sudo systemctl restart apache2"
    echo "- View logs: sudo tail -f /var/log/apache2/cybte-error.log"
    echo "- Update code: cd ${APP_DIR} && sudo -u www-data git pull"
    echo "- Database backup: sudo /usr/local/bin/backup-cybte.sh"
    echo
}

# ============================================================================
# MAIN DEPLOYMENT FLOW
# ============================================================================

main() {
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  TrustShield AI Deployment Script${NC}"
    echo -e "${GREEN}  Domain: ${WWW_DOMAIN}${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo
    
    # Pre-deployment checks
    check_root
    check_variables
    
    # Confirm deployment
    read -p "Continue with deployment? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
    
    # Execute deployment steps
    update_system
    install_apache
    install_php
    install_mysql
    install_certbot
    install_utils
    
    create_app_directory
    deploy_application
    setup_database
    import_database
    
    set_permissions
    create_env_file
    configure_apache
    configure_firewall
    
    # SSL setup (may fail if DNS not ready)
    setup_ssl || log_warning "SSL setup failed. Run manually later: sudo certbot --apache -d ${DOMAIN} -d ${WWW_DOMAIN}"
    
    setup_backups
    verify_deployment
    
    print_summary
}

# Run main function
main "$@"
