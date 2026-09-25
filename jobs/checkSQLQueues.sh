#!/bin/bash

var="$(ps aux | grep sqlQueue.py | grep -v grep | wc -l)"

if (( $var >= 8 )); then
   now=$(date)
   echo "$now SQL Jobs Running..." >> /var/www/ebs/writable/logs/checkQueues.log
else
   cd /var/www/ebs/jobs/
   ./startQueue.sh

   now=$(date)
   echo "$now SQL Jobs Started..." >> /var/www/ebs/writable/logs/checkQueues.log 
fi
