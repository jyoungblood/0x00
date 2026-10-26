#!/bin/bash
set -euo pipefail

SSH_USER="example"
SSH_SERVER="0.0.0.0"
SSH_PORT="2200"
LOG_PATH="/home/example/public_html/storage/logs/laravel.log"

# Show recent errors and keep following the log if it is recreated or rotated.
exec ssh -p "$SSH_PORT" -t "$SSH_USER@$SSH_SERVER" "tail -n 200 -F '$LOG_PATH'"
