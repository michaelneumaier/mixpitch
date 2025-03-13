#!/bin/bash

# Check if the PID file exists
if [ -f storage/queue-worker.pid ]; then
    PID=$(cat storage/queue-worker.pid)
    echo "Stopping queue worker with PID: $PID"
    
    # Kill the process
    kill $PID
    
    # Remove the PID file
    rm storage/queue-worker.pid
    
    echo "Queue worker stopped"
else
    echo "No queue worker PID file found. Worker might not be running."
fi 