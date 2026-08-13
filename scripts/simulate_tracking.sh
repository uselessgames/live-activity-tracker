#!/bin/bash
# simulates gps tracker reporting location every second (or pass int for longer interval)

LAT=0.518389
LON=25.205708
HEADING=270
SLEEP_INTERVAL="${1:-1}" 

API_KEY="test-key-123"
URL="http://localhost:8080/api/report.php"

TIME_RECORDED=$(date +%s)

while true; do
  read LAT LON HEADING <<< $(awk -v lat="$LAT" -v lon="$LON" -v heading="$HEADING" 'BEGIN {
    srand(systime() + PROCINFO["pid"] + int(rand()*100000));
    pi = 3.14159265;
    dist = 40 + rand() * 50;
    echo "dist";
    offset = (rand() * 60) - 30;
    new_heading = heading + offset;
    while (new_heading < 0)   new_heading += 360;
    while (new_heading >= 360) new_heading -= 360;
    rad = new_heading * pi / 180;
    dlat = (dist * cos(rad)) / 111320;
    dlon = (dist * sin(rad)) / (111320 * cos(lat * pi / 180));
    printf "%.8f %.8f %.4f", lat + dlat, lon + dlon, new_heading;
  }')
  
  # random 6-13secs ahead of the previous time_recorded, target avg around 25 km/h
  TIME_OFFSET=$(( (RANDOM % 8) + 6 ))
  TIME_RECORDED=$(( TIME_RECORDED + TIME_OFFSET ))

  curl -s -X POST "$URL" \
    -H "Content-Type: application/json" \
    -d "{\"api_key\":\"$API_KEY\",\"lat\":$LAT,\"lon\":$LON,\"time_recorded\":$TIME_RECORDED}" > /dev/null

  echo "lat $LAT, lon $LON time: $TIME_RECORDED"
  sleep $SLEEP_INTERVAL  # Use the variable here
done
