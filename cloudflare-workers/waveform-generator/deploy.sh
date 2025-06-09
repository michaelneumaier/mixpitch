#!/bin/bash

# Cloudflare Workers Waveform Generator Deployment Script

set -e

echo "🚀 Deploying MixPitch Waveform Generator to Cloudflare Workers..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default environment
ENVIRONMENT=${1:-production}

echo -e "${YELLOW}Environment: $ENVIRONMENT${NC}"

# Check if wrangler is installed
if ! command -v wrangler &> /dev/null; then
    echo -e "${RED}❌ Wrangler CLI is not installed. Please install it first:${NC}"
    echo "npm install -g wrangler"
    exit 1
fi

# Check if user is logged in to Cloudflare
if ! wrangler whoami &> /dev/null; then
    echo -e "${RED}❌ Not logged in to Cloudflare. Please login first:${NC}"
    echo "wrangler login"
    exit 1
fi

# Install dependencies
echo -e "${YELLOW}📦 Installing dependencies...${NC}"
npm install

# Build the project
echo -e "${YELLOW}🔨 Building project...${NC}"
npm run build

# Deploy based on environment
if [ "$ENVIRONMENT" = "staging" ]; then
    echo -e "${YELLOW}🚀 Deploying to staging...${NC}"
    wrangler deploy --env staging
elif [ "$ENVIRONMENT" = "development" ]; then
    echo -e "${YELLOW}🚀 Deploying to development...${NC}"
    wrangler deploy --env development
else
    echo -e "${YELLOW}🚀 Deploying to production...${NC}"
    wrangler deploy
fi

# Get the deployed URL
echo -e "${GREEN}✅ Deployment completed!${NC}"

# Test the deployment
echo -e "${YELLOW}🧪 Testing deployment...${NC}"
WORKER_URL=$(wrangler subdomain 2>/dev/null || echo "your-worker.your-subdomain.workers.dev")

echo -e "${GREEN}🎉 Waveform Generator deployed successfully!${NC}"
echo -e "${GREEN}Worker URL: https://$WORKER_URL${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Update your .env file with:"
echo "   CLOUDFLARE_WAVEFORM_WORKER_URL=https://$WORKER_URL"
echo "2. Test the worker with a sample audio file"
echo "3. Monitor the logs: wrangler tail"
echo ""
echo -e "${GREEN}🔗 Useful commands:${NC}"
echo "  View logs: wrangler tail"
echo "  View metrics: wrangler pages project list"
echo "  Local development: npm run dev" 