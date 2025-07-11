#!/bin/bash

# Apply IAM Permissions for MixPitch Lambda Deployment
# This script must be run by someone with AWS administrative privileges

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
USER_NAME="mbam1-dev"
POLICY_NAME="MixPitchLambdaDeployment"
ACCOUNT_ID="881533634640"
POLICY_ARN="arn:aws:iam::${ACCOUNT_ID}:policy/${POLICY_NAME}"

echo -e "${BLUE}=== MixPitch Lambda Deployment Permissions Setup ===${NC}"
echo ""

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as admin
print_status "Checking AWS permissions..."
if ! aws iam get-user --user-name "$USER_NAME" >/dev/null 2>&1; then
    print_error "Cannot access user $USER_NAME. Please ensure you have AWS admin privileges."
    exit 1
fi

print_success "AWS admin privileges confirmed"

# Check if policy file exists
if [[ ! -f "iam-policy.json" ]]; then
    print_error "iam-policy.json not found. Please run this script from the functions directory."
    exit 1
fi

print_status "Creating IAM policy: $POLICY_NAME"

# Create policy (or update if exists)
if aws iam get-policy --policy-arn "$POLICY_ARN" >/dev/null 2>&1; then
    print_warning "Policy $POLICY_NAME already exists"
    
    # Get current policy version
    CURRENT_VERSION=$(aws iam get-policy --policy-arn "$POLICY_ARN" --query 'Policy.DefaultVersionId' --output text)
    
    # Create new version
    print_status "Creating new policy version..."
    NEW_VERSION=$(aws iam create-policy-version \
        --policy-arn "$POLICY_ARN" \
        --policy-document file://iam-policy.json \
        --set-as-default \
        --query 'PolicyVersion.VersionId' \
        --output text)
    
    print_success "Updated policy to version $NEW_VERSION"
    
    # Delete old version (keep only latest)
    if [[ "$CURRENT_VERSION" != "v1" ]]; then
        aws iam delete-policy-version --policy-arn "$POLICY_ARN" --version-id "$CURRENT_VERSION" || true
    fi
else
    # Create new policy
    aws iam create-policy \
        --policy-name "$POLICY_NAME" \
        --policy-document file://iam-policy.json \
        --description "Lambda deployment permissions for MixPitch audio processing"
    
    print_success "Created new policy: $POLICY_NAME"
fi

# Attach policy to user
print_status "Attaching policy to user: $USER_NAME"

if aws iam list-attached-user-policies --user-name "$USER_NAME" --query "AttachedPolicies[?PolicyName=='$POLICY_NAME']" --output text | grep -q "$POLICY_NAME"; then
    print_warning "Policy already attached to user $USER_NAME"
else
    aws iam attach-user-policy \
        --user-name "$USER_NAME" \
        --policy-arn "$POLICY_ARN"
    
    print_success "Attached policy to user $USER_NAME"
fi

# Verify permissions
print_status "Verifying permissions..."

echo ""
echo -e "${GREEN}=== CURRENT USER POLICIES ===${NC}"
aws iam list-attached-user-policies --user-name "$USER_NAME" --output table

echo ""
print_success "Permissions setup completed successfully!"

echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. User $USER_NAME can now run: ./deploy.sh --stage dev --verbose"
echo "2. Or use serverless directly: serverless deploy --stage dev --region us-east-2"
echo "3. Monitor deployment logs and test the endpoints"
echo ""

echo -e "${BLUE}Test Commands (for $USER_NAME):${NC}"
echo "# Test permissions"
echo "aws cloudformation describe-stacks --query 'Stacks[?contains(StackName, \`mixpitch\`)].StackName'"
echo ""
echo "# Deploy functions"
echo "./deploy.sh --stage dev --verbose"
echo ""

echo -e "${GREEN}✅ Setup Complete!${NC}" 