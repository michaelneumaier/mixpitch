# IAM Permissions Setup for MixPitch Lambda Deployment

## 📋 Overview

To enable automated Lambda deployment for the MixPitch audio processing system, user `mbam1-dev` needs additional IAM permissions. This directory contains all the necessary files and scripts.

## 📁 Files Created

### 🔧 Core Permission Files
- **`iam-policy.json`** - Complete IAM policy with minimal required permissions
- **`apply-permissions.sh`** - Automated script to apply all permissions (admin only)
- **`ADD_PERMISSIONS.md`** - Manual step-by-step instructions

### 📦 Deployment Files (Already Ready)
- **`serverless.yml`** - Serverless framework configuration
- **`deploy.sh`** - Automated deployment script
- **`transcode_audio.py`** - Lambda function code
- **`requirements.txt`** - Python dependencies
- **`package.json`** - Node.js dependencies

## 🚀 Quick Setup (Recommended)

### For AWS Administrator:

1. **Run the automated script:**
   ```bash
   cd /path/to/mixpitch/functions
   ./apply-permissions.sh
   ```

2. **That's it!** The script will:
   - ✅ Create the IAM policy
   - ✅ Attach it to user `mbam1-dev`
   - ✅ Verify permissions
   - ✅ Show next steps

### For Developer (After Permissions Applied):

1. **Test permissions:**
   ```bash
   aws cloudformation describe-stacks --query 'Stacks[?contains(StackName, `mixpitch`)].StackName'
   ```

2. **Deploy the Lambda functions:**
   ```bash
   ./deploy.sh --stage dev --verbose
   ```

3. **Update Laravel configuration** with the new API Gateway URL

## 🔍 What Permissions Are Granted

The IAM policy provides **minimal required access** for:

| Service | Permissions | Scope |
|---------|-------------|-------|
| **CloudFormation** | Create/update/delete stacks | `mixpitch-audio-processing-*` |
| **Lambda** | Create/update functions | `mixpitch-audio-processing-*` |
| **API Gateway** | Create/manage REST APIs | All (required for Lambda integration) |
| **IAM** | Create Lambda execution roles | `mixpitch-audio-processing-*` |
| **CloudWatch Logs** | Create log groups/streams | `/aws/lambda/mixpitch-audio-processing-*` |
| **S3** | Read/write objects | `mixpitch-dev` bucket only |

## 🛡️ Security Notes

- ✅ **Least Privilege**: Only necessary permissions granted
- ✅ **Resource Scoped**: Limited to specific resource patterns
- ✅ **Account Bound**: Restricted to account `881533634640`
- ✅ **No Admin Access**: Cannot modify other users/policies

## 📞 Manual Alternative

If you prefer using the AWS Console:

1. **Go to IAM Console** → Users → `mbam1-dev`
2. **Add permissions** → Attach policies directly
3. **Create policy** → JSON tab → Copy from `iam-policy.json`
4. **Name**: `MixPitchLambdaDeployment`
5. **Attach** to user

## ✅ Verification

After applying permissions, these commands should work:

```bash
# List attached policies (should show MixPitchLambdaDeployment)
aws iam list-attached-user-policies --user-name mbam1-dev

# Deploy the functions
./deploy.sh --stage dev --verbose

# Check deployed functions
aws lambda list-functions --query 'Functions[?contains(FunctionName, `mixpitch`)].FunctionName'
```

## 🔄 After Deployment

Once deployed successfully:

1. **Update Laravel `.env`:**
   ```env
   AWS_LAMBDA_AUDIO_PROCESSOR_URL=https://your-new-api-gateway-url/dev
   ```

2. **Test the integration:**
   ```bash
   php artisan audio:process --dry-run --all
   ```

3. **Monitor CloudWatch logs** for any issues

## 🆘 Troubleshooting

### Permission Denied Errors
- Ensure admin ran `apply-permissions.sh` successfully
- Check user has `MixPitchLambdaDeployment` policy attached

### Deployment Failures
- Verify all environment variables are set (`AWS_BUCKET`, `AWS_ACCOUNT_ID`)
- Check FFmpeg layer configuration in `serverless.yml`
- Review CloudWatch logs for detailed error messages

### Lambda Function Errors
- Verify S3 bucket permissions
- Check file URL encoding
- Monitor function timeout settings

## 📞 Support

If you encounter issues:

1. **Check CloudWatch logs** for detailed error messages
2. **Verify IAM permissions** using the verification commands above
3. **Review deployment logs** with `--verbose` flag
4. **Test with small audio files** first

---

**Ready to deploy?** Have an AWS admin run `./apply-permissions.sh` and then deploy with `./deploy.sh --stage dev --verbose`! 