#!/bin/bash
# Ruleaza tot sync-ul de portofoliu in ordinea de referinta (fara citiri).
# Ordinea conteaza: fiecare entitate referentiaza precedenta.
PHP=/usr/bin/php8.2
DIR=/var/www/ebsv2/sync
echo "########## SYNC RUN $(date '+%Y-%m-%d %H:%M:%S') ##########"
$PHP "$DIR/sync_clienti.php"   || echo "!! clienti a esuat"
$PHP "$DIR/sync_locuri.php"    || echo "!! locuri a esuat"
$PHP "$DIR/sync_contracte.php" || echo "!! contracte a esuat"
$PHP "$DIR/sync_facturi.php"   || echo "!! facturi a esuat"
$PHP "$DIR/sync_documente.php" || echo "!! documente a esuat"
echo "########## SYNC END $(date '+%H:%M:%S') ##########"
