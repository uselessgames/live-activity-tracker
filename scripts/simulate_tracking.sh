#!/bin/bash
# simulates gps tracker reporting location every second (or pass int for longer interval)

LAT=0.518389
LON=25.205708
HEADING=270
DIST=80       # metres per step
SLEEP_INTERVAL="${1:-1}" 

API_KEY="test-key-123"
URL="http://localhost:8080/api/report.php"

while true; do
  read LAT LON HEADING <<< $(awk -v lat="$LAT" -v lon="$LON" -v heading="$HEADING" -v dist="$DIST" 'BEGIN {
    srand(systime() + PROCINFO["pid"] + int(rand()*100000));
    pi = 3.14159265;
    offset = (rand() * 60) - 30;
    new_heading = heading + offset;
    while (new_heading < 0)   new_heading += 360;
    while (new_heading >= 360) new_heading -= 360;
    rad = new_heading * pi / 180;
    dlat = (dist * cos(rad)) / 111320;
    dlon = (dist * sin(rad)) / (111320 * cos(lat * pi / 180));
    printf "%.8f %.8f %.4f", lat + dlat, lon + dlon, new_heading;
  }')

  curl -s -X POST "$URL" \
    -H "Content-Type: application/json" \
    -d "{\"api_key\":\"$API_KEY\",\"lat\":$LAT,\"lon\":$LON}" > /dev/null

  echo "sent: $LAT, $LON (heading: $HEADING)"
  sleep $SLEEP_INTERVAL  # Use the variable here
done
