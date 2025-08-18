#!/bin/bash

# Launch Chrome with autoplay enabled for Laravel Jeopardy
# Use this script to start the game for presentations

echo "Starting Laravel Jeopardy with autoplay enabled..."

# Kill any existing Chrome instances (optional)
# killall "Google Chrome" 2>/dev/null

# Launch Chrome with autoplay policy disabled
/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome \
  --autoplay-policy=no-user-gesture-required \
  --disable-features=PreloadMediaEngagementData,MediaEngagementBypassAutoplayPolicies \
  http://localhost:8000

# For Windows, use:
# start chrome.exe --autoplay-policy=no-user-gesture-required --start-fullscreen http://localhost:8000

# For Linux, use:
# google-chrome --autoplay-policy=no-user-gesture-required --start-fullscreen http://localhost:8000
