# Cybte AI — Production Deployment Guide

This repository is a PHP/MySQL application. Use a PHP-capable Apache or Nginx host for the production application. Vercel should not be treated as the runtime for the existing PHP backend.

## Runtime requirements

- PHP 8.2+ recommended
- MySQL 8.0+ or MariaDB 10.6+
- PHP extensions: `pdo_mysql`, `openssl`, `fileinfo`, `mbstring`, `curl`
- HTTPS/TLS
- A web server document root pointing to `public/`
- A writable Secure Vault directory **outside** the public document root

## 1. Rotate historical credentials first

This repository previously contained environment material, a database credential and VPN session material in Git history. Removing those files from the current branch does not erase historical copies.

Before production deployment, rotate any previously used:

- database passwords
- SMTP / Resend credentials
- payment provider credentials
- API / JWT secrets
- VPN panel credentials and sessions/cookies
- encryption/application keys

Do not reuse the old values.

## 2. Configure the server environment

Copy the example only on the server:

```bash
cp .env.example .env
chmod 600 .env
```

At minimum configure:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.cybte.com
APP_KEY=base64:YOUR_NEW_32_BYTE_BASE64_KEY

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=cybte
DB_USER=cybte_app
DB_PASS=YOUR_NEW_DATABASE_PASSWORD

VAULT_STORAGE_PATH=/var/lib/cybte/vault
```

Generate a fresh Vault/application key:

```bash
openssl rand -base64 32
```

Create the Vault directory outside `public/` and restrict permissions to the PHP/web-server account:

```bash
sudo install -d -m 700 -o www-data -g www-data /var/lib/cybte/vault
```

Back up the key separately from the application server. Losing the key means encrypted Vault documents cannot be recovered.

## 3. Database

For an existing installation, back up the database first, then run the existing migrations as required by that installation and apply the enterprise refresh migration:

```bash
mysql -u cybte_app -p cybte < database/migrations/2026_09_enterprise_refresh.sql
```

For payment functionality, ensure the payment schema/plans migration has also been applied:

```bash
mysql -u cybte_app -p cybte < database/migrations/create_payments_table.sql
```

The application now requires database credentials from the environment; there is no source-code password fallback.

## 4. Web server

Point the virtual-host document root to:

```text
/path/to/cybte.com/public
```

Do not expose the repository root as the public document root. This keeps `src/`, `database/`, `.env`, migrations and Vault storage away from direct HTTP access.

Enable HTTPS before production traffic. Disable PHP error display and keep error logging server-side.

Recommended PHP limits for the current Secure Vault MVP:

```ini
expose_php = Off
display_errors = Off
log_errors = On
upload_max_filesize = 12M
post_max_size = 12M
memory_limit = 256M
```

The Vault application limit is 10 MB per file; the PHP limits must be slightly higher so the request can reach application validation.

## 5. Email verification

Configure either SMTP or Resend using `.env.example`. Test account registration and verify that generated links use the real `APP_URL` and arrive from the intended Cybte AI sender identity.

## 6. Payments

Before accepting live payments:

- apply the payment schema
- configure real provider credentials
- verify provider status polling with a small test transaction
- confirm the server, not the browser, determines the plan price

The application deliberately disables provider webhooks until official cryptographic webhook-signature verification is implemented and tested. Automated refunds are also deliberately disabled until a real provider refund API is integrated. Do not work around those safeguards by changing only database payment status.

## 7. Secure Vault

The current Vault MVP implements:

- authenticated account ownership checks
- AES-256-GCM file encryption
- server-side MIME validation
- SHA-256 integrity verification after decryption
- audit records for upload/download/delete
- non-public encrypted storage by default

Before storing regulated, mission-critical or irreplaceable customer records, add and test:

- centralized/managed key storage and rotation
- encrypted backups and disaster recovery
- malware scanning before storage/retrieval
- MFA / step-up authentication
- storage quotas and organization-level policy controls
- monitoring and alerting
- independent penetration testing

## 8. Pre-deployment checks

GitHub Actions on this repository checks changed PHP syntax, merge markers, key public pages and security headers. The production deployment should additionally test against the actual database and provider integrations.

Minimum production acceptance checklist:

- [ ] Homepage loads over HTTPS
- [ ] Mobile/tablet/desktop layouts reviewed in real browsers
- [ ] Create account → verification email → verification → login works
- [ ] Dashboard loads account-backed data
- [ ] Contact enquiry inserts successfully
- [ ] Vault upload → encrypted storage → download → delete works
- [ ] A user cannot retrieve another user’s Vault document
- [ ] Payment amount cannot be changed from browser developer tools
- [ ] Real provider confirms a small payment before VPN activation
- [ ] Admin-only pages reject normal users
- [ ] Debug/setup/test endpoints return 404 because they are not deployed
- [ ] Error pages do not expose stack traces, credentials or tokens
- [ ] Backups and restore procedure are tested

## 9. Deployment sequence

A safe sequence for the current refresh is:

```text
backup production
→ rotate secrets
→ merge approved branch
→ deploy code
→ configure new .env
→ apply migrations
→ configure Vault directory
→ restart PHP/web server
→ run acceptance checklist
→ enable production traffic
```

## Important distinction

Merging `cybte-enterprise-refresh` into `main` is a source-control action. It does not automatically make new database tables, environment variables, Vault permissions or payment credentials available on the live server. Complete the deployment steps above before directing production users to the upgraded application.
