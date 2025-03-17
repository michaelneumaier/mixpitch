# MixPitch S3 and FFmpeg Setup Guide

This guide explains how to set up Amazon S3 for file storage and external FFmpeg processing for MixPitch.

## Changes Made to Application

The following changes have been made to migrate the application to use S3 for file storage:

1. **Environment Configuration**: Updated `.env` file to set the default filesystem to S3 and added AWS credentials.
2. **Livewire Configuration**: Modified the Livewire configuration to use S3 for temporary file uploads.
3. **PitchFileController**: Updated to store files in S3 instead of local storage.
4. **PitchFile Model**: Modified to retrieve URLs and file sizes from S3.
5. **GenerateAudioWaveform Job**: Completely rewritten to use an external service for FFmpeg processing.
6. **File Templates**: Updated to use S3 URLs for downloading and playing audio files.

## Amazon S3 Setup

### 1. Create an AWS Account
If you don't already have an AWS account, sign up at [https://aws.amazon.com/](https://aws.amazon.com/).

### 2. Create an S3 Bucket
1. Sign in to the AWS Management Console and open the S3 console
2. Choose "Create bucket"
3. Name your bucket (e.g., `mixpitch-files`)
4. Select your preferred region
5. Configure options (recommended: enable versioning)
6. Set permissions (typically block all public access for private files)
7. Review and create bucket

### 3. Create IAM User for API Access
1. Go to the IAM console in AWS
2. Create a new user with programmatic access
3. Attach the `AmazonS3FullAccess` policy (or create a custom policy with limited permissions)
4. Save the Access Key ID and Secret Access Key provided

### 4. Update Environment Variables
Update your `.env` file with the following settings:
```
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key_id
AWS_SECRET_ACCESS_KEY=your_secret_access_key
AWS_DEFAULT_REGION=your_region
AWS_BUCKET=your_bucket_name
AWS_URL=https://your-bucket-name.s3.amazonaws.com
```

## CORS Configuration for S3 Bucket

For audio streaming and direct file access to work properly, you need to configure CORS on your S3 bucket:

1. In the S3 console, select your bucket
2. Go to "Permissions" tab
3. Scroll down to "Cross-origin resource sharing (CORS)"
4. Add the following configuration:

```json
[
    {
        "AllowedHeaders": [
            "*"
        ],
        "AllowedMethods": [
            "GET",
            "HEAD",
            "PUT",
            "POST"
        ],
        "AllowedOrigins": [
            "https://your-website-domain.com"
        ],
        "ExposeHeaders": [
            "ETag",
            "Content-Length",
            "Content-Type"
        ],
        "MaxAgeSeconds": 3600
    }
]
```

Replace `https://your-website-domain.com` with your actual domain.

## FFmpeg Processing Options

Since the application is configured to not require a local FFmpeg installation, you can use one of the following options for audio processing:

### Option 1: AWS Lambda with FFmpeg Layer

1. Create a Lambda function in AWS
2. Add the FFmpeg layer to your Lambda function
   - You can use a pre-built layer from the AWS Serverless Application Repository
   - Or create your own using the [FFmpeg Lambda Layer](https://github.com/serverlesspub/ffmpeg-aws-lambda-layer) project
3. Set up an API Gateway to trigger the Lambda function
4. Update the `processAudioWithExternalService` method in the `GenerateAudioWaveform` job to call your Lambda function's API endpoint

Example Lambda function code:
```javascript
const AWS = require('aws-sdk');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

exports.handler = async (event) => {
    const s3 = new AWS.S3();
    const fileUrl = event.fileUrl;
    const s3BucketName = fileUrl.split('/')[2].split('.')[0];
    const s3Key = fileUrl.split('/').slice(3).join('/');
    
    // Download file from S3
    const localPath = `/tmp/${path.basename(s3Key)}`;
    await s3.getObject({
        Bucket: s3BucketName,
        Key: s3Key
    }).promise().then(data => {
        fs.writeFileSync(localPath, data.Body);
    });
    
    // Process the file with FFmpeg
    const tempFile = `/tmp/audio_data_${Date.now()}.dat`;
    execSync(`ffmpeg -i ${localPath} -ac 1 -filter:a aresample=8000 -map 0:a -c:a pcm_s16le -f data ${tempFile}`);
    
    // Get duration
    const ffmpegInfo = execSync(`ffmpeg -i ${localPath} 2>&1`).toString();
    let duration = 0;
    const durationMatch = ffmpegInfo.match(/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2}\.[0-9]+)/);
    if (durationMatch) {
        const hours = parseInt(durationMatch[1]);
        const minutes = parseInt(durationMatch[2]);
        const seconds = parseFloat(durationMatch[3]);
        duration = hours * 3600 + minutes * 60 + seconds;
    }
    
    // Process the data file to generate peaks
    const rawData = fs.readFileSync(tempFile);
    const numPeaks = 200;
    const peaks = generatePeaks(rawData, numPeaks);
    
    // Clean up
    fs.unlinkSync(localPath);
    fs.unlinkSync(tempFile);
    
    return {
        statusCode: 200,
        body: JSON.stringify({
            duration: duration,
            peaks: peaks
        })
    };
};

function generatePeaks(rawData, numPeaks) {
    // Implementation of peak generation
    // ... (implementation details)
}
```

### Option 2: Third-Party Audio Processing API

Several services offer audio processing capabilities:

1. [AudioMass](https://audiomass.co/) - Offers audio processing capabilities and can be self-hosted
2. [Dolby.io](https://dolby.io/) - Media APIs including audio processing
3. [Cloudinary](https://cloudinary.com/) - Supports audio file processing

Update the `processAudioWithExternalService` method to call your chosen service's API.

### Option 3: Self-Hosted API

If you prefer, you can create a simple API service with FFmpeg installed:

1. Set up a small VPS with FFmpeg installed
2. Create a simple API endpoint using Express.js, Django, Laravel, etc.
3. Have the endpoint accept audio file URLs, process them with FFmpeg, and return the results
4. Update the `processAudioWithExternalService` method to call your API

## Integration Testing

After setting up your chosen solution:

1. Deploy the application with the updated environment variables
2. Upload a test audio file
3. Check the job logs to ensure processing is working correctly
4. Verify that the waveform is displayed correctly in the UI

## Troubleshooting

- **File Upload Issues**: Check AWS IAM permissions and S3 bucket CORS settings
- **Processing Errors**: Verify API keys/endpoints and Lambda function configurations
- **Waveform Display Issues**: Inspect browser console for JavaScript errors 