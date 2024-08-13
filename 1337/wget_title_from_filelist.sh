#!/bin/bash
while read -r URL; do
    wget -q -O - "$URL" | \
       tr "\n" " " | \
       sed 's|.*<title>\([^<]*\).*</head>.*|\1|;s|^\s*||;s|\s*$||'
       echo -n " --> $URL"
    echo
done

