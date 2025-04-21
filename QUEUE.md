# Queue Processing Documentation

## Overview

This application uses Laravel's queue system with Redis to process track jobs in the background. The system is configured to run multiple queue workers in parallel to increase throughput.

## Managing Queue Workers

### Starting Queue Workers

To start 10 parallel queue workers, run:

```bash
./start-workers.sh
```

This will start 10 queue worker processes that will process jobs from the Redis queue. Each worker will:
- Process jobs with up to 3 retries
- Sleep for 3 seconds between polling for new jobs
- Automatically terminate after 3600 seconds (1 hour) to prevent memory leaks

### Stopping Queue Workers

To stop all running queue workers, run:

```bash
./stop-workers.sh
```

This will terminate all active queue worker processes.

### Checking Worker Status

To check the status of queue workers, run:

```bash
ps aux | grep queue:work | grep -v grep
```

This will show all running queue worker processes.

### Log Files

Each worker writes its output to a separate log file:

```
/www/wwwroot/sunopanel.prus.dev/storage/logs/worker-{N}.log
```

Where `{N}` is the worker number (1-10).

## Automatic Restart

For production environments, it's recommended to set up Supervisor to manage these processes automatically. This ensures that if a worker crashes, it will be automatically restarted.

## Monitoring Queue Size

To check the current queue size:

```bash
php artisan queue:size
```

## Clearing the Queue

If you need to clear the queue:

```bash
php artisan queue:clear --queue=default
```

## Performance Considerations

The current configuration allows processing up to 10 tracks simultaneously. If you need to adjust this number, modify the `NUM_WORKERS` variable in the `start-workers.sh` script. 