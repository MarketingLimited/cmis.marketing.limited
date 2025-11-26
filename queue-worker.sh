#!/bin/bash
# Queue Worker Management Script for CMIS

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

case "$1" in
    start)
        echo "Starting queue worker..."
        nohup php artisan queue:work --queue=default,social --tries=3 --timeout=120 > storage/logs/queue-worker.log 2>&1 &
        echo "Queue worker started with PID: $!"
        ;;
    stop)
        echo "Stopping queue worker..."
        pkill -f "queue:work"
        echo "Queue worker stopped"
        ;;
    restart)
        echo "Restarting queue worker..."
        pkill -f "queue:work"
        sleep 2
        nohup php artisan queue:work --queue=default,social --tries=3 --timeout=120 > storage/logs/queue-worker.log 2>&1 &
        echo "Queue worker restarted with PID: $!"
        ;;
    status)
        if pgrep -f "queue:work" > /dev/null; then
            echo "Queue worker is running:"
            ps aux | grep "queue:work" | grep -v grep
        else
            echo "Queue worker is not running"
        fi
        ;;
    logs)
        tail -f storage/logs/queue-worker.log
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|logs}"
        exit 1
        ;;
esac

exit 0
