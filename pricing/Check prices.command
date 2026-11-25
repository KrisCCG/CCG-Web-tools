#!/bin/bash
# Double-click to check the newest price file. Nothing is uploaded.
cd "$(dirname "$0")"
if ! command -v python3 >/dev/null 2>&1; then
  echo "Python 3 is not installed. Ask Claude for help."
  read -n 1 -s -r -p "Press any key to close."; exit 1
fi
python3 check_prices.py
echo
read -n 1 -s -r -p "Press any key to close this window."
