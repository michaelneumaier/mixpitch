# MixPitch Audio Processing Lambda Functions

This directory contains AWS Lambda functions for audio processing in MixPitch, including waveform generation and audio transcoding with watermarking.

## Functions

### 1. Waveform Generation (`generate_waveform.py`)
- **Endpoint**: `/waveform`
- **Purpose**: Generate waveform visualization data from audio files
- **Input**: Audio file URL
- **Output**: JSON with duration and waveform peaks

### 2. Audio Transcoding (`transcode_audio.py`)
- **Endpoint**: `/transcode` 
- **Purpose**: Transcode audio files to MP3 and apply watermarking
- **Input**: Audio file URL, target format, watermark settings
- **Output**: JSON with processed file S3 URL

## Prerequisites

1. **AWS CLI** configured with appropriate permissions
2. **Node.js** (v16 or later) 
3. **Docker** (for Python packaging)
4. **Serverless Framework**

## Setup

### 1. Install Dependencies

```bash
# Navigate to functions directory
cd functions

# Install serverless framework and plugins
npm install

# Install Python requirements (handled automatically during deployment)
```

### 2. Configure Environment Variables

Create a `.env` file or set environment variables:

```bash
export AWS_BUCKET=your-s3-bucket-name
export AUDIO_PROCESSING_BUCKET=your-s3-bucket-name  # Optional: separate bucket for processed files
export AWS_REGION=us-east-2
export AWS_ACCOUNT_ID=your-aws-account-id
```

### 3. FFmpeg Layer

The Lambda functions require an FFmpeg layer. You need to either:

**Option A: Create your own FFmpeg layer**
```bash
# This creates a Lambda layer with FFmpeg binaries
aws lambda publish-layer-version \
  --layer-name ffmpeg \
  --description "FFmpeg binaries for Lambda" \
  --zip-file fileb://ffmpeg-layer.zip \
  --compatible-runtimes python3.9
```

**Option B: Use existing public layer**
Update `serverless.yml` with a public FFmpeg layer ARN:
```yaml
layers:
  - arn:aws:lambda:us-east-2:898466741470:layer:ffmpeg:1
```

## Deployment

### Development Deployment
```bash
# Deploy to development stage
npm run deploy-dev

# Or directly with serverless
serverless deploy --stage dev
```

### Production Deployment
```bash
# Deploy to production stage
npm run deploy-prod

# Or directly with serverless
serverless deploy --stage prod
```

### Manual Deployment Steps
```bash
# 1. Package the functions
serverless package

# 2. Deploy the package
serverless deploy --package .serverless

# 3. Get the API Gateway URLs
serverless info
```

## Testing

### Test Waveform Generation
```bash
# Invoke the waveform function
serverless invoke -f waveform --data '{
  "body": "{\"file_url\": \"https://your-bucket.s3.amazonaws.com/test-file.mp3\", \"peaks_count\": 200}"
}'
```

### Test Audio Transcoding
```bash
# Invoke the transcode function
serverless invoke -f transcode --data '{
  "body": "{\"file_url\": \"https://your-bucket.s3.amazonaws.com/test-file.mp3\", \"target_format\": \"mp3\", \"target_bitrate\": \"192k\", \"apply_watermark\": true, \"watermark_settings\": {\"frequency\": 1000, \"volume\": 0.1}}"
}'
```

### HTTP Testing
```bash
# Get your API Gateway URL from deployment output, then:
curl -X POST https://your-api-id.execute-api.us-east-2.amazonaws.com/dev/transcode \
  -H "Content-Type: application/json" \
  -d '{
    "file_url": "https://your-bucket.s3.amazonaws.com/test-file.mp3",
    "target_format": "mp3",
    "target_bitrate": "192k",
    "apply_watermark": true,
    "watermark_settings": {
      "frequency": 1000,
      "volume": 0.1,
      "pitch_id": "123",
      "project_id": "456"
    }
  }'
```

## Configuration

### Environment Variables
- `AWS_BUCKET`: S3 bucket for file storage
- `AUDIO_PROCESSING_BUCKET`: S3 bucket for processed files (optional)
- `FUNCTION_NAME`: Function identifier for logging

### Lambda Settings
- **Runtime**: Python 3.9
- **Memory**: 1024 MB
- **Timeout**: 300 seconds (5 minutes)
- **Layers**: FFmpeg layer required

## Architecture

```
API Gateway → Lambda Function → Download File → Process with FFmpeg → Upload to S3 → Return URL
```

### Processing Flow
1. **Request**: Receive audio file URL and processing parameters
2. **Download**: Download source file to Lambda temporary storage
3. **Process**: Use FFmpeg to transcode and apply watermarking
4. **Upload**: Upload processed file to S3
5. **Response**: Return S3 URL of processed file

## Monitoring

### CloudWatch Logs
```bash
# View real-time logs
npm run logs-transcode
npm run logs-waveform

# Or with serverless directly
serverless logs -f transcode -t
serverless logs -f waveform -t
```

### Metrics
Monitor these CloudWatch metrics:
- **Duration**: Processing time per request
- **Errors**: Failed processing attempts
- **Invocations**: Total number of requests
- **Throttles**: Rate limiting events

## Security

### IAM Permissions
Functions have minimal required permissions:
- S3 read/write access to specified buckets
- CloudWatch Logs write access

### S3 Access
- Source files: Read access
- Processed files: Write access
- Bucket isolation supported

## Cost Optimization

### Factors Affecting Cost
- **File Size**: Larger files take longer to process
- **Processing Complexity**: Watermarking adds processing time
- **Memory Usage**: 1GB memory allocation for FFmpeg

### Optimization Tips
- Use appropriate file size limits
- Consider batch processing for multiple files
- Monitor and adjust timeout settings
- Use S3 lifecycle policies for processed files

## Troubleshooting

### Common Issues

**1. FFmpeg Not Found**
```
Error: FFmpeg not available
```
Solution: Ensure FFmpeg layer is properly configured

**2. S3 Upload Failed**
```
Error: Failed to upload processed file to S3
```
Solution: Check S3 bucket permissions and environment variables

**3. Timeout Errors**
```
Error: Task timed out after 300.00 seconds
```
Solution: Increase timeout or optimize file size

**4. Memory Errors**
```
Error: Runtime exited with error: signal: killed
```
Solution: Increase memory allocation or reduce file size

### Debug Mode
Set environment variable for verbose logging:
```bash
export LAMBDA_DEBUG=true
```

## Integration with MixPitch

### Laravel Configuration
Update your Laravel `.env`:
```bash
AWS_LAMBDA_AUDIO_PROCESSOR_URL=https://your-api-id.execute-api.us-east-2.amazonaws.com/dev
AUDIO_PROCESSING_METHOD=lambda
AUDIO_USE_LAMBDA=true
```

### Usage in Code
The Laravel `AudioProcessingService` will automatically use the Lambda functions when configured.

## Deployment Checklist

- [ ] AWS CLI configured
- [ ] Environment variables set
- [ ] FFmpeg layer created/configured
- [ ] S3 bucket permissions verified
- [ ] Serverless framework installed
- [ ] Functions deployed successfully
- [ ] API Gateway endpoints accessible
- [ ] Test requests working
- [ ] Laravel integration configured
- [ ] Monitoring set up

## Support

For issues or questions:
1. Check CloudWatch logs for detailed error messages
2. Verify IAM permissions and S3 access
3. Test with small audio files first
4. Monitor Lambda metrics in CloudWatch 