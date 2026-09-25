#!/bin/bash
# Publica sursele ebsv2 pe GitHub, cu POARTA DE SIGURANTA anti-secrete.
# Ruleaza-l TU:   ! bash /var/www/ebsv2/sync/git_publish.sh
#
# Face: git init -> add -> AUDIT secrete -> (daca e curat) commit + push.
# Daca gaseste ORICE secret in staging, se opreste si NU urca nimic.
set -e
cd /var/www/ebsv2

REMOTE="https://github.com/bogdannedelcu/hermes.git"

echo "### 1) init + branch main"
[ -d .git ] || git init
git symbolic-ref HEAD refs/heads/main 2>/dev/null || git checkout -B main 2>/dev/null || true

echo "### 2) stage (respecta .gitignore)"
git add -A

echo "### 2b) scoate repo-urile git INCORPORATE (gitlinks mode 160000) — s-ar comite stricat"
for sub in $(git ls-files --cached -s | awk '$1=="160000"{print $4}'); do
  echo "   embedded repo -> git rm --cached $sub"; git rm --cached "$sub" >/dev/null 2>&1 || true
done

echo "### 3) AUDIT anti-secrete (staging) — cai ancorate, case-sensitive"
LEAK=$(git ls-files --cached | grep -E \
 '(^|/)\.env$|^app/Config/Database\.php$|^sync/config\.php$|(^|/)\.apikey$|(^|/)\.distrib_apikey$|^sync/crontab.*\.txt$|^sync/cron\.txt$|^jobs/sqlQueue.*\.py$|^jobs/ebsCron\.sh$|(^|/)nohup\.out$' \
 || true)
if [ -n "$LEAK" ]; then
  echo "!!! OPRIT — urmatoarele fisiere sensibile ar fi urcate:"; echo "$LEAK"
  echo "Adauga-le in .gitignore si scoate-le din index (git rm --cached <fisier>). NU s-a urcat nimic."
  exit 1
fi
echo "    OK — niciun secret in staging."

echo "### 4) verificare dimensiune (fisiere > 5MB in staging)"
git ls-files --cached | while read f; do
  [ -f "$f" ] && sz=$(stat -c%s "$f" 2>/dev/null || echo 0) && [ "$sz" -gt 5242880 ] && echo "   MARE: $f ($((sz/1048576)) MB)"
done
echo "    (daca apare ceva mare/nedorit mai sus, opreste cu Ctrl-C si adauga in .gitignore)"

echo "### 5) commit"
git config user.email >/dev/null 2>&1 || git config user.email "bogdan.nedelcu@gmail.com"
git config user.name  >/dev/null 2>&1 || git config user.name  "Bogdan Nedelcu"
git commit -m "Initial import: EBS v2 (sync suite, raport orar, fix afisare + dropdown, sync distributie)" || echo "(nimic de comis)"

echo "### 6) remote (URL curat, FARA token) + push cu token efemer"
git remote get-url origin >/dev/null 2>&1 && git remote set-url origin "$REMOTE" || git remote add origin "$REMOTE"

# Token din ENV (GITHUB_TOKEN) sau fisier 0600 (~/.hermes_token).
# NU se salveaza in .git/config si NU se foloseste vreun credential helper.
TOKEN="${GITHUB_TOKEN:-$(cat "$HOME/.hermes_token" 2>/dev/null)}"
if [ -z "$TOKEN" ]; then
  echo "!! Lipseste tokenul. Pune-l in ~/.hermes_token (chmod 600) sau ruleaza cu:  GITHUB_TOKEN=... bash $0"
  exit 1
fi
echo "    -> push spre $REMOTE (token doar in aceasta comanda)"
git -c credential.helper= push "https://x-access-token:${TOKEN}@github.com/bogdannedelcu/hermes.git" main
git remote set-url origin "$REMOTE"   # ne asiguram ca origin ramane fara token

echo "### GATA. Verifica pe GitHub ca NU apar: .env, config.php, Database.php, *.apikey"
echo "### RECOMANDARE: tine repo-ul PRIVAT — parola DB si cheile API sunt in aplicatie."
