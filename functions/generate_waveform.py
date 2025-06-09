import json
import boto3
import subprocess
import tempfile
import os
import sys
import numpy as np
import urllib.request
import logging

os.environ['PATH'] = '/opt/bin:' + os.environ['PATH']

logger = logging.getLogger()
logger.setLevel(logging.INFO)

s3_client = boto3.client('s3')

def lambda_handler(event, context):
    logger.info(f"Received event: {json.dumps(event)}")
    
    try:
        # Parse the incoming request
        body = event.get('body', '{}')
        if isinstance(body, str):
            body = json.loads(body)
        else:
            body = body or {}
            
        file_url = body.get('file_url')
        peaks_count = int(body.get('peaks_count', 200))
        
        logger.info(f"Processing file URL: {file_url}")
        logger.info(f"Peaks count: {peaks_count}")
        
        if not file_url:
            return format_response(400, {'error': 'Missing file_url parameter'})
        
        # Check if ffmpeg is available
        try:
            subprocess.run(['ffmpeg', '-version'], capture_output=True)
            logger.info("FFmpeg is available")
        except Exception as e:
            logger.error(f"FFmpeg not found: {str(e)}")
            return format_response(500, {'error': 'FFmpeg not available'})
        
        # Create a temp file to download the audio
        with tempfile.NamedTemporaryFile(delete=False, suffix='.mp3') as temp_file:
            temp_path = temp_file.name
            
        logger.info(f"Downloading file to {temp_path}")
        urllib.request.urlretrieve(file_url, temp_path)
        
        # Get duration using ffprobe
        logger.info("Getting duration using ffprobe")
        duration_cmd = [
            'ffprobe', '-v', 'error', '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1', temp_path
        ]
        
        duration_result = subprocess.run(duration_cmd, capture_output=True, text=True)
        if duration_result.returncode != 0:
            logger.error(f"ffprobe error: {duration_result.stderr}")
            return format_response(500, {'error': f'ffprobe error: {duration_result.stderr}'})
            
        duration = float(duration_result.stdout.strip())
        logger.info(f"Duration: {duration} seconds")
        
        # Generate waveform data
        logger.info("Generating waveform data")
        peaks = generate_waveform(temp_path, peaks_count)
        
        # Clean up
        os.unlink(temp_path)
        
        return format_response(200, {
            'duration': duration,
            'peaks': peaks
        })
    
    except Exception as e:
        logger.error(f"Error processing audio: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        return format_response(500, {'error': str(e)})

def generate_waveform(audio_path, num_peaks):
    # Sample the audio into raw PCM data using ffmpeg
    logger.info("Converting audio to raw PCM data")
    raw_data_cmd = [
        'ffmpeg', '-i', audio_path, '-f', 's16le', '-ac', '1', '-ar', '44100', '-'
    ]
    
    raw_data_result = subprocess.run(raw_data_cmd, capture_output=True)
    if raw_data_result.returncode != 0:
        logger.error(f"FFmpeg error: {raw_data_result.stderr.decode('utf-8', errors='ignore')}")
        raise Exception("FFmpeg error during audio conversion")
    
    raw_audio = raw_data_result.stdout
    logger.info(f"Raw PCM data size: {len(raw_audio)} bytes")
    
    # Convert to numpy array for processing
    samples = np.frombuffer(raw_audio, dtype=np.int16)
    logger.info(f"Converted to {len(samples)} samples")
    
    # Take absolute values for waveform
    samples = np.abs(samples)
    
    # Create segments and take max in each segment
    samples_per_peak = max(1, len(samples) // num_peaks)
    peaks = []
    
    logger.info(f"Calculating {num_peaks} peaks with {samples_per_peak} samples per peak")
    
    for i in range(num_peaks):
        start = i * samples_per_peak
        end = min(start + samples_per_peak, len(samples))
        
        if start >= len(samples):
            # Fill remaining peaks with zeros if we've run out of samples
            peaks.append([0, 0])
            continue
            
        segment = samples[start:end]
        if len(segment) > 0:
            # Normalize to 0-1 range and create symmetrical peaks
            max_value = np.max(segment) / 32768.0
            peaks.append([-max_value, max_value])
        else:
            peaks.append([0, 0])
    
    logger.info(f"Generated {len(peaks)} peaks")
    return peaks

def format_response(status_code, body):
    return {
        'statusCode': status_code,
        'headers': {
            'Content-Type': 'application/json',
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type'
        },
        'body': json.dumps(body)
    }