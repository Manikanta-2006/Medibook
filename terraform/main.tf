# ==============================================================================
# MediBook - Terraform Infrastructure Configuration (EKS + VPC)
# ==============================================================================

terraform {
  required_version = ">= 1.3.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

# ==========================================
# 1. VPC Networking Infrastructure
# ==========================================

# Dedicated VPC
resource "aws_vpc" "eks_vpc" {
  cidr_block           = var.vpc_cidr
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name        = "${var.cluster_name}-vpc"
    Project     = "MediBook"
    Environment = "Production"
    "kubernetes.io/cluster/${var.cluster_name}" = "shared"
  }
}

# Internet Gateway for Public Subnets
resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.eks_vpc.id

  tags = {
    Name        = "${var.cluster_name}-igw"
    Project     = "MediBook"
    Environment = "Production"
  }
}

# Public Subnets (across Availability Zones)
resource "aws_subnet" "public" {
  count                   = length(var.availability_zones)
  vpc_id                  = aws_vpc.eks_vpc.id
  cidr_block              = "10.0.${count.index + 1}.0/24"
  availability_zone       = var.availability_zones[count.index]
  map_public_ip_on_launch = true

  tags = {
    Name        = "${var.cluster_name}-public-${count.index + 1}"
    Project     = "MediBook"
    Environment = "Production"
    "kubernetes.io/cluster/${var.cluster_name}" = "shared"
    "kubernetes.io/role/elb"                    = "1"
  }
}

# Private Subnets (across Availability Zones)
resource "aws_subnet" "private" {
  count             = length(var.availability_zones)
  vpc_id            = aws_vpc.eks_vpc.id
  cidr_block        = "10.0.${count.index + 10}.0/24"
  availability_zone = var.availability_zones[count.index]

  tags = {
    Name        = "${var.cluster_name}-private-${count.index + 1}"
    Project     = "MediBook"
    Environment = "Production"
    "kubernetes.io/cluster/${var.cluster_name}" = "shared"
    "kubernetes.io/role/internal-elb"           = "1"
  }
}

# Elastic IP for NAT Gateway
resource "aws_eip" "nat_eip" {
  domain     = "vpc"
  depends_on = [aws_internet_gateway.igw]

  tags = {
    Name        = "${var.cluster_name}-nat-eip"
    Project     = "MediBook"
    Environment = "Production"
  }
}

# NAT Gateway for Private Subnets (placed inside Public Subnet 1)
resource "aws_nat_gateway" "nat" {
  allocation_id = aws_eip.nat_eip.id
  subnet_id     = aws_subnet.public[0].id
  depends_on    = [aws_internet_gateway.igw]

  tags = {
    Name        = "${var.cluster_name}-nat"
    Project     = "MediBook"
    Environment = "Production"
  }
}

# Public Route Table
resource "aws_route_table" "public_rt" {
  vpc_id = aws_vpc.eks_vpc.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.igw.id
  }

  tags = {
    Name        = "${var.cluster_name}-public-rt"
    Project     = "MediBook"
    Environment = "Production"
  }
}

# Private Route Table
resource "aws_route_table" "private_rt" {
  vpc_id = aws_vpc.eks_vpc.id

  route {
    cidr_block     = "0.0.0.0/0"
    nat_gateway_id = aws_nat_gateway.nat.id
  }

  tags = {
    Name        = "${var.cluster_name}-private-rt"
    Project     = "MediBook"
    Environment = "Production"
  }
}

# Public Subnets Associations
resource "aws_route_table_association" "public_association" {
  count          = length(var.availability_zones)
  subnet_id      = aws_subnet.public[count.index].id
  route_table_id = aws_route_table.public_rt.id
}

# Private Subnets Associations
resource "aws_route_table_association" "private_association" {
  count          = length(var.availability_zones)
  subnet_id      = aws_subnet.private[count.index].id
  route_table_id = aws_route_table.private_rt.id
}

# ==========================================
# 2. IAM Security Roles for EKS
# ==========================================

# Control Plane IAM Role
resource "aws_iam_role" "eks_cluster_role" {
  name = "${var.cluster_name}-cluster-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "eks.amazonaws.com"
        }
      }
    ]
  })

  tags = {
    Project     = "MediBook"
    Environment = "Production"
  }
}

# Attach cluster policies to role
resource "aws_iam_role_policy_attachment" "eks_cluster_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSClusterPolicy"
  role       = aws_iam_role.eks_cluster_role.name
}

resource "aws_iam_role_policy_attachment" "eks_vpc_resource_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSVPCResourceController"
  role       = aws_iam_role.eks_cluster_role.name
}

# Worker Node Group IAM Role
resource "aws_iam_role" "eks_node_role" {
  name = "${var.cluster_name}-node-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ec2.amazonaws.com"
        }
      }
    ]
  })

  tags = {
    Project     = "MediBook"
    Environment = "Production"
  }
}

# Attach worker policies to role
resource "aws_iam_role_policy_attachment" "eks_worker_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSWorkerNodePolicy"
  role       = aws_iam_role.eks_node_role.name
}

resource "aws_iam_role_policy_attachment" "eks_cni_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKS_CNI_Policy"
  role       = aws_iam_role.eks_node_role.name
}

resource "aws_iam_role_policy_attachment" "eks_registry_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryReadOnly"
  role       = aws_iam_role.eks_node_role.name
}

# ==========================================
# 3. AWS EKS Control Plane
# ==========================================

resource "aws_eks_cluster" "medibook" {
  name     = var.cluster_name
  role_arn = aws_iam_role.eks_cluster_role.arn
  version  = var.eks_version

  vpc_config {
    subnet_ids = concat(aws_subnet.public[*].id, aws_subnet.private[*].id)
  }

  depends_on = [
    aws_iam_role_policy_attachment.eks_cluster_policy,
    aws_iam_role_policy_attachment.eks_vpc_resource_policy
  ]

  tags = {
    Project     = "MediBook"
    Environment = "Production"
  }
}

# ==========================================
# 4. Managed Node Group (Worker Nodes)
# ==========================================

resource "aws_eks_node_group" "workers" {
  cluster_name    = aws_eks_cluster.medibook.name
  node_group_name = "medibook-workers"
  node_role_arn   = aws_iam_role.eks_node_role.arn
  subnet_ids      = aws_subnet.private[*].id # Workers should live in private subnets

  scaling_config {
    desired_size = var.node_desired_capacity
    min_size     = var.node_min_size
    max_size     = var.node_max_size
  }

  disk_size      = var.node_volume_size
  instance_types = [var.worker_instance_type]

  depends_on = [
    aws_iam_role_policy_attachment.eks_worker_policy,
    aws_iam_role_policy_attachment.eks_cni_policy,
    aws_iam_role_policy_attachment.eks_registry_policy
  ]

  tags = {
    Project     = "MediBook"
    Environment = "Production"
    Role        = "worker"
  }
}

# ==========================================
# 5. AWS ECR Repository
# ==========================================
resource "aws_ecr_repository" "medibook" {
  name                 = "medibook-app"
  image_tag_mutability = "MUTABLE"
  force_delete         = true

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = {
    Project     = "MediBook"
    Environment = "Production"
  }
}

# ==========================================
# 6. EKS OIDC, IRSA & EBS CSI Addon
# ==========================================

# 1. Fetch TLS Certificate for OIDC issuer
data "tls_certificate" "eks" {
  url = aws_eks_cluster.medibook.identity[0].oidc[0].issuer
}

# 2. Create IAM OIDC Provider for EKS
resource "aws_iam_openid_connect_provider" "eks" {
  client_id_list  = ["sts.amazonaws.com"]
  thumbprint_list = [data.tls_certificate.eks.certificates[0].sha1_fingerprint]
  url             = aws_eks_cluster.medibook.identity[0].oidc[0].issuer
}

# 3. Assume Role Policy for EBS CSI Service Account
data "aws_iam_policy_document" "ebs_csi_assume_role_policy" {
  statement {
    actions = ["sts:AssumeRoleWithWebIdentity"]
    effect  = "Allow"

    condition {
      test     = "StringEquals"
      variable = "${replace(aws_iam_openid_connect_provider.eks.url, "https://", "")}:sub"
      values   = ["system:serviceaccount:kube-system:ebs-csi-controller-sa"]
    }

    condition {
      test     = "StringEquals"
      variable = "${replace(aws_iam_openid_connect_provider.eks.url, "https://", "")}:aud"
      values   = ["sts.amazonaws.com"]
    }

    principals {
      identifiers = [aws_iam_openid_connect_provider.eks.arn]
      type        = "Federated"
    }
  }
}

# 4. IAM Role for EBS CSI Controller
resource "aws_iam_role" "ebs_csi_role" {
  assume_role_policy = data.aws_iam_policy_document.ebs_csi_assume_role_policy.json
  name               = "medibook-eks-ebs-csi-role"
}

# 5. Attach EBS CSI Driver Policy to Role
resource "aws_iam_role_policy_attachment" "ebs_csi_policy_attach" {
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonEBSCSIDriverPolicy"
  role       = aws_iam_role.ebs_csi_role.name
}

# 6. Install EKS Addon for EBS CSI Driver bound to the IRSA role
resource "aws_eks_addon" "ebs_csi" {
  cluster_name             = aws_eks_cluster.medibook.name
  addon_name               = "aws-ebs-csi-driver"
  service_account_role_arn = aws_iam_role.ebs_csi_role.arn

  depends_on = [
    aws_iam_role_policy_attachment.ebs_csi_policy_attach,
    aws_eks_node_group.workers
  ]
}



