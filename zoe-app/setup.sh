<?php
// zoe-app/setup.sh
#!/bin/bash
echo "🤖 Zoe Bot Setup"
echo "================="

# Source .env
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -f "$SCRIPT_DIR/.env" ]; then
    export $(grep -v '^#' "$SCRIPT_DIR/.env" | xargs)
fi

TOKEN="${TELEGRAM_BOT_TOKEN}"
SECRET="${TELEGRAM_SECRET_TOKEN}"
WEBHOOK_URL="${APP_URL}/api/webhook.php"

if [ -z "$TOKEN" ]; then
    echo "❌ TELEGRAM_BOT_TOKEN not set in .env"
    exit 1
fi

echo "Setting webhook..."
PARAMS="url=${WEBHOOK_URL}"
if [ -n "$SECRET" ]; then
    PARAMS="${PARAMS}&secret_token=${SECRET}"
fi

curl -s "https://api.telegram.org/bot${TOKEN}/setWebhook?${PARAMS}" | python3 -m json.tool 2>/dev/null
echo ""
echo "✅ Done!"
