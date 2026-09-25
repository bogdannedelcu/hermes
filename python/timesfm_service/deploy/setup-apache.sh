#!/usr/bin/env bash
# Optional: expose the Chronos-2 microservice via Apache at /timesfm/*.
# PHP (V4Algorithm.php) already talks to 127.0.0.1:8081 directly, so this proxy
# is ONLY needed if you want to call the service from a browser or from another
# host on the LAN.
#
# Run with sudo:  sudo bash deploy/setup-apache.sh

set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Trebuie rulat ca root: sudo bash $0" >&2
    exit 1
fi

CONF=/etc/apache2/sites-available/ebsv2.conf
BACKUP="${CONF}.bak.$(date +%Y%m%d_%H%M%S)"

if [ ! -f "$CONF" ]; then
    echo "Nu gasesc $CONF — abort." >&2
    exit 1
fi

cp "$CONF" "$BACKUP"
echo "Backup: $BACKUP"

a2enmod proxy proxy_http rewrite 2>&1 | grep -v "already enabled" || true

if grep -q "<Location /timesfm>" "$CONF"; then
    echo "Location /timesfm exista deja in $CONF — nu modific."
else
    sed -i '/<\/VirtualHost>/i \
\
      # Chronos-2 forecasting microservice (FastAPI on 127.0.0.1:8081)\
      # Acces via http://192.168.1.95:8080/timesfm/health, /timesfm/forecast, etc.\
      # NB: PHP talks to 127.0.0.1:8081 direct; this proxy is for browser/external use only.\
      <Location /timesfm>\
          ProxyPass        http://127.0.0.1:8081\
          ProxyPassReverse http://127.0.0.1:8081\
          Require ip 127.0.0.1 192.168.1.0/24\
      </Location>' "$CONF"
    echo "Adaugat <Location /timesfm> in $CONF"
fi

apache2ctl configtest
systemctl reload apache2

echo ""
echo "Gata. Testeaza cu:"
echo "  curl -i http://192.168.1.95:8080/timesfm/health"
