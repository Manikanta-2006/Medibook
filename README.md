# 🏥 MediBook — Hospital Appointment Booking System

A full-stack **Hospital Appointment Booking System** built with **PHP + MySQL**, containerized with **Docker**, automated with **GitHub Actions CI/CD**, and production-ready with **Nginx** reverse proxy and **AWS EC2** deployment.

---

## 📚 Overview

MediBook is a web-based healthcare management platform that enables patients to:
- Browse and search hospitals in Delhi
- Book appointments with top medical facilities
- Track bed availability across hospital wards
- Monitor medicine and equipment inventory
- Manage personal profiles and appointment history

---

## 🚀 Features

| Feature | Description |
|---------|-------------|
| 🧑‍⚕️ **Patient Records** | Register, login, manage profile & medical info |
| 📅 **Appointment Booking** | Book appointments with slot conflict prevention |
| 🛏️ **Bed Availability** | Real-time bed tracking across wards and ICUs |
| 💊 **Medicine Inventory** | Track medicines, equipment, and consumables |
| 🔍 **Hospital Search** | Filter by specialty, rating, and availability |
| 🔐 **Authentication** | Secure login/signup with password hashing |

---

## 🛠️ Tech Stack

### Application
| Technology | Purpose |
|-----------|---------|
| **PHP 8.2** | Backend logic & server-side processing |
| **MySQL 8.0** | Relational database |
| **HTML/CSS/JS** | Frontend UI |
| **Tailwind CSS** | Utility-first styling (via CDN) |
| **phpMyAdmin** | Database management interface |

### DevOps & Cloud
| Technology | Purpose |
|-----------|---------|
| **Docker** | Containerization |
| **Docker Compose** | Multi-container orchestration |
| **Nginx** | Reverse proxy, SSL termination, rate limiting |
| **GitHub Actions** | CI/CD pipeline (Build → Test → Push → Deploy) |
| **AWS EC2** | Cloud hosting (Ubuntu 22.04) |
| **Let's Encrypt** | Free SSL certificates via Certbot |

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    AWS EC2 Instance                      │
│                                                         │
│  ┌───────────┐    ┌──────────────┐    ┌──────────────┐ │
│  │   Nginx   │───▶│  PHP+Apache  │───▶│   MySQL 8.0  │ │
│  │  :80/:443 │    │    :8080     │    │    :3306     │ │
│  │           │    │              │    │              │ │
│  │ - SSL     │    │ - MediBook   │    │ - hospital_db│ │
│  │ - Proxy   │    │ - PDO        │    │ - Persistent │ │
│  │ - Rate    │    │ - Sessions   │    │   Volume     │ │
│  │   Limit   │    │              │    │              │ │
│  └───────────┘    └──────────────┘    └──────────────┘ │
│                                              │         │
│                                    ┌─────────┴──────┐  │
│                                    │  phpMyAdmin     │  │
│                                    │    :8081        │  │
│                                    └────────────────┘  │
└─────────────────────────────────────────────────────────┘
         ▲
         │ HTTPS
    ┌────┴────┐
    │  Users  │
    └─────────┘
```

---

## 📁 Project Structure

```
MediBook/
├── src/                          # Application source code
│   ├── home.php                  # Hospital listings & booking
│   ├── login.php                 # User login
│   ├── signup.php                # User registration
│   ├── profile.php               # User profile & appointments
│   ├── beds.php                  # Bed availability view
│   ├── inventory.php             # Inventory status view
│   ├── images/                   # Hospital images
│   └── php/                      # Backend API handlers
│       ├── config.php            # Database config (env vars)
│       ├── auth.php              # Login/Signup/Logout
│       ├── appointment.php       # Appointment booking
│       ├── cancel_appointment.php
│       ├── update_profile.php
│       ├── beds.php              # Beds API
│       ├── inventory.php         # Inventory API
│       ├── city_hospitals.php    # Hospital search API
│       └── profile.php           # Profile API
├── docker/                       # Docker configuration
│   ├── php/
│   │   ├── Dockerfile            # PHP 8.2 + Apache image
│   │   └── php.ini               # PHP production settings
│   ├── nginx/
│   │   ├── nginx.conf            # Nginx main config
│   │   └── default.conf          # Server block + proxy
│   └── mysql/
│       └── init.sql              # Database schema + seed data
├── .github/
│   └── workflows/
│       └── deploy.yml            # CI/CD pipeline
├── docker-compose.yml            # Development stack
├── docker-compose.prod.yml       # Production overrides
├── .env.example                  # Environment template
├── .gitignore                    # Git ignore rules
├── .dockerignore                 # Docker ignore rules
├── Makefile                      # Convenience commands
├── DEPLOYMENT.md                 # AWS EC2 deployment guide
└── README.md                     # This file
```

---

## ⚡ Quick Start (Docker)

### Prerequisites
- [Docker](https://docs.docker.com/get-docker/) installed
- [Docker Compose](https://docs.docker.com/compose/install/) installed

### 1. Clone the Repository

```bash
git clone https://github.com/Manikanta-2006/Medibook.git
cd Medibook
```

### 2. Configure Environment

```bash
cp .env.example .env
# Edit .env if you want to change default credentials
```

### 3. Start the Application

```bash
# Using Makefile
make up

# OR using Docker Compose directly
docker-compose up -d --build
```

### 4. Access the Application

| Service | URL | Credentials |
|---------|-----|-------------|
| **MediBook App** | http://localhost:8080 | Sign up for a new account |
| **phpMyAdmin** | http://localhost:8081 | root / medibook_root_2025 |

### 5. Stop the Application

```bash
make down
# OR
docker-compose down
```

---

## 💻 Local Development (XAMPP)

For development without Docker:

1. Install [XAMPP](https://www.apachefriends.org/)
2. Copy `src/` contents to `htdocs/medibook/`
3. Import `docker/mysql/init.sql` via phpMyAdmin
4. Access at `http://localhost/medibook/home.php`

> **Note:** When running locally with XAMPP, `config.php` falls back to default credentials (`root` / empty password).

---

## 🔐 Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment mode | `development` |
| `APP_DEBUG` | Debug mode | `true` |
| `APP_PORT` | Application port | `8080` |
| `MYSQL_ROOT_PASSWORD` | MySQL root password | `medibook_root_2025` |
| `MYSQL_DATABASE` | Database name | `hospital_db` |
| `MYSQL_USER` | Application DB user | `medibook_user` |
| `MYSQL_PASSWORD` | Application DB password | `medibook_pass_2025` |
| `MYSQL_HOST` | Database host | `db` |
| `MYSQL_PORT` | Database port | `3306` |
| `PMA_PORT` | phpMyAdmin port | `8081` |

---

## 🔄 CI/CD Pipeline
 
 ### Option A: GitHub Actions (`.github/workflows/deploy.yml`)
 The GitHub Actions pipeline runs automatically on push to main:
 ```
 Push to main → Build → Test → Push to Registry → Deploy to EC2
 ```
 
 | Stage | What it does |
 |-------|-------------|
 | **🔨 Build** | Builds Docker image from Dockerfile |
 | **🧪 Test** | PHP lint check, starts containers, verifies DB, HTTP health check |
 | **📤 Push** | Tags and pushes image to Docker Hub |
 | **🚀 Deploy** | SSHs into EC2, pulls latest, restarts containers |
 
 ### Option B: Jenkins Pipeline (`jenkins/Jenkinsfile`)
 A 6-stage declarative pipeline designed for automated K8s environments:
 ```
 Checkout → Build Docker → Test (Lint + Health) → Push to Hub → Deploy (K8s) → Verify Rollout
 ```
 
 ---
 
 ## ☸️ Kubernetes & AWS EKS Orchestration
 
 For enterprise scalability, MediBook is fully orchestrated with Kubernetes manifests (`k8s/`):
 
 - **High Availability**: Web app pods scaled to 2+ replicas with rolling updates.
 - **Autoscaling (HPA)**: Pods automatically scale from 2 to 5 based on CPU/Memory thresholds.
 - **Enterprise LoadBalancer**: Nginx reverse proxy exposed externally using AWS LoadBalancer integration.
 - **Persistent DB**: MySQL data mounted to persistent EBS volumes using PVCs (`gp2`).
 - **DB Admin**: NodePort-enabled phpMyAdmin exposed on port `30080`.
 
 For a complete local and AWS deployment walkthrough, see [KUBERNETES.md](KUBERNETES.md).
 
 ---
 
 ### Required Cloud Secrets
 
 | Secret | Description |
 |--------|-------------|
 | `DOCKER_HUB_USERNAME` | Docker Hub username |
 | `DOCKER_HUB_TOKEN` | Docker Hub access token |
 | `EC2_HOST` | EC2 instance public IP |
 | `EC2_USER` | SSH username (e.g., `ubuntu`) |
 | `EC2_SSH_KEY` | EC2 private SSH key |
 | `kubeconfig` | Jenkins Kubernetes config file |

---

## 🔒 Security Features

| Feature | Implementation |
|---------|---------------|
| **SQL Injection Prevention** | PDO prepared statements throughout |
| **XSS Prevention** | `htmlspecialchars()` on all output |
| **Password Security** | `password_hash()` with bcrypt |
| **Session Security** | HTTPOnly, SameSite=Strict cookies |
| **Environment Secrets** | Credentials via `.env` (gitignored) |
| **Security Headers** | X-Frame-Options, X-Content-Type-Options, X-XSS-Protection |
| **Rate Limiting** | Nginx rate limits on login/signup endpoints |
| **Input Validation** | Server-side validation on all forms |

---

## 🛠️ Makefile Commands

```bash
make help          # Show all available commands
make up            # Start development containers
make down          # Stop all containers
make build         # Build Docker images
make rebuild       # Rebuild without cache
make logs          # View all container logs
make shell         # Open PHP container shell
make db-shell      # Open MySQL shell
make db-backup     # Export database backup
make db-restore    # Restore from latest backup
make lint          # PHP syntax check
make prod          # Start production stack
make prod-down     # Stop production stack
make clean         # Remove everything (containers, volumes, images)
```

---

## ☁️ Cloud Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for a comprehensive guide covering:

- AWS EC2 instance setup
- Docker & Docker Compose installation
- Application deployment
- Domain & DNS configuration
- SSL certificate setup with Certbot
- Monitoring & health checks
- Automated database backups
- Alternative platforms (DigitalOcean, Render, Railway)

---

## 🗄️ Database Schema

| Table | Description | Key Fields |
|-------|-------------|------------|
| `users` | Patient accounts | id, username, email, password, phone |
| `hospitals` | Hospital listings | id, name, address, specialties, rating |
| `appointments` | Booked appointments | id, user_id, hospital_id, date, time_slot |
| `beds` | Ward bed tracking | id, hospital_id, ward_type, total, available |
| `inventory` | Medicine/equipment | id, hospital_id, item_name, category, quantity |

---

## 👥 Contributors

- **Manikanta** — Developer

---

## 📄 License

This project is developed for academic purposes.
