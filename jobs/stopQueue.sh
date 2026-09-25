#!/bin/sh

pkill -f "python3 sqlQueue.py"
rm  /var/www/ebs/writable/logs/sqlQueue*
