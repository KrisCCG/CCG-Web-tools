#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Was the exposed dashboard endpoint actually accessed?
#
# /calculator/api-dashboard.php served every customer order to anyone, with no
# authentication, from ~14 May 2026 until it was gated on 17 Aug 2026.
# This tells you whether anyone other than you actually pulled it — which is the
# difference between "exposed" and "breached".
#
# USAGE
#   1. Download the access logs covering 14 May – 17 Aug from the Infomaniak
#      Manager (Web Hosting → Logs), or copy them off the server.
#   2. Put them in one folder, e.g. ~/Desktop/logs/  (gzipped is fine)
#   3. ./check-dashboard-exposure.sh ~/Desktop/logs
#
# Add your own office/home IPs below so your own testing is filtered out.
# ─────────────────────────────────────────────────────────────────────────────

LOGDIR="${1:-.}"
MY_IPS="${MY_IPS:-}"          # e.g. MY_IPS='81.246.1.2|85.27.3.4' ./check-...

if [ ! -d "$LOGDIR" ]; then
  echo "Not a directory: $LOGDIR"; exit 1
fi

# zcat -f reads plain and gzipped alike.
read_logs() { find "$LOGDIR" -type f \( -name '*access*' -o -name '*.log*' \) -print0 \
                | xargs -0 zcat -f 2>/dev/null; }

echo "═══════════════════════════════════════════════════════════════"
echo " Dashboard exposure check — logs in: $LOGDIR"
echo "═══════════════════════════════════════════════════════════════"

TOTAL=$(read_logs | grep -c 'api-dashboard' )
echo
echo "Total requests to api-dashboard.php : ${TOTAL:-0}"

if [ "${TOTAL:-0}" -eq 0 ]; then
  echo
  echo "  NOTHING FOUND. Either nobody touched it, or these logs don't cover"
  echo "  the exposure window. Check the date range below before concluding."
  echo
  echo "First and last log entries seen:"
  read_logs | grep -oE '\[[0-9]{2}/[A-Za-z]{3}/[0-9]{4}' | sort -u | sed -n '1p;$p' | sed 's/^/  /'
  exit 0
fi

echo
echo "── Successful data pulls (HTTP 200 with ?action=orders) ───────"
read_logs | grep 'api-dashboard' | grep 'action=orders' | grep -E '" 200 ' \
  | awk '{print $2}' | sort | uniq -c | sort -rn | sed 's/^/  /'

echo
echo "── Every distinct client IP that touched it ───────────────────"
if [ -n "$MY_IPS" ]; then
  read_logs | grep 'api-dashboard' | awk '{print $2}' \
    | grep -vE "$MY_IPS" | sort | uniq -c | sort -rn | sed 's/^/  /'
  echo "  (your own IPs excluded: $MY_IPS)"
else
  read_logs | grep 'api-dashboard' | awk '{print $2}' | sort | uniq -c | sort -rn | sed 's/^/  /'
  echo
  echo "  NOTE: your own IPs are NOT excluded — set MY_IPS to filter them,"
  echo "        otherwise your own dashboard use looks like a third party."
fi

echo
echo "── User agents (crawler vs browser vs script) ─────────────────"
read_logs | grep 'api-dashboard' | grep -oE '"[^"]*"$' | sort | uniq -c | sort -rn | head -15 | sed 's/^/  /'

echo
echo "── Timeline by day ────────────────────────────────────────────"
read_logs | grep 'api-dashboard' | grep -oE '\[[0-9]{2}/[A-Za-z]{3}/[0-9]{4}' \
  | tr -d '[' | sort | uniq -c | sed 's/^/  /'

echo
echo "═══════════════════════════════════════════════════════════════"
echo " How to read this:"
echo "  - Only your own IPs + known crawlers  → exposed, not harvested."
echo "  - An unknown IP with 200s on ?action=orders → data was taken."
echo "  - Repeated hits from one IP over days → someone was watching it."
echo "═══════════════════════════════════════════════════════════════"
