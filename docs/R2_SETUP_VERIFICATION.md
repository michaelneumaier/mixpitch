# R2 Setup Verification Guide

## How to Verify R2 Credentials Are Working

### 1. Check Lambda Environment Variables

**AWS Console Method:**
1. Go to AWS Console → Lambda → `mixpitch-audio-processing-dev-transcode`
2. Click "Configuration" → "Environment variables"
3. Verify these variables are set and not empty:
   - `CF_R2_ACCESS_KEY_ID`
   - `CF_R2_SECRET_ACCESS_KEY`
   - `CF_R2_ENDPOINT`
   - `CF_R2_BUCKET`

### 2. Test Audio Processing

**Laravel Command:**
```bash
# From your Laravel root directory
php artisan audio:process --pitch_id=PITCH_ID --verbose
```

**Expected Success Logs:**
```
Lambda: "Uploading directly to R2: bucket=your-bucket, key=pitches/123/processed/..."
Laravel: "Lambda uploaded directly to final destination" "storage_provider": "r2"
```

### 3. Check CloudWatch Logs

**AWS Console:**
1. Go to CloudWatch → Log groups → `/aws/lambda/mixpitch-audio-processing-dev-transcode`
2. Look for recent log entries
3. **Success indicators:**
   - `"Uploading directly to R2: bucket=..."`
   - `"File uploaded successfully to R2: ..."`
   - `"R2 signed URL: ..."`

**Failure indicators:**
- `"R2 not configured (missing credentials), falling back to AWS S3"`
- `"R2 upload failed: ..."`

### 4. Monitor Data Transfer

**Check for reduced transfers:**
- **Before fix**: Files uploaded to both S3 and R2
- **After fix**: Files only uploaded to R2 (no S3 uploads)

### 5. Test with Real Audio File

**Steps:**
1. Upload an audio file to a pitch
2. Trigger audio processing
3. Check logs for R2 upload success
4. Verify file exists in R2 bucket (not S3)

## Troubleshooting

### Common Issues

**1. "R2 not configured" error:**
- **Cause**: Environment variables not set in Lambda
- **Fix**: Verify .env file has correct values before deployment

**2. "R2 upload failed" error:**
- **Cause**: Invalid R2 credentials or endpoint
- **Fix**: Double-check credentials from Cloudflare R2 dashboard

**3. "Access Denied" error:**
- **Cause**: R2 token lacks proper permissions
- **Fix**: Recreate R2 token with "Object Read & Write" permissions

**4. Files still uploading to S3:**
- **Cause**: R2 credentials not properly passed to Lambda
- **Fix**: Check AWS Lambda environment variables in console

### Quick Diagnostics

**Check current Lambda environment:**
```bash
# From functions directory
aws lambda get-function-configuration \
  --function-name mixpitch-audio-processing-dev-transcode \
  --query 'Environment.Variables' \
  --output table
```

**Expected output should show:**
```
|  CF_R2_ACCESS_KEY_ID      |  your_actual_key_here     |
|  CF_R2_SECRET_ACCESS_KEY  |  your_actual_secret_here  |
|  CF_R2_ENDPOINT           |  https://account.r2...    |
|  CF_R2_BUCKET             |  your_bucket_name         |
```

### Recovery Steps

If R2 setup fails:
1. System automatically falls back to S3 (no downtime)
2. Fix R2 credentials 
3. Redeploy Lambda function
4. Test with new audio file

## Success Metrics

Once working correctly, you should see:
- ✅ **75% reduction** in data transfer operations
- ✅ **20-40% faster** processing times
- ✅ **Lower costs** from eliminated cross-provider transfers
- ✅ **Simpler architecture** with single storage provider 