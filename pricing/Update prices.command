#!/bin/bash
# Double-click: checks the newest price Excel and prepares the folder "upload"
# (internal calculator + shop-v2). Nothing is uploaded by this script.
cd "$(dirname "$0")"
if ! command -v python3 >/dev/null 2>&1; then
  echo "Python 3 is not installed. Ask Claude for help."
  read -n 1 -s -r -p "Press any key to close."; exit 1
fi
python3 check_prices.py
echo
echo "Next: upload the content of the folder 'upload' onto /sites/clickclick.be/ (see upload/UPLOAD.txt)."
read -n 1 -s -r -p "Press any key to close this window."
