#!/bin/bash

echo "Stopping all queue workers..."

# Kill all queue:work processes
pkill -f 'queue:work' && echo "All workers stopped." || echo "No workers were running." 