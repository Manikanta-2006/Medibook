# ============================================
# MediBook - Terraform Outputs
# ============================================

output "eks_cluster_name" {
  description = "Name of the EKS Cluster"
  value       = aws_eks_cluster.medibook.name
}

output "eks_cluster_endpoint" {
  description = "EKS Control Plane HTTPS API endpoint"
  value       = aws_eks_cluster.medibook.endpoint
}

output "eks_cluster_security_group_id" {
  description = "Security group ID attached to the cluster Control Plane"
  value       = aws_eks_cluster.medibook.vpc_config[0].cluster_security_group_id
}

output "vpc_id" {
  description = "VPC ID dedicated for EKS"
  value       = aws_vpc.eks_vpc.id
}

output "public_subnets" {
  description = "List of public subnet IDs"
  value       = aws_subnet.public[*].id
}

output "private_subnets" {
  description = "List of private subnet IDs"
  value       = aws_subnet.private[*].id
}

output "kubeconfig_connection_command" {
  description = "Command to authenticate kubectl with EKS"
  value       = "aws eks update-kubeconfig --region ${var.aws_region} --name ${aws_eks_cluster.medibook.name}"
}

output "ecr_repository_url" {
  description = "URL of ECR repository"
  value       = aws_ecr_repository.medibook.repository_url
}

