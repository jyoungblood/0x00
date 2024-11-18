#!/bin/bash

# call a url every 5 seconds, control batch process on remote site


while true; do
    echo "$(date) - Calling geolocation-populate..."
    /usr/bin/curl -s "https://XXXX.XXXX.XXXX/geolocation-populate"
    echo -e "\n----------------------------------------"
    sleep 5
done


# to run as a bg process: nohup ./geolocation-populate.sh > output.log 2>&1 &
# to kill: kill $pid