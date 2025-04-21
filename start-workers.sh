#!/bin/bash

# Kill any existing queue workers
pkill -f 'queue:work' || echo "No workers to kill"

# Number of parallel workers
NUM_WORKERS=10

# Laravel artisan queue worker command
ARTISAN_PATH="/www/wwwroot/sunopanel.prus.dev/artisan"
PHP_PATH="/www/server/php/83/bin/php"

echo "Starting $NUM_WORKERS queue workers..."

# Start the workers
for ((i=1; i<=$NUM_WORKERS; i++))
do
    echo "Starting worker $i..."
    nohup $PHP_PATH $ARTISAN_PATH queue:work redis --sleep=3 --tries=3 --backoff=0 --max-time=3600 > /www/wwwroot/sunopanel.prus.dev/storage/logs/worker-$i.log 2>&1 &
done

echo "All workers started. Use 'ps aux | grep queue:work' to verify." 