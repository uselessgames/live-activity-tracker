#!/bin/bash
# clear_history.sh

docker compose exec db psql -U tracking -d tracking -c "TRUNCATE TABLE positions;"

echo "History cleared."
