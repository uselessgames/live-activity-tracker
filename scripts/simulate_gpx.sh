#!/bin/bash
# replays a GPX track through the tracker API, using the file's own lat/lon
# and timestamps. sleeps between points to match the original recording pace.
#
# usage: ./simulate_gpx.sh path/to/track.gpx

GPX_FILE="${1:-example.gpx}"
API_KEY="test-key-123"
URL="http://localhost:8080/api/report.php"

if [ ! -f "$GPX_FILE" ]; then
  echo "GPX file not found: $GPX_FILE"
  exit 1
fi

mapfile -t LATS < <(grep -oP '(?<=<trkpt lat=")[^"]+' "$GPX_FILE")
mapfile -t LONS < <(grep -oP '(?<= lon=")[^"]+(?=")' "$GPX_FILE")
mapfile -t TIMES < <(grep -oP '(?<=<time>)[^<]+' "$GPX_FILE")

COUNT=${#LATS[@]}
echo "Loaded $COUNT points from $GPX_FILE"

PREV_EPOCH=""

for ((i = 0; i < COUNT; i++)); do
  LAT="${LATS[$i]}"
  LON="${LONS[$i]}"
  EPOCH=$(date -d "${TIMES[$i]}" +%s)

  if [ -n "$PREV_EPOCH" ]; then
    DELAY=$((EPOCH - PREV_EPOCH))
    [ "$DELAY" -gt 0 ] && sleep "$DELAY"
  fi
  PREV_EPOCH="$EPOCH"

  curl -s -X POST "$URL" \
    -H "Content-Type: application/json" \
    -d "{\"api_key\":\"$API_KEY\",\"lat\":$LAT,\"lon\":$LON,\"time_recorded\":$EPOCH}" > /dev/null

  echo "[$((i + 1))/$COUNT] lat $LAT, lon $LON, time $EPOCH"
done

echo "Playback finished."
