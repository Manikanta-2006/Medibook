# ============================================
# MediBook - Makefile
# ============================================
# Convenience commands for development and
# production operations
# ============================================

.PHONY: help up down build rebuild logs shell db-shell db-backup db-restore lint prod prod-down clean status k8s-apply k8s-delete k8s-status k8s-logs k8s-scale eks-create eks-delete

# Default target
help: ## Show this help message
	@echo ""
	@echo "  MediBook - Available Commands"
	@echo "  =============================="
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
	@echo ""

# -----------------------------------------
# Development Commands
# -----------------------------------------

up: ## Start all containers (development)
	docker-compose up -d
	@echo ""
	@echo "✅ MediBook is running!"
	@echo "   App:        http://localhost:8080"
	@echo "   phpMyAdmin: http://localhost:8081"
	@echo ""

down: ## Stop all containers
	docker-compose down
	@echo "✅ All containers stopped"

build: ## Build Docker images
	docker-compose build
	@echo "✅ Images built successfully"

rebuild: ## Rebuild images from scratch (no cache)
	docker-compose build --no-cache
	@echo "✅ Images rebuilt from scratch"

logs: ## View container logs (follow mode)
	docker-compose logs -f

logs-app: ## View only app container logs
	docker-compose logs -f app

logs-db: ## View only database logs
	docker-compose logs -f db

status: ## Show container status
	docker-compose ps

# -----------------------------------------
# Shell Access
# -----------------------------------------

shell: ## Open a shell in the PHP container
	docker-compose exec app bash

db-shell: ## Open MySQL shell
	docker-compose exec db mysql -u root -p$(shell grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) hospital_db

# -----------------------------------------
# Database Management
# -----------------------------------------

db-backup: ## Export database to backup file
	@mkdir -p backups
	docker-compose exec -T db mysqldump -u root \
		-p$(shell grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) \
		hospital_db > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Database backed up to backups/ directory"

db-restore: ## Restore database from latest backup
	@LATEST=$$(ls -t backups/*.sql 2>/dev/null | head -1); \
	if [ -z "$$LATEST" ]; then \
		echo "❌ No backup files found in backups/"; \
	else \
		echo "Restoring from $$LATEST..."; \
		docker-compose exec -T db mysql -u root \
			-p$(shell grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) \
			hospital_db < $$LATEST; \
		echo "✅ Database restored from $$LATEST"; \
	fi

db-reset: ## Reset database with initial seed data
	docker-compose exec -T db mysql -u root \
		-p$(shell grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) \
		< docker/mysql/init.sql
	@echo "✅ Database reset with seed data"

# -----------------------------------------
# Code Quality
# -----------------------------------------

lint: ## Run PHP syntax check on all files
	@echo "Running PHP lint check..."
	@find src/ -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"
	@echo "✅ PHP lint check complete"

# -----------------------------------------
# Production Commands
# -----------------------------------------

prod: ## Start production stack (with Nginx)
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
	@echo ""
	@echo "✅ MediBook Production is running!"
	@echo "   App: http://localhost (via Nginx)"
	@echo ""

prod-down: ## Stop production stack
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml down
	@echo "✅ Production stack stopped"

prod-logs: ## View production logs
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# -----------------------------------------
# Cleanup
# -----------------------------------------

clean: ## Remove all containers, volumes, and images
	docker-compose down -v --rmi all
	@echo "✅ All containers, volumes, and images removed"

prune: ## Remove unused Docker resources
	docker system prune -f
	@echo "✅ Docker resources pruned"

# -----------------------------------------
# Kubernetes Commands
# -----------------------------------------

KUBE_NAMESPACE = medibook

k8s-apply: ## Deploy all K8s manifests
	@echo "📦 Deploying MediBook to Kubernetes..."
	kubectl apply -f k8s/namespace.yml
	kubectl apply -f k8s/configmap.yml
	kubectl apply -f k8s/secrets.yml
	kubectl apply -f k8s/mysql/init-configmap.yml
	kubectl apply -f k8s/mysql/pvc.yml
	kubectl apply -f k8s/mysql/deployment.yml
	kubectl apply -f k8s/mysql/service.yml
	@echo "⏳ Waiting for MySQL..."
	kubectl wait --for=condition=ready pod -l tier=database -n $(KUBE_NAMESPACE) --timeout=120s
	kubectl apply -f k8s/app/deployment.yml
	kubectl apply -f k8s/app/service.yml
	kubectl apply -f k8s/app/hpa.yml
	kubectl apply -f k8s/nginx/configmap.yml
	kubectl apply -f k8s/nginx/deployment.yml
	kubectl apply -f k8s/nginx/service.yml
	kubectl apply -f k8s/phpmyadmin/deployment.yml
	kubectl apply -f k8s/phpmyadmin/service.yml
	@echo "✅ All K8s resources deployed!"
	@echo ""
	kubectl get all -n $(KUBE_NAMESPACE)

k8s-delete: ## Delete all K8s resources
	kubectl delete namespace $(KUBE_NAMESPACE)
	@echo "✅ All K8s resources deleted"

k8s-status: ## Show K8s pod and service status
	@echo "📊 Pods:"
	kubectl get pods -n $(KUBE_NAMESPACE) -o wide
	@echo ""
	@echo "🌐 Services:"
	kubectl get svc -n $(KUBE_NAMESPACE)
	@echo ""
	@echo "📈 HPA:"
	kubectl get hpa -n $(KUBE_NAMESPACE)

k8s-logs: ## View app pod logs in K8s
	kubectl logs -f deployment/medibook-app -n $(KUBE_NAMESPACE)

k8s-scale: ## Scale app to 3 replicas
	kubectl scale deployment medibook-app --replicas=3 -n $(KUBE_NAMESPACE)
	@echo "✅ App scaled to 3 replicas"

# -----------------------------------------
# AWS EKS Commands
# -----------------------------------------

eks-create: ## Create AWS EKS cluster
	@echo "🚀 Creating EKS cluster (takes 15-20 min)..."
	exksctl create cluster -f aws/eks-cluster.yml
	aws eks update-kubeconfig --name medibook-cluster --region ap-south-1
	@echo "✅ EKS cluster created and kubectl configured!"

eks-delete: ## Delete AWS EKS cluster
	@echo "⚠️  Deleting EKS cluster..."
	exksctl delete cluster -f aws/eks-cluster.yml
	@echo "✅ EKS cluster deleted"

eks-deploy: ## Full deploy to EKS (create cluster + deploy app)
	./aws/deploy.sh
