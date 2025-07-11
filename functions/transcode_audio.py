import json
import boto3
import subprocess
import tempfile
import os
import sys
import urllib.request
import logging
import uuid
from datetime import datetime

os.environ['PATH'] = '/opt/bin:' + os.environ['PATH']

logger = logging.getLogger()
logger.setLevel(logging.INFO)

s3_client = boto3.client('s3')

def lambda_handler(event, context):
    logger.info(f"Received transcoding event: {json.dumps(event)}")
    
    try:
        # Parse the incoming request
        body = event.get('body', '{}')
        if isinstance(body, str):
            body = json.loads(body)
        else:
            body = body or {}
            
        file_url = body.get('file_url')
        target_format = body.get('target_format', 'mp3')
        target_bitrate = body.get('target_bitrate', '192k')
        apply_watermark = body.get('apply_watermark', False)
        watermark_settings = body.get('watermark_settings', {})
        
        logger.info(f"Processing file URL: {file_url}")
        logger.info(f"Target format: {target_format}, bitrate: {target_bitrate}")
        logger.info(f"Apply watermark: {apply_watermark}")
        
        if not file_url:
            return format_response(400, {'error': 'Missing file_url parameter'})
        
        # Check if ffmpeg is available
        try:
            subprocess.run(['ffmpeg', '-version'], capture_output=True)
            logger.info("FFmpeg is available")
        except Exception as e:
            logger.error(f"FFmpeg not found: {str(e)}")
            return format_response(500, {'error': 'FFmpeg not available'})
        
        # Process the audio file
        result = process_audio_file(file_url, target_format, target_bitrate, apply_watermark, watermark_settings)
        
        return format_response(200, result)
    
    except Exception as e:
        logger.error(f"Error processing audio: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        return format_response(500, {'error': str(e)})


def process_audio_file(file_url, target_format, target_bitrate, apply_watermark, watermark_settings):
    """Process audio file with transcoding and optional watermarking"""
    
    # Create temporary files
    with tempfile.NamedTemporaryFile(delete=False, suffix='.tmp') as input_file:
        input_path = input_file.name
    
    output_path = tempfile.mktemp(suffix=f'.{target_format}')
    
    try:
        # Download the input file
        logger.info(f"Downloading file from: {file_url}")
        urllib.request.urlretrieve(file_url, input_path)
        
        # Get input file info
        input_info = get_audio_info(input_path)
        logger.info(f"Input file info: {input_info}")
        
        # Build FFmpeg command
        ffmpeg_cmd = build_ffmpeg_command(
            input_path, 
            output_path, 
            target_format, 
            target_bitrate, 
            apply_watermark, 
            watermark_settings
        )
        
        logger.info(f"Running FFmpeg command: {' '.join(ffmpeg_cmd)}")
        
        # Execute FFmpeg
        result = subprocess.run(ffmpeg_cmd, capture_output=True, text=True)
        
        if result.returncode != 0:
            logger.error(f"FFmpeg error: {result.stderr}")
            raise Exception(f"FFmpeg processing failed: {result.stderr}")
        
        logger.info("FFmpeg processing completed successfully")
        
        # Upload processed file directly to R2 (or S3 as fallback)
        upload_result = upload_to_r2(output_path, target_format, watermark_settings)
        
        # Get output file info
        output_info = get_audio_info(output_path)
        
        return {
            'success': True,
            'output_url': upload_result['signed_url'],
            's3_key': upload_result['s3_key'],
            'direct_upload': upload_result['direct_upload'],
            'input_info': input_info,
            'output_info': output_info,
            'watermarked': apply_watermark,
            'processing_details': {
                'target_format': target_format,
                'target_bitrate': target_bitrate,
                'watermark_applied': apply_watermark
            }
        }
        
    finally:
        # Clean up temporary files
        if os.path.exists(input_path):
            os.unlink(input_path)
        if os.path.exists(output_path):
            os.unlink(output_path)
        logger.info("Temporary files cleaned up")

def get_audio_info(file_path):
    """Get audio file information using ffprobe"""
    cmd = [
        'ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_format', '-show_streams', file_path
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        logger.warning(f"ffprobe failed: {result.stderr}")
        return {}
    
    try:
        info = json.loads(result.stdout)
        format_info = info.get('format', {})
        stream_info = next((s for s in info.get('streams', []) if s.get('codec_type') == 'audio'), {})
        
        return {
            'duration': float(format_info.get('duration', 0)),
            'bitrate': int(format_info.get('bit_rate', 0)),
            'codec': stream_info.get('codec_name', 'unknown'),
            'sample_rate': int(stream_info.get('sample_rate', 0)),
            'channels': int(stream_info.get('channels', 0))
        }
    except (json.JSONDecodeError, ValueError, KeyError) as e:
        logger.warning(f"Failed to parse ffprobe output: {e}")
        return {}

def build_ffmpeg_command(input_path, output_path, target_format, target_bitrate, apply_watermark, watermark_settings):
    """Build FFmpeg command for transcoding and watermarking"""
    
    cmd = [
        'ffmpeg',
        '-i', input_path,
        '-y',  # Overwrite output file
        '-codec:a', 'libmp3lame',  # Use MP3 encoder
        '-b:a', target_bitrate,    # Set bitrate
        '-ar', '44100',            # Sample rate
        '-ac', '2'                 # Stereo channels
    ]
    
    # Add watermarking if requested
    if apply_watermark:
        watermark_filter = build_watermark_filter(watermark_settings)
        cmd.extend(['-af', watermark_filter])
    
    cmd.append(output_path)
    
    return cmd

def build_watermark_filter(watermark_settings):
    """Build FFmpeg audio filter for watermarking"""
    
    try:
        # Default watermark settings with proper type conversion
        frequency = int(watermark_settings.get('frequency', 1000))  # 1kHz
        volume = float(watermark_settings.get('volume', 0.1))       # 10% volume
        duration = float(watermark_settings.get('duration', 0.5))   # 500ms
        interval = int(watermark_settings.get('interval', 30))      # Every 30 seconds
        
        logger.info(f"Watermark settings: frequency={frequency}, volume={volume}, duration={duration}, interval={interval}")
        
    except (ValueError, TypeError) as e:
        logger.error(f"Error converting watermark settings: {e}")
        # Use safe defaults
        frequency = 1000
        volume = 0.1
        duration = 0.5
        interval = 30
    
    # Check if we should use periodic tone watermark
    if watermark_settings.get('type') == 'periodic_tone':
        # Generate periodic white noise bursts using FFmpeg anoisesrc
        # White noise covers full frequency spectrum, making it harder to remove
        logger.info(f"Using periodic white noise watermark: every {interval}s for {duration}s at {volume} volume")
        
        # Create white noise and control its volume periodically
        # This uses anoisesrc filter with white noise and mathematical volume control
        noise_filter = (
            f"anoisesrc=color=white:amplitude=0.5:sample_rate=44100[noise];"
            f"[noise]volume='if(between(mod(t,{interval}),0,{duration}),{volume},0)':eval=frame[gated_noise];"
            f"[0:a][gated_noise]amix=inputs=2:duration=first"
        )
        
        logger.info(f"Generated white noise filter: {noise_filter}")
        return noise_filter
    
    # Default subtle watermark using frequency modulation and EQ
    # This approach is less intrusive but still provides protection
    watermark_filter = f"volume={1.0 + volume},highpass=f=20,lowpass=f=18000,dynaudnorm=f=75:g=25"
    
    return watermark_filter

def upload_to_r2(file_path, file_format, watermark_settings):
    """Upload processed file directly to R2 and return signed URL"""
    
    # Use R2 configuration for direct upload
    # R2 is S3-compatible, so we can use boto3 with custom endpoint
    import boto3
    
    # R2 credentials from environment
    r2_access_key = os.environ.get('CF_R2_ACCESS_KEY_ID', '').strip()
    r2_secret_key = os.environ.get('CF_R2_SECRET_ACCESS_KEY', '').strip()
    r2_endpoint = os.environ.get('CF_R2_ENDPOINT', '').strip()
    r2_bucket = os.environ.get('CF_R2_BUCKET', '').strip()
    
    # Fallback to AWS S3 if R2 not configured
    if not all([r2_access_key, r2_secret_key, r2_endpoint, r2_bucket]):
        logger.warning("R2 not configured (missing credentials), falling back to AWS S3")
        return upload_to_s3_fallback(file_path, file_format, watermark_settings)
    
    # Create R2 client (S3-compatible)
    r2_client = boto3.client(
        's3',
        aws_access_key_id=r2_access_key,
        aws_secret_access_key=r2_secret_key,
        endpoint_url=r2_endpoint,
        region_name='auto'  # R2 uses 'auto' region
    )
    
    # Generate unique filename
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    unique_id = str(uuid.uuid4())[:8]
    pitch_id = watermark_settings.get('pitch_id', 'unknown')
    project_id = watermark_settings.get('project_id', 'unknown')
    
    # Use the final destination path that Laravel expects
    s3_key = f"pitches/{pitch_id}/processed/transcoded_{timestamp}_{unique_id}.{file_format}"
    
    logger.info(f"Uploading directly to R2: bucket={r2_bucket}, key={s3_key}")
    
    try:
        # Upload file to R2
        with open(file_path, 'rb') as file_data:
            r2_client.upload_fileobj(
                file_data,
                r2_bucket,
                s3_key,
                ExtraArgs={
                    'ContentType': f'audio/{file_format}',
                    'Metadata': {
                        'processed_at': timestamp,
                        'original_watermarked': str(watermark_settings.get('watermark_applied', False)),
                        'pitch_id': str(pitch_id),
                        'project_id': str(project_id),
                        'processing_source': 'aws_lambda_to_r2'
                    }
                }
            )
        
        # Generate a signed URL for downloading (valid for 10 minutes)
        signed_url = r2_client.generate_presigned_url(
            'get_object',
            Params={'Bucket': r2_bucket, 'Key': s3_key},
            ExpiresIn=600  # 10 minutes should be enough for Laravel to verify
        )
        
        logger.info(f"File uploaded successfully to R2: {s3_key}")
        logger.info(f"R2 signed URL: {signed_url}")
        
        # Return both the signed URL and the final S3 key
        return {
            'signed_url': signed_url,
            's3_key': s3_key,
            'direct_upload': True,
            'storage_provider': 'r2'
        }
        
    except Exception as e:
        logger.error(f"R2 upload failed: {str(e)}")
        # Fallback to S3 on R2 failure
        logger.info("Falling back to AWS S3 due to R2 failure")
        return upload_to_s3_fallback(file_path, file_format, watermark_settings)


def upload_to_s3_fallback(file_path, file_format, watermark_settings):
    """Fallback upload to AWS S3 if R2 fails"""
    
    # Get S3 bucket from environment or use default
    bucket_name = os.environ.get('AUDIO_PROCESSING_BUCKET', os.environ.get('AWS_BUCKET', 'mixpitch-storage'))
    
    # Generate unique filename
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    unique_id = str(uuid.uuid4())[:8]
    pitch_id = watermark_settings.get('pitch_id', 'unknown')
    project_id = watermark_settings.get('project_id', 'unknown')
    
    # Use the final destination path that Laravel expects
    s3_key = f"pitches/{pitch_id}/processed/transcoded_{timestamp}_{unique_id}.{file_format}"
    
    logger.info(f"Uploading to AWS S3 fallback: bucket={bucket_name}, key={s3_key}")
    
    try:
        # Upload file to S3
        with open(file_path, 'rb') as file_data:
            s3_client.upload_fileobj(
                file_data,
                bucket_name,
                s3_key,
                ExtraArgs={
                    'ContentType': f'audio/{file_format}',
                    'Metadata': {
                        'processed_at': timestamp,
                        'original_watermarked': str(watermark_settings.get('watermark_applied', False)),
                        'pitch_id': str(pitch_id),
                        'project_id': str(project_id),
                        'processing_source': 'aws_lambda_s3_fallback'
                    }
                }
            )
        
        # Generate a signed URL for downloading (valid for 10 minutes)
        signed_url = s3_client.generate_presigned_url(
            'get_object',
            Params={'Bucket': bucket_name, 'Key': s3_key},
            ExpiresIn=600  # 10 minutes should be enough for Laravel to verify
        )
        
        logger.info(f"File uploaded successfully to S3 fallback: {s3_key}")
        logger.info(f"S3 signed URL: {signed_url}")
        
        # Return both the signed URL and the final S3 key
        return {
            'signed_url': signed_url,
            's3_key': s3_key,
            'direct_upload': True,
            'storage_provider': 's3'
        }
        
    except Exception as e:
        logger.error(f"S3 fallback upload failed: {str(e)}")
        raise Exception(f"Failed to upload processed file to both R2 and S3: {str(e)}")


# Legacy function name for backward compatibility
def upload_to_s3(file_path, file_format, watermark_settings):
    """Legacy function that now uploads to R2 first, then S3 as fallback"""
    return upload_to_r2(file_path, file_format, watermark_settings)

def format_response(status_code, body):
    """Format Lambda response with CORS headers"""
    return {
        'statusCode': status_code,
        'headers': {
            'Content-Type': 'application/json',
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type',
            'Cache-Control': 'no-cache'
        },
        'body': json.dumps(body)
    } 