# AWS CLI Configuration & Terraform EKS Manual

This step-by-step manual guides you through connecting your local machine to AWS using the **AWS CLI**, installing **Terraform**, and deploying a highly available **Amazon EKS cluster** and network infrastructure (VPC, private/public subnets, NAT Gateway).

---

## 1. Connecting & Configuring the AWS CLI

The AWS Command Line Interface (CLI) is the unified tool to manage your AWS services.

### 1.1 Step 1: Create an IAM User in AWS
To allow your terminal to interface with AWS, you need programmatic access keys:
1. Log into your **AWS Management Console**.
2. Search for and navigate to **IAM** (Identity and Access Management).
3. In the left sidebar, click **Users** → **Create user**.
4. Configure user details:
   - **User name**: `medibook-admin`
   - Click **Next**.
5. Set permissions:
   - Choose **Attach policies directly**.
   - Search for and check:
     - `AdministratorAccess` *(Recommended for setup; provides sufficient permissions to build VPCs, subnets, EKS Control Planes, Managed Node Groups, and IAM roles)*.
   - Click **Next** → **Create user**.
6. Generate access keys:
   - Click on your newly created user (`medibook-admin`).
   - Navigate to the **Security credentials** tab.
   - Scroll down to **Access keys** and click **Create access key**.
   - Select **Command Line Interface (CLI)** as the use case.
   - Click **Next**, add a description tag (e.g. `Mac Terminal`), and click **Create access key**.
   - > [!IMPORTANT]
     > Copy the **Access Key ID** and the **Secret Access Key** immediately. You will not be able to view the Secret Access Key again after closing this screen.

---

### 1.2 Step 2: Install & Configure AWS CLI
On macOS, install the AWS CLI and configure it with your credentials:

1. Install via Homebrew:
   ```bash
   brew install awscli
   ```
2. Run the interactive configuration command:
   ```bash
   aws configure
   ```
3. Input your programmatic key credentials:
   ```text
   AWS Access Key ID [None]: <YOUR_ACCESS_KEY_ID>
   AWS Secret Access Key [None]: <YOUR_SECRET_ACCESS_KEY>
   Default region name [None]: ap-south-1
   Default output format [None]: json
   ```
4. Verify connection:
   ```bash
   aws sts get-caller-identity
   ```
   *Expected output: A JSON block containing your UserId, Account number, and the IAM User ARN (`arn:aws:iam::...:user/medibook-admin`).*

---

## 2. Setting Up Terraform

Terraform is a declarative Infrastructure-as-Code (IaC) tool.

### 2.1 Step 1: Install Terraform
On macOS, use Homebrew to install HashiCorp's Terraform CLI:
```bash
brew tap hashicorp/tap
brew install hashicorp/tap/terraform
```
Verify the installation:
```bash
terraform -version
```

### 2.2 Step 2: Initialize Terraform Directory
Move into the `terraform` directory under your project root:
```bash
cd terraform

# Initialize the working directory (downloads AWS provider plugins)
terraform init
```

### 2.3 Step 3: Run dry-run checks
Always run a dry-run to ensure your IAM permissions and configurations are correct without applying any changes:
```bash
terraform plan
```

### 2.4 Step 4: Apply Configuration
Deploy your infrastructure to AWS:
```bash
terraform apply -auto-approve
```
*Wait ~15 minutes for AWS to configure your subnets, NAT Gateways, EKS Control Plane, and worker node groups.*

---

## 3. Configuring Kubeconfig & Accessing EKS

Once Terraform completes, it will output the exact cluster connection commands. 

### 3.1 Authenticate Kubernetes CLI
Run the update command (provided in Terraform outputs) to pull the authentication details down into your local machine's `~/.kube/config`:
```bash
aws eks update-kubeconfig --region ap-south-1 --name medibook-cluster
```

### 3.2 Verify Cluster Nodes
You can now communicate directly with EKS using `kubectl`:
```bash
# Verify connection by listing nodes
kubectl get nodes
```
*Expected output: You should see 2 worker nodes in a `Ready` status.*

---

## 4. Terraform EKS Clean-Up

To prevent unnecessary hourly billing on AWS when you are finished testing or running experiments, wipe out all resources:

1. Clean up Kubernetes services (LoadBalancers and persistent volumes) first:
   ```bash
   kubectl delete namespace medibook
   ```
2. Destroy the physical network and cluster elements:
   ```bash
   cd terraform
   terraform destroy -auto-approve
   ```
