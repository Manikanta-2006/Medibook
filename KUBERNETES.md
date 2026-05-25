# MediBook — Kubernetes & AWS EKS Deployment Guide

Complete guide for deploying MediBook on Kubernetes locally and on AWS EKS with Jenkins CI/CD.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Prerequisites](#2-prerequisites)
3. [Local Kubernetes (Docker Desktop)](#3-local-kubernetes-docker-desktop)
4. [AWS EKS Deployment](#4-aws-eks-deployment)
5. [Jenkins CI/CD Setup](#5-jenkins-cicd-setup)
6. [Scaling & Monitoring](#6-scaling--monitoring)
7. [Kubernetes Commands Reference](#7-kubernetes-commands-reference)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Architecture Overview

```
                      ┌──────────────────────────────────────┐
                      │       AWS EKS Cluster                │
                      │                                      │
  Internet ──▶  ┌─────┴─────┐    ┌──────────────┐           │
                │   Nginx   │───▶│  PHP App     │           │
                │   (LB)    │    │  (2-5 pods)  │           │
                │   :80     │    │  HPA enabled │           │
                └───────────┘    └──────┬───────┘           │
                                       │                    │
                                 ┌─────▼──────┐            │
                                 │  MySQL 8.0 │            │
                                 │  (1 pod)   │            │
                                 │  PVC: 5Gi  │            │
                                 └────────────┘            │
                                       │                    │
                                 ┌─────┴──────┐            │
                                 │ phpMyAdmin  │            │
                                 │ (NodePort)  │            │
                                 └────────────┘            │
                      └──────────────────────────────────────┘

  Jenkins Pipeline:
  ┌──────────┐  ┌──────────┐  ┌──────┐  ┌──────┐  ┌────────┐  ┌────────┐
  │ Checkout │─▶│  Build   │─▶│ Test │─▶│ Push │─▶│Deploy  │─▶│Verify  │
  │   Git    │  │  Docker  │  │ Lint │  │  Hub │  │  K8s   │  │Health  │
  └──────────┘  └──────────┘  └──────┘  └──────┘  └────────┘  └────────┘
```

### Kubernetes Resources

| Resource | Name | Type | Purpose |
|----------|------|------|---------|
| Namespace | `medibook` | — | Isolate all resources |
| ConfigMap | `medibook-config` | — | Non-secret app config |
| Secret | `medibook-secrets` | Opaque | MySQL credentials |
| Deployment | `medibook-mysql` | 1 replica | Database server |
| Service | `medibook-mysql-service` | ClusterIP | Internal DB access |
| PVC | `medibook-mysql-pvc` | 5Gi gp2 | Persistent DB storage |
| Deployment | `medibook-app` | 2 replicas | PHP application |
| Service | `medibook-app-service` | ClusterIP | Internal app routing |
| HPA | `medibook-app-hpa` | 2-5 pods | Auto-scale on CPU/memory |
| Deployment | `medibook-nginx` | 1 replica | Reverse proxy |
| Service | `medibook-nginx-service` | LoadBalancer | Public entry point |
| Deployment | `medibook-phpmyadmin` | 1 replica | DB admin UI |
| Service | `medibook-phpmyadmin-service` | NodePort:30080 | Admin access |

---

## 2. Prerequisites

### Required Tools

| Tool | Install Command | Purpose |
|------|----------------|---------|
| **Docker** | [docker.com](https://docker.com) | Container runtime |
| **kubectl** | `brew install kubectl` | K8s CLI |
| **eksctl** | `brew install eksctl` | EKS cluster management |
| **AWS CLI** | `brew install awscli` | AWS operations |
| **Jenkins** | [jenkins.io](https://jenkins.io) | CI/CD server |

### Install on Mac

```bash
# Install kubectl
brew install kubectl

# Install eksctl
brew install eksctl

# Install AWS CLI
brew install awscli

# Configure AWS credentials
aws configure
# Enter: Access Key, Secret Key, Region (ap-south-1), Output (json)
```

### Docker Hub Account

Create a free account at [hub.docker.com](https://hub.docker.com) to push/pull images.

```bash
# Login to Docker Hub
docker login
```

---

## 3. Local Kubernetes (Docker Desktop)

### 3.1 Enable Kubernetes

1. Open **Docker Desktop** → **Settings** → **Kubernetes**
2. Check **Enable Kubernetes**
3. Click **Apply & Restart**
4. Wait for K8s to start (green indicator)

### 3.2 Verify Kubernetes

```bash
kubectl cluster-info
kubectl get nodes
```

### 3.3 Build and Push Image

```bash
# Build the Docker image
docker build -t manikanta2006/medibook-app:latest -f docker/php/Dockerfile .

# Push to Docker Hub
docker push manikanta2006/medibook-app:latest
```

### 3.4 Deploy to Local K8s

```bash
# For local testing, change PVC storageClass
# Edit k8s/mysql/pvc.yml: change storageClassName from 'gp2' to 'hostpath'

# Apply all manifests
kubectl apply -f k8s/namespace.yml
kubectl apply -f k8s/configmap.yml
kubectl apply -f k8s/secrets.yml

# MySQL
kubectl apply -f k8s/mysql/init-configmap.yml
kubectl apply -f k8s/mysql/pvc.yml
kubectl apply -f k8s/mysql/deployment.yml
kubectl apply -f k8s/mysql/service.yml

# Wait for MySQL
kubectl wait --for=condition=ready pod -l tier=database -n medibook --timeout=120s

# App
kubectl apply -f k8s/app/deployment.yml
kubectl apply -f k8s/app/service.yml
kubectl apply -f k8s/app/hpa.yml

# Nginx
kubectl apply -f k8s/nginx/configmap.yml
kubectl apply -f k8s/nginx/deployment.yml
kubectl apply -f k8s/nginx/service.yml

# phpMyAdmin
kubectl apply -f k8s/phpmyadmin/deployment.yml
kubectl apply -f k8s/phpmyadmin/service.yml
```

### 3.5 Access Locally

```bash
# Check pod status
kubectl get pods -n medibook

# Get service URLs
kubectl get svc -n medibook

# Access app (LoadBalancer on Docker Desktop uses localhost)
# App:        http://localhost:80
# phpMyAdmin: http://localhost:30080
```

---

## 4. AWS EKS Deployment

### 4.1 One-Click Deploy

```bash
./aws/deploy.sh
```

This script:
1. ✅ Checks prerequisites (AWS CLI, eksctl, kubectl, Docker)
2. ✅ Verifies AWS credentials
3. ✅ Creates EKS cluster (~15-20 min)
4. ✅ Configures kubectl
5. ✅ Builds & pushes Docker image
6. ✅ Applies all K8s manifests
7. ✅ Waits for rollout completion
8. ✅ Prints access URLs

### 4.2 Manual Step-by-Step

```bash
# Step 1: Create EKS cluster
eksctl create cluster -f aws/eks-cluster.yml
# ⏳ Takes 15-20 minutes

# Step 2: Configure kubectl
aws eks update-kubeconfig --name medibook-cluster --region ap-south-1

# Step 3: Verify nodes
kubectl get nodes

# Step 4: Build & push image
docker build -t manikanta2006/medibook-app:latest -f docker/php/Dockerfile .
docker push manikanta2006/medibook-app:latest

# Step 5: Deploy manifests (same as Section 3.4)
kubectl apply -f k8s/namespace.yml
kubectl apply -f k8s/configmap.yml
kubectl apply -f k8s/secrets.yml
kubectl apply -f k8s/mysql/
kubectl wait --for=condition=ready pod -l tier=database -n medibook --timeout=180s
kubectl apply -f k8s/app/
kubectl apply -f k8s/nginx/
kubectl apply -f k8s/phpmyadmin/

# Step 6: Get LoadBalancer URL
kubectl get svc medibook-nginx-service -n medibook
# The EXTERNAL-IP column shows the AWS ELB URL
```

### 4.3 Delete EKS Cluster

```bash
# Delete all K8s resources first
kubectl delete namespace medibook

# Delete the EKS cluster
eksctl delete cluster -f aws/eks-cluster.yml
# ⏳ Takes 5-10 minutes
```

---

## 5. Jenkins CI/CD Setup

### 5.1 Install Jenkins

**Option A: Local Jenkins**
```bash
# Mac
brew install jenkins-lts
brew services start jenkins-lts
# Access: http://localhost:8080
```

**Option B: Jenkins on Kubernetes**
```bash
kubectl apply -f jenkins/jenkins-deployment.yml
kubectl get svc jenkins-service -n jenkins
# Access via LoadBalancer URL
```

### 5.2 Configure Jenkins Credentials

In Jenkins → **Manage Jenkins** → **Credentials** → **Add Credentials**:

| ID | Type | Description |
|----|------|-------------|
| `docker-hub-credentials` | Username/Password | Docker Hub login |
| `kubeconfig` | Secret File | Kubernetes config file (`~/.kube/config`) |

### 5.3 Create Jenkins Pipeline

1. Jenkins → **New Item** → **Pipeline** → Name: `MediBook`
2. **Pipeline** section:
   - **Definition**: Pipeline script from SCM
   - **SCM**: Git
   - **Repository URL**: `https://github.com/Manikanta-2006/Medibook.git`
   - **Script Path**: `jenkins/Jenkinsfile`
3. Click **Save**
4. Click **Build Now**

### 5.4 Pipeline Stages

| # | Stage | What It Does | Duration |
|---|-------|-------------|----------|
| 1 | **Checkout** | Clone code from GitHub | ~5s |
| 2 | **Build Docker Image** | Build PHP+Apache image | ~30s |
| 3 | **Test** | PHP lint, start containers, health check | ~60s |
| 4 | **Push to Docker Hub** | Tag + push image (main only) | ~20s |
| 5 | **Deploy to Kubernetes** | Apply K8s manifests, update image | ~30s |
| 6 | **Verify Deployment** | Wait for rollout, check pods | ~30s |

---

## 6. Scaling & Monitoring

### Auto-Scaling (HPA)

```bash
# View current HPA status
kubectl get hpa -n medibook

# Manually scale
kubectl scale deployment medibook-app --replicas=4 -n medibook

# Watch pods scale
kubectl get pods -n medibook -w
```

### Monitoring

```bash
# Pod resource usage (requires metrics-server)
kubectl top pods -n medibook

# Node resource usage
kubectl top nodes

# Pod logs
kubectl logs -f deployment/medibook-app -n medibook

# MySQL logs
kubectl logs -f deployment/medibook-mysql -n medibook

# Nginx logs
kubectl logs -f deployment/medibook-nginx -n medibook
```

### Install Metrics Server (for HPA)

```bash
kubectl apply -f https://github.com/kubernetes-sigs/metrics-server/releases/latest/download/components.yaml
```

---

## 7. Kubernetes Commands Reference

```bash
# -------- Namespace --------
kubectl get all -n medibook              # All resources
kubectl get pods -n medibook             # List pods
kubectl get svc -n medibook              # List services
kubectl get deploy -n medibook           # List deployments
kubectl get pvc -n medibook              # List volumes

# -------- Debugging --------
kubectl describe pod <pod-name> -n medibook
kubectl logs <pod-name> -n medibook
kubectl exec -it <pod-name> -n medibook -- bash

# -------- Scaling --------
kubectl scale deploy medibook-app --replicas=3 -n medibook
kubectl get hpa -n medibook

# -------- Updates --------
kubectl set image deployment/medibook-app \
    medibook-app=manikanta2006/medibook-app:v2 -n medibook
kubectl rollout status deployment/medibook-app -n medibook
kubectl rollout undo deployment/medibook-app -n medibook   # Rollback

# -------- Cleanup --------
kubectl delete namespace medibook        # Delete everything
```

---

## 8. Troubleshooting

| Issue | Solution |
|-------|----------|
| Pod stuck in `Pending` | Check PVC: `kubectl describe pvc -n medibook` |
| Pod in `CrashLoopBackOff` | Check logs: `kubectl logs <pod> -n medibook` |
| LoadBalancer no external IP | Wait 2-3 min, or check AWS ELB in console |
| MySQL connection refused | Ensure MySQL pod is `Running` and service exists |
| Image pull error | Verify image exists: `docker pull manikanta2006/medibook-app` |
| HPA not scaling | Install metrics-server, check: `kubectl get hpa -n medibook` |
| `storageClassName` error | Change `gp2` to `hostpath` for local, `gp2` for AWS |

### Reset Everything

```bash
# Delete all MediBook resources
kubectl delete namespace medibook

# Re-deploy
kubectl apply -f k8s/namespace.yml
# ... (apply remaining manifests)
```
