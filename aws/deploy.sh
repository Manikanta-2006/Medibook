#!/bin/bash
# ============================================
# MediBook - AWS EKS Deployment Script
# ============================================
# One-click deployment to AWS EKS
# Usage: ./aws/deploy.sh
# ============================================

set -e

# Configuration
CLUSTER_NAME="medibook-cluster"
REGION="ap-south-1"
NAMESPACE="medibook"
DOCKER_IMAGE="manikanta2006/medibook-app"

echo "============================================"
echo "  MediBook - AWS EKS Deployment"
echo "============================================"
echo ""

# -----------------------------------------------
# Step 1: Check Prerequisites
# -----------------------------------------------
echo "📋 Step 1: Checking prerequisites..."

command -v aws >/dev/null 2>&1 || { echo "❌ AWS CLI not installed. Install: https://aws.amazon.com/cli/"; exit 1; }
command -v eksctl >/dev/null 2>&1 || { echo "❌ eksctl not installed. Install: https://eksctl.io/"; exit 1; }
command -v kubectl >/dev/null 2>&1 || { echo "❌ kubectl not installed. Install: https://kubernetes.io/docs/tasks/tools/"; exit 1; }
command -v docker >/dev/null 2>&1 || { echo "❌ Docker not installed."; exit 1; }

echo "  ✅ AWS CLI: $(aws --version 2>&1 | cut -d' ' -f1)"
echo "  ✅ eksctl: $(eksctl version)"
echo "  ✅ kubectl: $(kubectl version --client --short 2>/dev/null || kubectl version --client)"
echo "  ✅ Docker: $(docker --version)"
echo ""

# -----------------------------------------------
# Step 2: Verify AWS Credentials
# -----------------------------------------------
echo "📋 Step 2: Verifying AWS credentials..."
AWS_ACCOUNT=$(aws sts get-caller-identity --query "Account" --output text 2>/dev/null)
if [ -z "$AWS_ACCOUNT" ]; then
    echo "❌ AWS credentials not configured. Run: aws configure"
    exit 1
fi
echo "  ✅ AWS Account: $AWS_ACCOUNT"
echo ""

# -----------------------------------------------
# Step 3: Create EKS Cluster
# -----------------------------------------------
echo "📋 Step 3: Creating EKS cluster..."
echo "  ⏳ This takes 15-20 minutes..."

# Check if cluster already exists
if eksctl get cluster --name $CLUSTER_NAME --region $REGION 2>/dev/null; then
    echo "  ℹ️  Cluster '$CLUSTER_NAME' already exists. Skipping creation."
else
    eksctl create cluster -f aws/eks-cluster.yml
    echo "  ✅ EKS cluster created!"
fi
echo ""

# -----------------------------------------------
# Step 4: Configure kubectl
# -----------------------------------------------
echo "📋 Step 4: Configuring kubectl..."
aws eks update-kubeconfig --name $CLUSTER_NAME --region $REGION
echo "  ✅ kubectl configured for cluster: $CLUSTER_NAME"

# Verify connection
echo "  📊 Cluster nodes:"
kubectl get nodes
echo ""

# -----------------------------------------------
# Step 5: Build and Push Docker Image
# -----------------------------------------------
echo "📋 Step 5: Building and pushing Docker image..."
docker build -t $DOCKER_IMAGE:latest -f docker/php/Dockerfile .
docker push $DOCKER_IMAGE:latest
echo "  ✅ Image pushed: $DOCKER_IMAGE:latest"
echo ""

# -----------------------------------------------
# Step 6: Deploy Kubernetes Manifests
# -----------------------------------------------
echo "📋 Step 6: Deploying to Kubernetes..."

echo "  📦 Applying namespace..."
kubectl apply -f k8s/namespace.yml

echo "  📦 Applying configs and secrets..."
kubectl apply -f k8s/configmap.yml
kubectl apply -f k8s/secrets.yml

echo "  📦 Deploying MySQL..."
kubectl apply -f k8s/mysql/init-configmap.yml
kubectl apply -f k8s/mysql/pvc.yml
kubectl apply -f k8s/mysql/deployment.yml
kubectl apply -f k8s/mysql/service.yml

echo "  ⏳ Waiting for MySQL to be ready..."
kubectl wait --for=condition=ready pod -l tier=database -n $NAMESPACE --timeout=180s
echo "  ✅ MySQL is ready!"

echo "  📦 Deploying PHP Application..."
kubectl apply -f k8s/app/deployment.yml
kubectl apply -f k8s/app/service.yml
kubectl apply -f k8s/app/hpa.yml

echo "  📦 Deploying Nginx..."
kubectl apply -f k8s/nginx/configmap.yml
kubectl apply -f k8s/nginx/deployment.yml
kubectl apply -f k8s/nginx/service.yml

echo "  📦 Deploying phpMyAdmin..."
kubectl apply -f k8s/phpmyadmin/deployment.yml
kubectl apply -f k8s/phpmyadmin/service.yml

echo "  ✅ All manifests applied!"
echo ""

# -----------------------------------------------
# Step 7: Wait for Deployments
# -----------------------------------------------
echo "📋 Step 7: Waiting for deployments to be ready..."
kubectl rollout status deployment/medibook-app -n $NAMESPACE --timeout=120s
kubectl rollout status deployment/medibook-nginx -n $NAMESPACE --timeout=60s
echo "  ✅ All deployments ready!"
echo ""

# -----------------------------------------------
# Step 8: Get Access URLs
# -----------------------------------------------
echo "📋 Step 8: Getting access URLs..."
echo ""
echo "============================================"
echo "  🎉 MediBook Deployed Successfully!"
echo "============================================"
echo ""

# Show all pods
echo "📊 Pod Status:"
kubectl get pods -n $NAMESPACE -o wide
echo ""

# Show all services
echo "🌐 Services:"
kubectl get svc -n $NAMESPACE
echo ""

# Get LoadBalancer URL
echo "🔗 Application URL:"
LB_URL=$(kubectl get svc medibook-nginx-service -n $NAMESPACE -o jsonpath='{.status.loadBalancer.ingress[0].hostname}' 2>/dev/null)
if [ -n "$LB_URL" ]; then
    echo "   http://$LB_URL"
else
    echo "   ⏳ LoadBalancer URL is pending. Run this to check:"
    echo "   kubectl get svc medibook-nginx-service -n $NAMESPACE"
fi
echo ""

# phpMyAdmin URL
echo "🛠️  phpMyAdmin:"
echo "   Access via NodePort: http://<node-ip>:30080"
echo ""

echo "============================================"
echo "  Useful Commands:"
echo "  kubectl get pods -n $NAMESPACE"
echo "  kubectl logs -f deployment/medibook-app -n $NAMESPACE"
echo "  kubectl get hpa -n $NAMESPACE"
echo "  eksctl delete cluster -f aws/eks-cluster.yml"
echo "============================================"
