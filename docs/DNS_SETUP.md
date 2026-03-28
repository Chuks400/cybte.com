# DNS Configuration Guide for cybte.com

## Overview

This guide explains how to configure DNS records to point your domain `cybte.com` to your server, enabling access via `www.cybte.com`.

## Prerequisites

- Registered domain: `cybte.com`
- Server with public IP address
- Access to domain registrar's DNS management panel

## DNS Record Types Explained

### A Record (Address Record)
Maps a domain name to an IPv4 address.

### CNAME Record (Canonical Name)
Creates an alias from one domain to another.

### AAAA Record
Maps a domain name to an IPv6 address (optional).

### MX Record (Mail Exchange)
For email handling (not covered here).

### TXT Record
For verification and SPF/DKIM (not covered here).

---

## Step-by-Step DNS Configuration

### Step 1: Get Your Server IP Address

```bash
# On your server, run:
curl ifconfig.me
curl icanhazip.com
```

**Example Output**: `203.0.113.45`

Note this IP address - you'll need it for DNS configuration.

### Step 2: Access DNS Management

Log in to your domain registrar's control panel:

| Registrar | DNS Management Location |
|-----------|------------------------|
| GoDaddy | My Products → DNS |
| Namecheap | Domain List → Manage → Advanced DNS |
| Cloudflare | Overview → DNS |
| Google Domains | DNS → Custom resource records |
| Alibaba Cloud | Domain → DNS Settings |
| Tencent Cloud | Domain Name → DNS |

### Step 3: Configure DNS Records

Add the following DNS records:

#### Required Records

| Type | Name/Host | Value/Points to | TTL |
|------|-----------|-----------------|-----|
| A | @ | YOUR_SERVER_IP | 3600 |
| A | www | YOUR_SERVER_IP | 3600 |

**Example Configuration**:

```
Type: A
Name: @
Value: 203.0.113.45
TTL: 3600 (1 hour)
```

```
Type: A
Name: www
Value: 203.0.113.45
TTL: 3600 (1 hour)
```

#### Optional Records

| Type | Name | Value | Purpose |
|------|------|-------|---------|
| A | api | YOUR_SERVER_IP | API subdomain |
| A | admin | YOUR_SERVER_IP | Admin panel |
| AAAA | @ | YOUR_IPV6 | IPv6 support |

---

## DNS Configuration Examples by Registrar

### GoDaddy

1. Log in to GoDaddy
2. Go to "My Products" → "DNS"
3. Under "Records" section, click "Add"
4. Add A record:
   - Type: A
   - Name: @
   - Value: YOUR_SERVER_IP
   - TTL: 1 Hour
5. Add another A record:
   - Type: A
   - Name: www
   - Value: YOUR_SERVER_IP
   - TTL: 1 Hour
6. Click "Save"

### Namecheap

1. Log in to Namecheap
2. Go to "Domain List" → Click "Manage"
3. Click "Advanced DNS" tab
4. Under "Host Records", click "Add New Record"
5. Add records:
   - Type: A Record
   - Host: @
   - Value: YOUR_SERVER_IP
   - TTL: Automatic
6. Add second record:
   - Type: A Record
   - Host: www
   - Value: YOUR_SERVER_IP
   - TTL: Automatic
7. Click "Save All Changes"

### Cloudflare (Recommended for CDN)

1. Sign up at Cloudflare.com
2. Add your domain
3. Cloudflare will scan existing DNS records
4. Verify A records exist:
   - Name: cybte.com → YOUR_SERVER_IP
   - Name: www → YOUR_SERVER_IP
5. Continue to activate
6. Change nameservers at your registrar to Cloudflare's

### Alibaba Cloud

1. Log in to Alibaba Cloud Console
2. Go to "Domain Name Service"
3. Find your domain and click "DNS Settings"
4. Click "Add Record"
5. Add:
   - Type: A
   - Host: @
   - ISP Line: Default
   - Value: YOUR_SERVER_IP
   - TTL: 10 minutes
6. Add second record with Host: www
7. Click "OK"

---

## Step 4: Verify DNS Configuration

### Wait for Propagation

DNS changes take time to propagate globally:
- **Minimum**: 5 minutes
- **Typical**: 1-4 hours
- **Maximum**: 48 hours (rare)

### Check DNS Propagation

**Method 1: Command Line**
```bash
# Check A record
nslookup cybte.com

# Check www subdomain
nslookup www.cybte.com

# Using dig
dig cybte.com A
dig www.cybte.com A

# Check global propagation
nslookup cybte.com 8.8.8.8  # Google DNS
nslookup cybte.com 1.1.1.1  # Cloudflare DNS
```

**Method 2: Online Tools**
- [whatsmydns.net](https://www.whatsmydns.net/) - Check propagation worldwide
- [dnschecker.org](https://dnschecker.org/) - Multi-location DNS check
- [digwebinterface.com](https://www.digwebinterface.com/) - Web-based dig tool

**Expected Output**:
```
Name:    cybte.com
Address: 203.0.113.45
```

---

## Step 5: Test Domain Access

Once DNS has propagated, test your domain:

```bash
# Ping test
ping cybte.com
ping www.cybte.com

# HTTP request (before SSL setup)
curl -I http://cybte.com

# Check final redirect (after SSL setup)
curl -I https://www.cybte.com
```

---

## Common Issues and Solutions

### Issue: DNS Not Propagating
**Symptoms**: Domain still shows old IP or doesn't resolve

**Solutions**:
1. Clear local DNS cache:
   ```bash
   # Windows
   ipconfig /flushdns
   
   # macOS
   sudo dscacheutil -flushcache
   
   # Linux
   sudo systemd-resolve --flush-caches
   ```

2. Check using different DNS servers:
   ```bash
   nslookup cybte.com 8.8.8.8
   nslookup cybte.com 1.1.1.1
   ```

3. Wait longer (up to 48 hours)

### Issue: Domain Points to Wrong IP
**Symptoms**: nslookup shows different IP than your server

**Solutions**:
1. Verify DNS records at registrar
2. Check for typos in IP address
3. Ensure no old A records exist

### Issue: www Works but Root Domain Doesn't
**Symptoms**: www.cybte.com works, cybte.com doesn't

**Solutions**:
1. Ensure you have A record for `@` (root)
2. Some registrars use different notation:
   - `@` = root domain
   - `*` = wildcard (all subdomains)
   - Leave blank = root domain (some registrars)

### Issue: DNS_PROBE_FINISHED_NXDOMAIN
**Symptoms**: Browser shows "This site can't be reached"

**Solutions**:
1. Domain registration expired - renew domain
2. Nameservers incorrect - check registrar settings
3. DNS records not saved - re-add and save

---

## Advanced DNS Configuration

### Using Cloudflare (Recommended)

**Benefits**:
- Free CDN (Content Delivery Network)
- DDoS protection
- Free SSL certificate
- DNS analytics
- Faster global propagation

**Setup**:
1. Sign up at [cloudflare.com](https://www.cloudflare.com)
2. Add site and select free plan
3. Import DNS records
4. Change nameservers at registrar
5. Wait for activation email
6. Configure SSL/TLS settings to "Full (strict)"

### DNSSEC (Optional Security)

DNSSEC prevents DNS spoofing attacks.

**Enable at registrar** if supported:
1. Go to DNSSEC settings
2. Enable DNSSEC
3. Copy DS record to your registrar

---

## Nameserver Configuration

### When to Change Nameservers

Change nameservers if using:
- Cloudflare
- AWS Route53
- Custom DNS provider

### How to Change Nameservers

1. Log in to domain registrar
2. Find "Nameservers" or "DNS Management"
3. Replace default nameservers with:
   ```
   lara.ns.cloudflare.com
   greg.ns.cloudflare.com
   ```
   (Example for Cloudflare)
4. Save changes
5. Wait 24-48 hours for propagation

---

## Testing and Validation Checklist

- [ ] nslookup cybte.com returns correct IP
- [ ] nslookup www.cybte.com returns correct IP
- [ ] ping cybte.com responds
- [ ] Website accessible via HTTP (before SSL)
- [ ] Website accessible via HTTPS (after SSL)
- [ ] No DNS errors in browser
- [ ] DNS propagation complete worldwide

---

## Timeline Expectations

| Task | Time Required |
|------|---------------|
| DNS record changes | 5-10 minutes |
| Initial propagation | 5-30 minutes |
| Full global propagation | 1-4 hours |
| Maximum wait time | 48 hours |

---

## Next Steps

After DNS is configured:
1. [Install SSL Certificate](SSL_SETUP.md)
2. [Configure Apache Virtual Host](DEPLOYMENT.md#step-6-apache-virtual-host-configuration)
3. [Deploy Application](DEPLOYMENT.md#step-4-application-deployment)
4. [Test Website Access](DEPLOYMENT.md#step-10-post-deployment-verification)

---

## Support Resources

- [Cloudflare DNS Docs](https://developers.cloudflare.com/dns/)
- [Google DNS Support](https://support.google.com/domains/answer/3290309)
- [GoDaddy DNS Help](https://www.godaddy.com/help/dns-management-665)
- [DNS Checker](https://dnschecker.org/)

---

**Last Updated**: March 2026
