#!/bin/bash

if [ -z "$1" ]
  then
    echo "No node number supplied - sayip.sh <node> "
    exit 1
fi

cat /var/lib/asterisk/sounds/letters/i.ulaw /var/lib/asterisk/sounds/letters/p.ulaw /var/lib/asterisk/sounds/address.ulaw > /tmp/ip.ulaw
asterisk -rx "rpt playback $1 /tmp/ip"

for i in $(ip link show | grep " UP " | grep -v lo | grep -v "link/ether" | awk '{print $2}') ; do

        DEVICE=${i/:/}

        ip=$(ip addr show $DEVICE | awk '/inet / {print $2}' | awk 'BEGIN { FS = "/"}  {print $1}')

        sleep 3
        /usr/local/sbin/speaktext.sh $ip $1
done

rm /tmp/ip.ulaw
