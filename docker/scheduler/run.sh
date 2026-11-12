#!/bin/sh
set -eu
cd /var/www/html
while true; do
  if wp core is-installed >/dev/null 2>&1; then
    wp cron event run --due-now || echo 'WordPress cron failed' >&2
    if wp help action-scheduler >/dev/null 2>&1; then
      wp action-scheduler run --batch-size=20 --batches=1 || echo 'Action Scheduler failed' >&2
    fi
  fi
  date -u +%FT%TZ > /tmp/onestep-scheduler-heartbeat
  sleep 60
done
