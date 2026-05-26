# ============================================
# MediBook - Terraform Variables
# ============================================

variable "aws_region" {
  description = "AWS region to deploy resources"
  type        = string
  default     = "ap-south-1"
}

variable "cluster_name" {
  description = "Name of the EKS cluster"
  type        = string
  default     = "medibook-cluster"
}

variable "eks_version" {
  description = "Kubernetes version for EKS Control Plane"
  type        = string
  default     = "1.30"
}

variable "vpc_cidr" {
  description = "CIDR block for the dedicated VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "Availability zones to spread private/public subnets"
  type        = list(string)
  default     = ["ap-south-1a", "ap-south-1b"]
}

variable "worker_instance_type" {
  description = "EC2 instance size for the worker node group"
  type        = string
  default     = "t3.small"
}

variable "node_desired_capacity" {
  description = "Desired number of running worker nodes"
  type        = number
  default     = 2
}

variable "node_min_size" {
  description = "Minimum size of the Auto-Scaling group"
  type        = number
  default     = 2
}

variable "node_max_size" {
  description = "Maximum size of the Auto-Scaling group"
  type        = number
  default     = 4
}

variable "node_volume_size" {
  description = "EBS Root disk volume size in GB per worker node"
  type        = number
  default     = 20
}

variable "node_volume_type" {
  description = "EBS disk volume type"
  type        = string
  default     = "gp3"
}
