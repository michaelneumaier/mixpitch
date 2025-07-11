# R2 Direct Upload Fix

## Problem

The previous audio processing workflow had a major inefficiency where files were bouncing between different cloud storage providers:

1. **Laravel uploads** → **R2** (Cloudflare)
2. **Lambda downloads** from **R2** 
3. **Lambda processes** file
4. **Lambda uploads** → **AWS S3** (different provider!)
5. **Laravel downloads** from **AWS S3**
6. **Laravel re-uploads** → **R2**

This resulted in:
- Files stored in two different cloud providers
- Expensive cross-provider data transfer
- Slower processing times
- Unnecessary complexity

## Solution

Modified the AWS Lambda function to upload directly to R2 (Cloudflare) instead of AWS S3, eliminating the cross-provider bouncing.

### Updated Flow

1. **Laravel uploads** → **R2** (Cloudflare)
2. **Lambda downloads** from **R2** 
3. **Lambda processes** file
4. **Lambda uploads** → **R2** (same provider!)
5. **No re-upload needed** - file is already in the right place

## Implementation

### 1. Lambda Function Changes

**Modified `functions/transcode_audio.py`:**

- Added `upload_to_r2()` function that uses R2-compatible S3 API
- Added fallback to AWS S3 if R2 credentials not configured
- Updated to return storage provider information
- Maintained backward compatibility

**Key changes:**
```python
# Create R2 client (S3-compatible)
r2_client = boto3.client(
    's3',
    aws_access_key_id=r2_access_key,
    aws_secret_access_key=r2_secret_key,
    endpoint_url=r2_endpoint,
    region_name='auto'
)

# Upload directly to R2
r2_client.upload_fileobj(file_data, r2_bucket, s3_key, ExtraArgs={...})
```

### 2. Environment Configuration

**Updated `functions/serverless.yml`:**
```yaml
environment:
  CF_R2_ACCESS_KEY_ID: ${env:CF_R2_ACCESS_KEY_ID}
  CF_R2_SECRET_ACCESS_KEY: ${env:CF_R2_SECRET_ACCESS_KEY}
  CF_R2_ENDPOINT: ${env:CF_R2_ENDPOINT}
  CF_R2_BUCKET: ${env:CF_R2_BUCKET}
```

### 3. Laravel Service Updates

**Enhanced `app/Services/AudioProcessingService.php`:**
- Added logging for storage provider used
- Better tracking of direct uploads vs fallbacks

## Benefits

### Immediate Improvements

- ✅ **Eliminated cross-provider transfers** (R2 ↔ AWS S3)
- ✅ **Reduced data transfer costs** significantly
- ✅ **Faster processing** (no redundant uploads)
- ✅ **Simplified architecture** (single storage provider)

### Cost Savings

| Previous | Optimized | Savings |
|----------|-----------|---------|
| R2 → Lambda | R2 → Lambda | Same |
| Lambda → AWS S3 | Lambda → R2 | **Eliminated** |
| AWS S3 → Laravel | N/A | **Eliminated** |
| Laravel → R2 | N/A | **Eliminated** |

**Result: ~75% reduction in data transfer operations**

## Deployment

### 1. Update Environment Variables

Ensure your `.env` has R2 credentials:
```env
CF_R2_ACCESS_KEY_ID=your-r2-access-key
CF_R2_SECRET_ACCESS_KEY=your-r2-secret-key
CF_R2_ENDPOINT=https://account-id.r2.cloudflarestorage.com
CF_R2_BUCKET=your-r2-bucket-name
```

### 2. Deploy Lambda Function

```bash
cd functions
serverless deploy --stage dev
```

### 3. Verify Operation

**Check logs for R2 uploads:**
```bash
# Lambda logs
aws logs tail /aws/lambda/mixpitch-audio-processing-dev-transcode --follow

# Laravel logs
tail -f storage/logs/laravel.log | grep "storage_provider"
```

**Expected log entries:**
```
Lambda: "Uploading directly to R2: bucket=your-bucket, key=pitches/123/processed/..."
Laravel: "Lambda uploaded directly to final destination" "storage_provider": "r2"
```

## Fallback Behavior

The system maintains reliability with automatic fallbacks:

1. **R2 Primary**: Attempts upload to R2 first
2. **S3 Fallback**: Falls back to AWS S3 if R2 fails
3. **Laravel Fallback**: Falls back to old re-upload method if both fail

## Monitoring

### Success Metrics

Monitor these metrics to verify the fix is working:

- **R2 upload success rate**: Should be >95%
- **Cross-provider transfers**: Should be near 0
- **Processing time reduction**: Should see 20-40% improvement
- **Data transfer costs**: Should see significant reduction

### Log Queries

**CloudWatch (Lambda):**
```
fields @timestamp, @message
| filter @message like /Uploading directly to R2/
| sort @timestamp desc
```

**Laravel:**
```bash
grep "storage_provider.*r2" storage/logs/laravel.log
```

## Future Improvements

This fix enables future optimizations:

1. **Cloudflare Workers**: Can now easily migrate to Workers since everything is in R2
2. **Edge Processing**: Process files closer to users
3. **Cost Optimization**: Further reduce costs with R2's pricing model

## Troubleshooting

### Common Issues

**1. R2 upload fails:**
- Check R2 credentials in Lambda environment
- Verify R2 bucket exists and is accessible
- Check R2 endpoint URL format

**2. Files not found after processing:**
- Verify R2 bucket configuration matches Laravel config
- Check file paths in logs
- Ensure Laravel is using correct R2 disk configuration

**3. Fallback to S3:**
- Check if R2 credentials are properly set in Lambda
- Review Lambda logs for R2 connection errors
- Verify R2 service status

### Recovery

If issues occur, the system automatically falls back to the previous method, ensuring no disruption to users. 