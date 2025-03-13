# Audio Waveform Generation

This document describes the audio waveform generation feature for MixPitch. The feature allows for pre-generating waveform visualization data for audio files, which improves the loading and rendering speed of audio players on the frontend.

## How It Works

The system uses FFmpeg to analyze audio files and extract sample data that is then processed into a format suitable for visualization. This process happens asynchronously through Laravel's queue system to avoid blocking the user interface during uploads.

### Key Components

1. **Database Columns**: Added to the `pitch_files` table:
   - `waveform_peaks`: Stores JSON data of the waveform peaks
   - `waveform_processed`: Boolean flag indicating if processing is complete
   - `waveform_processed_at`: Timestamp of when processing completed

2. **GenerateAudioWaveform Job**: A queued job that processes audio files and stores the waveform data.

3. **WaveSurfer Integration**: The frontend uses the stored waveform data to render visualizations without having to analyze the audio file in the browser.

## Using the Feature

### Automatic Processing

When users upload audio files (mp3, wav, ogg, m4a, flac, aac), the system automatically dispatches a job to generate waveform data. This happens in:
- `PitchFileController::upload` method
- `ManagePitch::uploadFiles` Livewire component

### Manual Processing

To manually process files (e.g., for files that were uploaded before this feature was implemented), you can use the following Artisan command:

```bash
# Generate for a specific file
php artisan waveform:generate {file_id}

# Generate for all unprocessed audio files
php artisan waveform:generate --all

# Force regeneration for files that already have waveform data
php artisan waveform:generate {file_id} --force
php artisan waveform:generate --all --force
```

## Queue Worker

The waveform generation happens in the background using Laravel's queue system. You must have a queue worker running to process these jobs.

### Starting a Queue Worker

In development, you can use the provided scripts:

```bash
# Start a queue worker
./start-queue-worker.sh

# Stop the queue worker
./stop-queue-worker.sh
```

In production, you should configure a proper queue worker using Supervisor or a similar tool.

### Supervisor Configuration Example

Here's an example configuration for Supervisor:

```ini
[program:mixpitch-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3 --timeout=300
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/queue.log
```

## Troubleshooting

### Required Dependencies

- FFmpeg must be installed on the server
- PHP should have exec permissions to run FFmpeg commands

### Common Issues

1. **Job Fails**: Check the Laravel logs for error messages. Common causes include:
   - FFmpeg not installed or not in PATH
   - Permission issues accessing the audio file
   - Memory limits exceeded for large audio files

2. **No Waveform Displayed**: Check that:
   - The `waveform_processed` flag is true for the file
   - The `waveform_peaks` contains valid JSON data
   - The WaveSurfer script is correctly loading the peaks data

## Performance Considerations

- The waveform generation process can be CPU-intensive for longer audio files
- Consider setting appropriate timeouts for the job based on expected file sizes
- The generated waveform data adds to the database size but significantly improves frontend performance

---

For any additional questions or issues, contact the development team. 