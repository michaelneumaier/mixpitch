#!/bin/bash

# Start the queue worker in the background with retry and sleep settings
# This will keep the worker running and retry failed jobs

echo "Starting queue worker..."
php artisan queue:work --tries=3 --sleep=3 --timeout=300 > storage/logs/queue-worker.log 2>&1 &

# Save the process ID
echo $! > storage/queue-worker.pid
echo "Queue worker started with PID: $!"
echo "Logs are being written to storage/logs/queue-worker.log"
echo "To stop the worker, run: kill \$(cat storage/queue-worker.pid)" 