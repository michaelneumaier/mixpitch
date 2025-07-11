# Adding IAM Permissions for Lambda Deployment

## For AWS Administrator

Someone with AWS administrative privileges needs to run these commands to enable automated Lambda deployment for user `mbam1-dev`.

## Step 1: Create the IAM Policy

```bash
# Navigate to the functions directory
cd /path/to/mixpitch/functions

# Create the policy (requires admin privileges)
aws iam create-policy \
  --policy-name MixPitchLambdaDeployment \
  --policy-document file://iam-policy.json \
  --description "Lambda deployment permissions for MixPitch audio processing"
```

## Step 2: Attach Policy to User

```bash
# Attach the policy to the mbam1-dev user
aws iam attach-user-policy \
  --user-name mbam1-dev \
  --policy-arn arn:aws:iam::881533634640:policy/MixPitchLambdaDeployment
```

## Step 3: Verify Permissions

```bash
# List attached policies for the user
aws iam list-attached-user-policies --user-name mbam1-dev
```

Expected output should include:
```json
{
    "AttachedPolicies": [
        {
            "PolicyName": "MixPitchLambdaDeployment",
            "PolicyArn": "arn:aws:iam::881533634640:policy/MixPitchLambdaDeployment"
        }
    ]
}
```

## Alternative: Use AWS Console

If you prefer using the AWS Console:

1. **Go to IAM Console** → Users → `mbam1-dev`

2. **Permissions tab** → Add permissions → Attach policies directly

3. **Create policy** → JSON tab → Paste the content from `iam-policy.json`

4. **Name the policy**: `MixPitchLambdaDeployment`

5. **Attach the policy** to user `mbam1-dev`

## What This Policy Allows

The policy grants minimal required permissions for:

- ✅ **CloudFormation**: Create/update/delete stacks for serverless deployment
- ✅ **Lambda**: Create/update/delete functions and manage configuration
- ✅ **API Gateway**: Create/update REST APIs and deployments
- ✅ **IAM**: Create roles for Lambda functions (scoped to specific prefixes)
- ✅ **CloudWatch Logs**: Create log groups and streams
- ✅ **S3**: Read/write access to the mixpitch-dev bucket

## After Permissions Are Added

Once permissions are applied, the developer can run:

```bash
# Deploy the audio processing functions
./deploy.sh --stage dev --verbose

# Or manually with serverless
serverless deploy --stage dev --region us-east-2
```

## Security Notes

- Permissions are scoped to specific resource patterns (mixpitch-audio-processing-*)
- No broad administrative access is granted
- Policy follows AWS least-privilege principle
- All actions are limited to specific AWS account (881533634640)

## Verification Commands

After applying permissions, test with:

```bash
# Should now work without errors
aws iam list-attached-user-policies --user-name mbam1-dev
aws cloudformation describe-stacks --query 'Stacks[?contains(StackName, `mixpitch`)].StackName'
aws lambda list-functions --query 'Functions[?contains(FunctionName, `mixpitch`)].FunctionName'
``` 