# MixPitch Waveform Generator - Cloudflare Worker

A high-performance audio waveform generation service built with Cloudflare Workers and FFmpeg WebAssembly.

## Overview

This Cloudflare Worker processes audio files from Cloudflare R2 storage and generates waveform data using FFmpeg compiled to WebAssembly. It's designed to replace the AWS Lambda-based waveform generation system with improved performance and global edge distribution.

## Features

- 🎵 **Multiple Format Support**: MP3, WAV, FLAC, AAC, M4A, OGG, Opus, WMA
- ⚡ **Edge Computing**: Runs on Cloudflare's global edge network
- 🔄 **WebAssembly Processing**: Uses FFmpeg WASM for high-quality audio analysis
- 📊 **Detailed Monitoring**: Comprehensive logging and performance metrics
- 🛡️ **Robust Error Handling**: Graceful fallbacks and detailed error reporting
- 🌍 **CORS Support**: Ready for cross-origin requests

## Quick Start

### Prerequisites

- Node.js 18+ 
- Cloudflare account with Workers plan
- Wrangler CLI installed globally

### Installation

1. **Install dependencies**:
```bash
npm install
```

2. **Install Wrangler CLI** (if not already installed):
```bash
npm install -g wrangler
```

3. **Login to Cloudflare**:
```bash
wrangler login
```

4. **Configure your R2 bucket** in `wrangler.toml`:
```toml
[[r2_buckets]]
binding = "AUDIO_BUCKET"
bucket_name = "your-audio-bucket-name"
```

### Development

1. **Start local development server**:
```bash
npm run dev
```

2. **Test the worker locally**:
```bash
curl -X POST http://localhost:8787 \
  -H "Content-Type: application/json" \
  -d '{"file_url": "path/to/your/audio/file.mp3", "peaks_count": 200}'
```

### Deployment

1. **Deploy to staging**:
```bash
./deploy.sh staging
```

2. **Deploy to production**:
```bash
./deploy.sh production
```

Or use the npm scripts:
```bash
npm run deploy          # Deploy to production
npm run deploy:staging  # Deploy to staging
```

## API Reference

### POST /

Generate waveform data from an audio file.

**Request Body:**
```json
{
  "file_url": "path/to/audio/file.mp3",
  "peaks_count": 200
}
```

**Parameters:**
- `file_url` (required): Path to the audio file in R2 storage
- `peaks_count` (optional): Number of waveform peaks to generate (1-1000, default: 200)

**Response:**
```json
{
  "duration": 180.5,
  "peaks": [[-0.1, 0.1], [-0.2, 0.3], ...],
  "metadata": {
    "peaks_count": 200,
    "file_size_bytes": 1048576,
    "processing_time_ms": 1500,
    "total_time_ms": 2000
  }
}
```

**Error Response:**
```json
{
  "error": "Error message",
  "status": 400,
  "timestamp": "2024-01-01T00:00:00.000Z",
  "processing_time_ms": 100
}
```

## Configuration

### Environment Variables

Set these in your `wrangler.toml` file or Cloudflare dashboard:

```toml
[vars]
ENVIRONMENT = "production"
MAX_FILE_SIZE_MB = "50"
DEFAULT_PEAKS_COUNT = "200"
ENABLE_LOGGING = "true"
```

### R2 Bucket Setup

1. Create an R2 bucket in your Cloudflare dashboard
2. Update the bucket name in `wrangler.toml`
3. Ensure the Worker has read permissions to the bucket

## Laravel Integration

Update your Laravel application configuration:

### 1. Environment Variables

Add to your `.env` file:
```env
CLOUDFLARE_WAVEFORM_WORKER_URL=https://your-worker.your-subdomain.workers.dev
CLOUDFLARE_WORKER_TOKEN=your-auth-token
CLOUDFLARE_R2_BUCKET=your-bucket-name
```

### 2. Queue Job Configuration

The `GenerateAudioWaveform` job will automatically use Cloudflare Workers when configured, with AWS Lambda as fallback.

## Performance

### Benchmarks

Typical processing times on Cloudflare Workers:

| File Size | Format | Duration | Processing Time |
|-----------|--------|----------|----------------|
| 3MB       | MP3    | 3 min    | ~2 seconds     |
| 8MB       | WAV    | 5 min    | ~4 seconds     |
| 15MB      | FLAC   | 10 min   | ~8 seconds     |

### Optimization Tips

1. **File Size**: Keep files under 50MB for optimal performance
2. **Format**: MP3 and AAC process faster than lossless formats
3. **Peaks Count**: Lower peak counts (100-300) process faster
4. **Caching**: Results are cached for 1 hour by default

## Monitoring

### Viewing Logs

```bash
# Real-time logs
wrangler tail

# Logs with filtering
wrangler tail --format pretty --grep "ERROR"
```

### Metrics

Monitor your worker in the Cloudflare dashboard:
- Request count and success rate
- Response time percentiles  
- Error rates and types
- CPU usage and memory consumption

## Troubleshooting

### Common Issues

1. **"FFmpeg initialization failed"**
   - Check that WebAssembly is enabled
   - Verify memory limits aren't exceeded

2. **"File not found in R2"**
   - Verify R2 bucket configuration
   - Check file path encoding
   - Ensure Worker has read permissions

3. **"File size exceeds limit"**
   - Increase `MAX_FILE_SIZE_MB` in configuration
   - Use smaller audio files or compress them

4. **"Unsupported file format"**
   - Check supported formats list
   - Verify file extension is correct

### Debug Mode

Enable detailed logging by setting:
```toml
[vars]
ENABLE_LOGGING = "true"
```

## Development

### Project Structure

```
src/
├── index.js           # Main worker script
├── audio-processor.js # FFmpeg WASM audio processing
└── r2-manager.js      # R2 storage operations

package.json           # Dependencies and scripts
wrangler.toml         # Cloudflare Workers configuration
deploy.sh             # Deployment script
```

### Adding New Features

1. **New Audio Formats**: Update `getSupportedFormats()` in `audio-processor.js`
2. **Custom Processing**: Extend the `AudioProcessor` class
3. **Additional Endpoints**: Add new routes in `index.js`

### Testing

```bash
# Run linting
npm run lint

# Run tests (when available)
npm test

# Local development with hot reload
npm run dev
```

## Security

- Input validation for all parameters
- File size limits to prevent abuse
- CORS headers for cross-origin requests
- Optional authentication token support

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Support

For issues and questions:
- Check the troubleshooting section
- Review Cloudflare Workers documentation
- Open an issue in the project repository

## License

MIT License - see LICENSE file for details. 