#!/bin/bash

# MixPitch Audio Processing Lambda Deployment Script
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
STAGE="dev"
REGION="us-east-2"
FORCE_DEPLOY=false
VERBOSE=false

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

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "Deploy MixPitch Audio Processing Lambda Functions"
    echo ""
    echo "Options:"
    echo "  -s, --stage STAGE      Deployment stage (dev/prod) [default: dev]"
    echo "  -r, --region REGION    AWS region [default: us-east-2]"
    echo "  -f, --force           Force deployment even if no changes"
    echo "  -v, --verbose         Verbose output"
    echo "  -h, --help            Show this help message"
    echo ""
    echo "Environment Variables (required):"
    echo "  AWS_BUCKET                S3 bucket name"
    echo "  AWS_ACCOUNT_ID           AWS Account ID"
    echo ""
    echo "Examples:"
    echo "  $0 --stage dev"
    echo "  $0 --stage prod --region us-west-2"
    echo "  $0 --force --verbose"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -s|--stage)
            STAGE="$2"
            shift 2
            ;;
        -r|--region)
            REGION="$2"
            shift 2
            ;;
        -f|--force)
            FORCE_DEPLOY=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Check prerequisites
print_status "Checking prerequisites..."

# Check if we're in the functions directory
if [[ ! -f "serverless.yml" ]]; then
    print_error "serverless.yml not found. Please run this script from the functions directory."
    exit 1
fi

# Check required tools
command -v serverless >/dev/null 2>&1 || {
    print_error "Serverless framework not found. Please install it:"
    echo "npm install -g serverless"
    exit 1
}

command -v aws >/dev/null 2>&1 || {
    print_error "AWS CLI not found. Please install and configure it."
    exit 1
}

command -v docker >/dev/null 2>&1 || {
    print_error "Docker not found. Docker is required for Python packaging."
    exit 1
}

# Check environment variables
if [[ -z "$AWS_BUCKET" ]]; then
    print_error "AWS_BUCKET environment variable is required"
    exit 1
fi

if [[ -z "$AWS_ACCOUNT_ID" ]]; then
    print_warning "AWS_ACCOUNT_ID not set. Attempting to detect..."
    AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text 2>/dev/null || echo "")
    if [[ -z "$AWS_ACCOUNT_ID" ]]; then
        print_error "Could not detect AWS Account ID. Please set AWS_ACCOUNT_ID environment variable."
        exit 1
    fi
    print_success "Detected AWS Account ID: $AWS_ACCOUNT_ID"
fi

# Check AWS credentials
print_status "Verifying AWS credentials..."
if ! aws sts get-caller-identity >/dev/null 2>&1; then
    print_error "AWS credentials not configured or invalid. Please run 'aws configure'."
    exit 1
fi

# Install dependencies if needed
if [[ ! -d "node_modules" ]]; then
    print_status "Installing Node.js dependencies..."
    npm install
fi

# Set deployment options
DEPLOY_OPTS="--stage $STAGE --region $REGION"
if [[ "$VERBOSE" == "true" ]]; then
    DEPLOY_OPTS="$DEPLOY_OPTS --verbose"
fi

if [[ "$FORCE_DEPLOY" == "true" ]]; then
    DEPLOY_OPTS="$DEPLOY_OPTS --force"
fi

print_status "Deployment Configuration:"
echo "  Stage: $STAGE"
echo "  Region: $REGION"
echo "  AWS Account: $AWS_ACCOUNT_ID"
echo "  S3 Bucket: $AWS_BUCKET"
echo "  Force Deploy: $FORCE_DEPLOY"
echo "  Verbose: $VERBOSE"
echo ""

# Deploy the functions
print_status "Deploying Lambda functions..."
if serverless deploy $DEPLOY_OPTS; then
    print_success "Deployment completed successfully!"
else
    print_error "Deployment failed!"
    exit 1
fi

# Get deployment info
print_status "Getting deployment information..."
API_URL=$(serverless info --stage $STAGE --region $REGION | grep "endpoints:" -A 10 | grep -E "(waveform|transcode)" | head -1 | awk '{print $3}' | sed 's|/[^/]*$||')

if [[ -n "$API_URL" ]]; then
    print_success "API Gateway URL: $API_URL"
    echo ""
    echo "Endpoints:"
    echo "  Waveform: $API_URL/waveform"
    echo "  Transcode: $API_URL/transcode"
    echo ""
    echo "Update your Laravel .env file:"
    echo "AWS_LAMBDA_AUDIO_PROCESSOR_URL=$API_URL"
else
    print_warning "Could not extract API Gateway URL. Check serverless info output manually."
fi

# Test deployment (optional)
read -p "Do you want to test the deployment? (y/N): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_status "Testing waveform function..."
    if serverless invoke -f waveform --stage $STAGE --region $REGION --data '{"body": "{\"file_url\": \"test\", \"peaks_count\": 10}"}' >/dev/null 2>&1; then
        print_success "Waveform function responding"
    else
        print_warning "Waveform function test failed (this is expected without a valid file URL)"
    fi
    
    print_status "Testing transcode function..."
    if serverless invoke -f transcode --stage $STAGE --region $REGION --data '{"body": "{\"file_url\": \"test\"}"}' >/dev/null 2>&1; then
        print_success "Transcode function responding"
    else
        print_warning "Transcode function test failed (this is expected without a valid file URL)"
    fi
fi

print_success "Deployment process completed!"
print_status "Next steps:"
echo "1. Update your Laravel .env with the API Gateway URL shown above"
echo "2. Test with real audio files"
echo "3. Monitor CloudWatch logs for any issues"
echo "4. Set up monitoring and alerts as needed" 