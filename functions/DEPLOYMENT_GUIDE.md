# Manual Deployment Guide for Audio Transcoding Lambda

## Overview
This guide is for deploying the audio transcoding Lambda function when you have AWS admin permissions or are working with someone who does.

## Prerequisites
- AWS CLI configured with admin permissions
- Node.js 18+ installed
- Serverless framework installed globally (`npm install -g serverless`)

## Step-by-Step Deployment

### 1. Set Environment Variables
```bash
export AWS_BUCKET=mixpitch-dev
export AWS_ACCOUNT_ID=881533634640
export AWS_REGION=us-east-2
```

### 2. Install Dependencies
```bash
cd functions
npm install
```

### 3. Deploy with Serverless
```bash
# Deploy to dev environment
serverless deploy --stage dev --region us-east-2

# Or use the deploy script
./deploy.sh --stage dev --verbose
```

### 4. Expected Output
After successful deployment, you should see:
```
Service Information
service: mixpitch-audio-processing
stage: dev
region: us-east-2
stack: mixpitch-audio-processing-dev

endpoints:
  POST - https://xxxxxxxxxx.execute-api.us-east-2.amazonaws.com/dev/waveform
  POST - https://xxxxxxxxxx.execute-api.us-east-2.amazonaws.com/dev/transcode

functions:
  waveform: mixpitch-audio-processing-dev-waveform
  transcode: mixpitch-audio-processing-dev-transcode
```

### 5. Update Laravel Configuration
Add to your Laravel `.env` file:
```env
AWS_LAMBDA_AUDIO_PROCESSOR_URL=https://xxxxxxxxxx.execute-api.us-east-2.amazonaws.com/dev
```

### 6. Test the Deployment
```bash
# Test waveform endpoint
curl -X POST https://xxxxxxxxxx.execute-api.us-east-2.amazonaws.com/dev/waveform \\
  -H "Content-Type: application/json" \\
  -d '{"file_url":"https://mixpitch-dev.s3.us-east-2.amazonaws.com/test.mp3","peaks_count":200}'

# Test transcode endpoint
curl -X POST https://xxxxxxxxxx.execute-api.us-east-2.amazonaws.com/dev/transcode \\
  -H "Content-Type: application/json" \\
  -d '{"file_url":"https://mixpitch-dev.s3.us-east-2.amazonaws.com/test.mp3","target_format":"mp3","apply_watermark":true}'
```

## Alternative: Add to Existing Stack

If you already have Lambda functions deployed, you can add the transcode function to your existing stack:

### 1. Find Your Existing Stack
```bash
aws cloudformation list-stacks --query 'StackSummaries[?contains(StackName, `audio`) || contains(StackName, `waveform`)].{Name:StackName,Status:StackStatus}' --output table
```

### 2. Update Existing serverless.yml
Add the transcode function to your existing serverless configuration and redeploy.

### 3. Deploy Update
```bash
serverless deploy --stage dev --region us-east-2
```

## Troubleshooting

### Permission Errors
If you get permission errors, ensure the IAM user has these policies:
- CloudFormationFullAccess
- AWSLambdaFullAccess
- AmazonAPIGatewayAdministrator
- AmazonS3FullAccess

### FFmpeg Layer
The function requires an FFmpeg layer. If deployment fails:
1. Check if the layer ARN in serverless.yml is correct
2. Create your own FFmpeg layer if needed
3. Update the layer ARN in the configuration

### Memory/Timeout Issues
If processing fails:
1. Increase memory allocation in serverless.yml
2. Increase timeout settings
3. Check file size limits

## Manual File Upload (If Needed)

If serverless deployment fails, you can manually upload:

### 1. Create ZIP Package
```bash
# Install dependencies
pip install -r requirements.txt -t .

# Create package
zip -r function.zip .
```

### 2. Upload via AWS Console
1. Go to Lambda console
2. Create new function
3. Upload ZIP package
4. Configure API Gateway trigger
5. Set environment variables

### 3. Configure API Gateway
1. Create new API Gateway
2. Add POST method
3. Configure Lambda proxy integration
4. Deploy API

## Final Steps
1. Test both endpoints with real audio files
2. Monitor CloudWatch logs for errors
3. Update Laravel configuration
4. Set up monitoring and alerts

## Support
If you encounter issues during deployment, check:
1. CloudWatch logs for detailed error messages
2. AWS CloudFormation console for stack status
3. Lambda function configuration
4. API Gateway configuration 