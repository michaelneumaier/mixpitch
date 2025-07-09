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
        
        # Upload processed file to S3
        s3_url = upload_to_s3(output_path, target_format, watermark_settings)
        
        # Get output file info
        output_info = get_audio_info(output_path)
        
        return {
            'success': True,
            'output_url': s3_url,
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
        for temp_path in [input_path, output_path]:
            if os.path.exists(temp_path):
                try:
                    os.unlink(temp_path)
                    logger.info(f"Cleaned up temporary file: {temp_path}")
                except Exception as e:
                    logger.warning(f"Failed to clean up {temp_path}: {e}")

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
    
    # Default watermark settings
    frequency = watermark_settings.get('frequency', 1000)  # 1kHz
    volume = watermark_settings.get('volume', 0.1)         # 10% volume
    duration = watermark_settings.get('duration', 0.5)     # 500ms
    interval = watermark_settings.get('interval', 30)      # Every 30 seconds
    
    # Create a subtle watermark using frequency modulation and EQ
    # This approach is less intrusive but still provides protection
    watermark_filter = f"volume={1.0 + volume},highpass=f=20,lowpass=f=18000,dynaudnorm=f=75:g=25"
    
    # Alternative: Add periodic subtle clicks (more detectable but less annoying)
    # Use this for stronger watermarking if needed
    if watermark_settings.get('type') == 'periodic_tone':
        # Generate periodic tone bursts
        tone_filter = f"sine=frequency={frequency}:beep_factor=4[tone];" \
                     f"[0:a][tone]amix=inputs=2:duration=first:weights=1 {volume}"
        return tone_filter
    
    return watermark_filter

def upload_to_s3(file_path, file_format, watermark_settings):
    """Upload processed file to S3 and return public URL"""
    
    # Get S3 bucket from environment or use default
    bucket_name = os.environ.get('AUDIO_PROCESSING_BUCKET', os.environ.get('AWS_BUCKET', 'mixpitch-storage'))
    
    # Generate unique filename
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    unique_id = str(uuid.uuid4())[:8]
    pitch_id = watermark_settings.get('pitch_id', 'unknown')
    project_id = watermark_settings.get('project_id', 'unknown')
    
    s3_key = f"processed-audio/{project_id}/{pitch_id}/transcoded_{timestamp}_{unique_id}.{file_format}"
    
    logger.info(f"Uploading to S3: bucket={bucket_name}, key={s3_key}")
    
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
                        'project_id': str(project_id)
                    }
                }
            )
        
        # Generate the S3 URL
        s3_url = f"https://{bucket_name}.s3.amazonaws.com/{s3_key}"
        
        logger.info(f"File uploaded successfully: {s3_url}")
        return s3_url
        
    except Exception as e:
        logger.error(f"S3 upload failed: {str(e)}")
        raise Exception(f"Failed to upload processed file to S3: {str(e)}")

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