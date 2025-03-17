# AWS Integration for Audio Processing in MixPitch

This document provides a comprehensive overview of how AWS services are integrated into MixPitch for file storage and audio processing.

## Architecture Overview

MixPitch leverages two primary AWS services:

1. **Amazon S3** - For storing audio files, project images, and other media assets
2. **AWS Lambda** - For serverless audio processing, including waveform generation and duration extraction

The general workflow is:

1. Files are uploaded directly to S3 from the Laravel application
2. A job is dispatched to process audio files (generate waveforms)
3. The job calls a Lambda function that processes the audio
4. The Lambda function returns waveform data that is stored in the database
5. The UI displays the waveform for audio playback

## S3 Storage Integration

### Configuration

S3 storage is configured in the following files:

- `.env` - Contains AWS credentials and bucket information
- `config/filesystems.php` - Configures the S3 disk
- `config/services.php` - Contains AWS service configurations

Key environment variables:

```
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-2
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.region.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### File Upload Flow

1. **Direct Upload**: Files are uploaded directly to S3 using Laravel's storage facade:

```php
$path = $file->store('pitch_files/' . $pitch->id, 's3');
```

2. **URL Generation**: URLs for stored files are generated using:

```php
$url = Storage::disk('s3')->url($path);
```

3. **URL Encoding**: URLs are properly encoded to handle spaces and special characters:

```php
$encodedUrl = str_replace(' ', '%20', $url);
```

## Signed URL Implementation

MixPitch uses AWS S3 signed URLs for secure file access, preventing unauthorized access and direct linking to media files. This approach improves security by creating temporary URLs with limited validity.

### How Signed URLs Work

1. **Temporary Access**: URLs expire after a set time period (15 minutes to 1 hour)
2. **Authentication**: URLs contain authentication parameters generated using AWS credentials
3. **Non-Shareable**: After expiration, links cannot be used to access the files

### Implementation Details

#### Model Accessors

Files and images use accessor methods to generate signed URLs:

```php
// For media playback (15 minute expiration)
public function getFullFilePathAttribute()
{
    return Storage::disk('s3')->temporaryUrl(
        $this->file_path,
        now()->addMinutes(15)
    );
}

// For downloads (60 minute expiration)
public function getSignedUrlAttribute($expirationMinutes = 60)
{
    return Storage::disk('s3')->temporaryUrl(
        $this->file_path,
        now()->addMinutes($expirationMinutes),
        [
            'ResponseContentDisposition' => 'attachment; filename="' . $this->file_name . '"'
        ]
    );
}
```

#### Download Headers

For file downloads, the Content-Disposition header is added:

```php
'ResponseContentDisposition' => 'attachment; filename="' . $this->file_name . '"'
```

This ensures browsers handle the file as a download rather than trying to display it.

#### Expiration Times

Different expiration times are used based on the context:

- **Media Player Access**: 15 minutes
- **File Downloads**: 60 minutes
- **Project Images**: 1 hour (since they're used in UI)

#### Batch Downloads

For ZIP downloads containing multiple files, the system:

1. Generates individual signed URLs for each file
2. Downloads the files to a temporary server location
3. Creates a ZIP archive and serves it to the user
4. Cleans up temporary files automatically

### Security Benefits

1. **Prevents Hot-Linking**: External sites cannot embed your media
2. **Restricts Access**: Only authenticated users with valid sessions can obtain working URLs
3. **Limited Window**: Even if a URL is shared, it becomes useless after expiration
4. **Access Control**: Different URL expiration times can be set based on user roles or content sensitivity

### Implementation in Views

In Blade templates, use the appropriate accessor based on the context:

```php
<!-- For media playback -->
<audio src="{{ $file->fullFilePath }}"></audio>

<!-- For file downloads (route-based approach) -->
<a href="{{ route('download.pitch-file', $file->id) }}">Download</a>
```

### Secure Download Controller

To prevent issues with URL expiration and improve security, MixPitch implements a dedicated controller for file downloads:

```php
// Route definition
Route::get('/download/pitch-file/{id}', [FileDownloadController::class, 'downloadPitchFile'])
    ->name('download.pitch-file')
    ->middleware('auth');

// Controller method
public function downloadPitchFile($id)
{
    $file = PitchFile::findOrFail($id);
    // Authorization checks...
    
    // Generate a fresh signed URL
    $signedUrl = $this->generateSignedDownloadUrl($file);
    
    // Redirect to the signed URL
    return redirect()->away($signedUrl);
}
```

This approach has several advantages:

1. **Fresh URLs**: A new signed URL is generated at the exact moment of download
2. **Reduced Conflicts**: Avoids conflicts with media players that might use the same URL
3. **Better Security**: Performs authorization checks before generating the download URL
4. **Improved Headers**: Sets proper Content-Type and Content-Disposition headers
5. **Audit Trail**: Provides centralized logging of all download attempts

## Lambda Audio Processing

### Configuration

Lambda configuration is stored in:

- `.env` - Contains the Lambda endpoint URL
- `config/services.php` - Configures the Lambda service

Key environment variable:

```
AWS_LAMBDA_AUDIO_PROCESSOR_URL=https://your-lambda-endpoint.execute-api.region.amazonaws.com/dev
```

### Lambda Function

The Lambda function is a Python-based serverless application that:

1. Receives an S3 file URL
2. Downloads the audio file
3. Processes it using FFmpeg to extract duration
4. Generates waveform data by sampling the audio
5. Returns the duration and waveform peaks

The Lambda function code:
```python
import json
import boto3
import subprocess
import tempfile
import os
import sys
import numpy as np
import urllib.request
import logging

def lambda_handler(event, context):
    # Extract file URL from request
    # Download and process the audio
    # Return duration and waveform peaks
    return {
        'statusCode': 200,
        'body': json.dumps({
            'duration': duration,
            'peaks': peaks
        })
    }
```

### Dependencies

The Lambda function requires:

1. **FFmpeg** - Added as a Lambda layer for audio processing
2. **NumPy** - Added as a Lambda layer for numerical processing
3. **Python 3.8+** - Runtime environment

## Integration Workflow

### 1. File Upload

When a file is uploaded:

```php
// In ManagePitch.php
$filePath = $file->storeAs(
    'pitch_files/' . $this->pitch->id, 
    $fileName, 
    's3'
);

// For audio files, dispatch job to generate waveform
if (in_array($extension, $audioExtensions)) {
    GenerateAudioWaveform::dispatch($pitchFile);
}
```

### 2. Waveform Generation Job

The job processes audio files:

```php
// In GenerateAudioWaveform.php
public function handle()
{
    // Get S3 URL for the file
    $fileUrl = $this->pitchFile->fullFilePath;
    
    // Process audio with Lambda
    $result = $this->processAudioWithExternalService($fileUrl);
    
    // Store the results
    $this->pitchFile->update([
        'waveform_peaks' => json_encode($result['waveform_peaks']),
        'waveform_processed' => true,
        'waveform_processed_at' => now(),
        'duration' => $result['duration'],
    ]);
}
```

### 3. Lambda Processing

The job calls Lambda to process the audio:

```php
protected function processAudioWithExternalService($fileUrl)
{
    // Encode URL properly
    $encodedFileUrl = str_replace(' ', '%20', $fileUrl);
    
    // Call Lambda function
    $response = Http::post($lambdaUrl, [
        'file_url' => $encodedFileUrl,
        'peaks_count' => 200
    ]);
    
    // Process response and return data
    // ...
}
```

### 4. Fallback Mechanism

If Lambda processing fails, a fallback method estimates duration and generates placeholder waveform data:

```php
protected function generateFallbackWaveformData()
{
    $numPeaks = 200;
    $duration = $this->estimateDurationFromFileSize();
    $peaks = $this->generatePlaceholderWaveform($numPeaks);
    
    return [
        'duration' => $duration,
        'waveform_peaks' => $peaks
    ];
}
```

## Frontend Integration

The waveform data is used by the frontend to display interactive audio waveforms:

```javascript
// In pitch-file-player.blade.php
const wavesurfer = WaveSurfer.create({
    container: `#${waveformId}`,
    waveColor: '#d1d5db',
    progressColor: '#4f46e5',
    // ...other options
});

// Load audio
wavesurfer.load("{{ $file->fullFilePath }}");
```

## Testing & Debugging

### Testing Routes

The application includes several testing routes:

1. `/test-audio-processor` - Web interface for testing
2. `/test-lambda-direct` - Tests basic Lambda connectivity
3. `/test-lambda-with-file/{file_id?}` - Tests Lambda with a specific file
4. `/test-lambda-url-formats/{file_id?}` - Tests different URL encoding formats

### Artisan Command

There's an Artisan command to manually generate waveforms:

```bash
# Generate waveform for a specific file
php artisan waveform:generate --file_id=123

# Generate for all files
php artisan waveform:generate --all

# Force regeneration
php artisan waveform:generate --all --force
```

## Common Issues & Solutions

### URL Encoding Issues

URLs with spaces or special characters must be properly encoded:

```php
$encodedUrl = str_replace(' ', '%20', $url);
```

### Lambda Response Format

Lambda returns data in a nested format that must be properly parsed:

```php
// API Gateway integration format
{
    "statusCode": 200,
    "body": "{\"duration\":180.5,\"peaks\":[[0.1,-0.1],[0.2,-0.2]]}"
}
```

### Lambda Dependencies

The Lambda function requires:
- FFmpeg layer with proper permissions
- NumPy layer
- Proper IAM permissions to access S3

## Logging

All AWS operations are logged for debugging:

```php
Log::info('Calling AWS Lambda audio processor', [
    'lambda_url' => $lambdaUrl,
    'file_url' => $fileUrl,
    'encoded_file_url' => $encodedFileUrl,
    'file_path' => $this->pitchFile->file_path
]);
```

Check `storage/logs/laravel.log` for detailed logs of all AWS operations.

## Security Considerations

1. **S3 Bucket Permissions**: Files in S3 need to be accessible via URL by the Lambda function
2. **Lambda IAM Role**: Needs permissions to access S3 objects
3. **API Gateway**: Configured with appropriate CORS settings and authentication

## Production Security Considerations

When deploying to production, additional security measures should be implemented:

### AWS IAM Best Practices

1. **Least Privilege Principle**: Create dedicated IAM roles with minimal permissions:
   - For S3: Use separate roles for read and write operations
   - For Lambda: Create function-specific roles with access only to required resources

2. **IAM Access Keys**:
   - Rotate access keys regularly (at least every 90 days)
   - Use AWS Secrets Manager or parameter store instead of environment variables
   - Enable MFA for all IAM users with console access

3. **Cross-Account Access**: If using multiple AWS accounts (e.g., dev/staging/prod), use cross-account roles rather than access keys

### S3 Security Hardening

1. **Bucket Policies**:
   - Enforce HTTPS-only access with a bucket policy
   - Implement IP-based restrictions for admin operations
   - Add explicit deny statements for public access

2. **Data Protection**:
   - Enable S3 server-side encryption (SSE-S3 or KMS)
   - Consider enabling object versioning for critical data
   - Enable access logging for security audits

3. **Signed URLs**:
   - Use presigned URLs with short expiration times instead of public URLs
   - Implement URL signing in your application:
   ```php
   $cmd = $s3Client->getCommand('GetObject', [
       'Bucket' => $bucket,
       'Key' => $key
   ]);
   $presignedUrl = $s3Client->createPresignedRequest($cmd, '+20 minutes')->getUri();
   ```

### Lambda Security

1. **Function Security**:
   - Enable AWS X-Ray for function tracing and debugging
   - Set appropriate memory and timeout values to prevent DoS
   - Use environment variables stored in AWS Parameter Store with encryption

2. **Code Security**:
   - Scan dependencies for vulnerabilities using tools like Snyk or OWASP
   - Sanitize all input from HTTP requests 
   - Validate file sizes and types before processing
   - Implement proper error handling that doesn't leak sensitive info

3. **API Gateway Protection**:
   - Enable AWS WAF for your API endpoints
   - Implement request throttling to prevent abuse
   - Add authentication and authorization (Lambda authorizers, Cognito, or JWT)
   - Create a custom domain with TLS 1.2+ and strong ciphers

### Network Security

1. **VPC Configuration**:
   - Consider running Lambda in a VPC for additional network isolation
   - Use security groups to restrict traffic
   - Implement VPC Endpoints for AWS services to avoid internet egress

2. **API Rate Limiting**:
   - Set appropriate throttling on API Gateway
   - Implement client-side retry logic with exponential backoff

### Operational Security

1. **Monitoring and Alerting**:
   - Enable CloudWatch alarms for unusual activity
   - Set up notifications for access key usage
   - Monitor for failed authentication attempts
   - Track Lambda errors and timeouts

2. **Logging and Auditing**:
   - Enable AWS CloudTrail for API activity auditing
   - Use centralized logging (CloudWatch Logs)
   - Implement log analysis for security events
   - Retain logs according to compliance requirements

3. **Incident Response**:
   - Create an incident response plan for AWS services
   - Document procedures for rotating compromised credentials
   - Maintain backup access methods to critical services

### Data Privacy and Compliance

1. **Personal Data Management**:
   - Classify data stored in S3 and ensure appropriate protections
   - Consider redaction/anonymization of sensitive audio content
   - Implement retention policies for user-uploaded content

2. **Compliance**:
   - Ensure your AWS configuration meets necessary compliance standards (GDPR, HIPAA, etc.)
   - Document your security controls and policies
   - Regularly review AWS Trusted Advisor recommendations

### Testing and Validation

1. **Security Testing**:
   - Run regular penetration tests against your API Gateway
   - Use AWS Config to validate security configurations
   - Check S3 buckets for unintended public access
   - Test Lambda functions for security vulnerabilities

2. **Disaster Recovery**:
   - Create backup procedures for critical data
   - Document recovery processes for service disruptions
   - Test recovery procedures periodically

By implementing these security measures, you'll significantly improve the security posture of your AWS-integrated application in production.

## Maintenance Tasks

1. **Queue Worker**: Ensure the queue worker is running to process background jobs:
   ```bash
   php artisan queue:work
   ```

2. **Lambda Layer Updates**: When updating FFmpeg or other dependencies, create new Lambda layers

3. **Monitoring**: Check CloudWatch logs for Lambda errors 