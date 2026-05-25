# MediBook — AWS EC2 Deployment Guide

Complete step-by-step guide to deploy MediBook on an AWS EC2 instance with Docker, Nginx, and SSL.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [EC2 Instance Setup](#2-ec2-instance-setup)
3. [Server Preparation](#3-server-preparation)
4. [Application Deployment](#4-application-deployment)
5. [Domain & DNS Configuration](#5-domain--dns-configuration)
6. [SSL Certificate Setup](#6-ssl-certificate-setup)
7. [Monitoring & Logging](#7-monitoring--logging)
8. [Database Backup](#8-database-backup)
9. [Troubleshooting](#9-troubleshooting)
10. [Alternative Platforms](#10-alternative-platforms)

---

## 1. Prerequisites

- AWS Account with EC2 access
- A registered domain name (optional but recommended)
- SSH key pair for EC2 access
- GitHub repository with MediBook project

---

## 2. EC2 Instance Setup

### 2.1 Launch EC2 Instance

1. Go to **AWS Console** → **EC2** → **Launch Instance**
2. Configure:

| Setting | Value |
|---------|-------|
| **Name** | MediBook-Server |
| **AMI** | Ubuntu Server 22.04 LTS |
| **Instance Type** | t2.micro (Free Tier) or t2.small |
| **Key Pair** | Create or select existing |
| **Storage** | 20 GB gp3 |

### 2.2 Configure Security Group

Create a security group with these inbound rules:

| Type | Port | Source | Purpose |
|------|------|--------|---------|
| SSH | 22 | Your IP | Server access |
| HTTP | 80 | 0.0.0.0/0 | Web traffic |
| HTTPS | 443 | 0.0.0.0/0 | Secure web traffic |
| Custom TCP | 8080 | Your IP | Direct app access (optional) |
| Custom TCP | 8081 | Your IP | phpMyAdmin (optional) |

### 2.3 Allocate Elastic IP

1. Go to **EC2** → **Elastic IPs** → **Allocate**
2. Associate the Elastic IP with your instance
3. Note the IP address (e.g., `3.110.XX.XX`)

### 2.4 Connect to Instance

```bash
# Set permissions on key file
chmod 400 your-key.pem

# Connect via SSH
ssh -i your-key.pem ubuntu@your-elastic-ip
```

---

## 3. Server Preparation

### 3.1 Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### 3.2 Install Docker

```bash
# Install prerequisites
sudo apt install -y apt-transport-https ca-certificates curl software-properties-common

# Add Docker's GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# Add user to docker group (avoid using sudo)
sudo usermod -aG docker $USER
newgrp docker

# Verify Docker installation
docker --version
```

### 3.3 Install Docker Compose

```bash
# Download Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

# Set permissions
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker-compose --version
```

### 3.4 Install Git

```bash
sudo apt install -y git
```

---

## 4. Application Deployment

### 4.1 Clone Repository

```bash
# Create application directory
sudo mkdir -p /opt/medibook
sudo chown $USER:$USER /opt/medibook

# Clone the repository
cd /opt/medibook
git clone https://github.com/Manikanta-2006/Medibook.git .
```

### 4.2 Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit environment variables for production
nano .env
```

Update `.env` with production values:

```env
# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_PORT=8080

# MySQL - USE STRONG PASSWORDS IN PRODUCTION
MYSQL_ROOT_PASSWORD=your_strong_root_password_here
MYSQL_DATABASE=hospital_db
MYSQL_USER=medibook_user
MYSQL_PASSWORD=your_strong_password_here
MYSQL_HOST=db
MYSQL_PORT=3306

# phpMyAdmin (keep disabled in production)
PMA_PORT=8081

# Nginx
NGINX_PORT=80
NGINX_SSL_PORT=443
```

### 4.3 Start Application

```bash
# Build and start containers (Development mode)
docker-compose up -d --build

# OR: Start in Production mode (with Nginx)
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### 4.4 Verify Deployment

```bash
# Check container status
docker-compose ps

# Check logs
docker-compose logs -f

# Test application
curl http://localhost:8080/login.php
```

---

## 5. Domain & DNS Configuration

### 5.1 Point Domain to EC2

1. Go to your **domain registrar** (e.g., GoDaddy, Namecheap, Route 53)
2. Add an **A Record**:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | @ | Your Elastic IP | 300 |
| A | www | Your Elastic IP | 300 |

3. Wait for DNS propagation (5-30 minutes)

### 5.2 Verify DNS

```bash
# Check if domain resolves to your IP
nslookup yourdomain.com
dig yourdomain.com
```

---

## 6. SSL Certificate Setup

### 6.1 Install Certbot

```bash
sudo apt install -y certbot
```

### 6.2 Obtain SSL Certificate

```bash
# Stop Nginx temporarily (if running on port 80)
docker-compose -f docker-compose.yml -f docker-compose.prod.yml stop nginx

# Get certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Certificate files will be at:
# /etc/letsencrypt/live/yourdomain.com/fullchain.pem
# /etc/letsencrypt/live/yourdomain.com/privkey.pem
```

### 6.3 Update Nginx Configuration

Edit `docker/nginx/default.conf`:

1. Uncomment the HTTPS server block
2. Replace `medibook.example.com` with your actual domain
3. Add HTTP → HTTPS redirect:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

### 6.4 Mount SSL Certificates

Update `docker-compose.prod.yml` nginx volumes:

```yaml
volumes:
  - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
  - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
  - /etc/letsencrypt:/etc/letsencrypt:ro
```

### 6.5 Restart with SSL

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 6.6 Auto-Renewal Setup

```bash
# Add cron job for automatic renewal
sudo crontab -e

# Add this line (renews at 3 AM daily):
0 3 * * * certbot renew --quiet && docker restart medibook-nginx
```

---

## 7. Monitoring & Logging

### 7.1 View Logs

```bash
# All container logs
docker-compose logs -f

# Specific container
docker-compose logs -f app
docker-compose logs -f db
docker-compose logs -f nginx

# Last 100 lines
docker-compose logs --tail=100 app
```

### 7.2 Health Check Script

Create `/opt/medibook/scripts/health-check.sh`:

```bash
#!/bin/bash
# MediBook Health Check Script

APP_URL="http://localhost:8080"
LOG_FILE="/opt/medibook/logs/health-check.log"

mkdir -p /opt/medibook/logs

HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $APP_URL/login.php)
TIMESTAMP=$(date "+%Y-%m-%d %H:%M:%S")

if [ "$HTTP_STATUS" -eq 200 ]; then
    echo "$TIMESTAMP - ✅ App is healthy (HTTP $HTTP_STATUS)" >> $LOG_FILE
else
    echo "$TIMESTAMP - ❌ App is DOWN (HTTP $HTTP_STATUS)" >> $LOG_FILE
    # Restart containers
    cd /opt/medibook
    docker-compose restart
    echo "$TIMESTAMP - 🔄 Containers restarted" >> $LOG_FILE
fi
```

```bash
# Make executable and add to cron
chmod +x /opt/medibook/scripts/health-check.sh

# Run every 5 minutes
crontab -e
*/5 * * * * /opt/medibook/scripts/health-check.sh
```

### 7.3 Monitor Resources

```bash
# Container resource usage
docker stats

# Disk usage
df -h
docker system df
```

---

## 8. Database Backup

### 8.1 Manual Backup

```bash
cd /opt/medibook

# Using Makefile
make db-backup

# OR manually
docker-compose exec -T db mysqldump -u root -pyour_password hospital_db > backups/manual_backup.sql
```

### 8.2 Automated Daily Backup

Create `/opt/medibook/scripts/db-backup.sh`:

```bash
#!/bin/bash
# MediBook Database Backup Script

BACKUP_DIR="/opt/medibook/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/hospital_db_$TIMESTAMP.sql"

mkdir -p $BACKUP_DIR

# Create backup
docker-compose -f /opt/medibook/docker-compose.yml exec -T db \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} hospital_db > $BACKUP_FILE

# Compress
gzip $BACKUP_FILE

# Remove backups older than 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

echo "$(date) - Backup created: ${BACKUP_FILE}.gz"
```

```bash
# Schedule daily backup at 2 AM
chmod +x /opt/medibook/scripts/db-backup.sh
crontab -e
0 2 * * * /opt/medibook/scripts/db-backup.sh >> /opt/medibook/logs/backup.log 2>&1
```

---

## 9. Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| Container won't start | `docker-compose logs app` — check for errors |
| Database connection refused | Ensure `db` container is healthy: `docker-compose ps` |
| Port already in use | Change port in `.env` or stop conflicting service |
| Permission denied | `sudo chown -R $USER:$USER /opt/medibook` |
| MySQL not initializing | Remove volume: `docker-compose down -v` and restart |

### Useful Commands

```bash
# Restart all containers
docker-compose restart

# Rebuild and restart
docker-compose up -d --build

# Enter container shell
docker-compose exec app bash
docker-compose exec db bash

# Check container health
docker inspect --format='{{json .State.Health}}' medibook-db

# View real-time logs
docker-compose logs -f --tail=50

# Complete reset (WARNING: deletes all data)
docker-compose down -v
docker-compose up -d --build
```

---

## 10. Alternative Platforms

### DigitalOcean Droplet

1. Create a Droplet (Ubuntu 22.04, $6/month)
2. Follow same steps from [Section 3](#3-server-preparation) onwards
3. Use DigitalOcean's floating IP instead of Elastic IP

### Render

1. Create a new **Web Service** on [render.com](https://render.com)
2. Connect your GitHub repository
3. Set build command: `docker build -t medibook -f docker/php/Dockerfile .`
4. Set start command: `docker-compose up`
5. Add environment variables in Render dashboard

### Railway

1. Connect GitHub repo at [railway.app](https://railway.app)
2. Railway auto-detects `docker-compose.yml`
3. Add MySQL plugin from Railway dashboard
4. Deploy automatically on push

---

## Production Deployment Commands — Quick Reference

```bash
# First-time deployment
git clone https://github.com/Manikanta-2006/Medibook.git /opt/medibook
cd /opt/medibook
cp .env.example .env
nano .env  # Update production credentials
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Update deployment
cd /opt/medibook
git pull origin main
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker image prune -f

# Rollback
git log --oneline -5
git checkout <previous-commit-hash>
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```
