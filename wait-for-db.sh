#!/bin/sh
set -e

host="$1"
shift
cmd="$@"

echo "Waiting for MySQL at $host..."

until mysql -h "$host" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "select 1" >/dev/null 2>&1; do
  echo "Database not ready yet..."
  sleep 2
done

echo "Database ready!"
exec $cmd
