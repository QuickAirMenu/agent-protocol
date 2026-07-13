#!/bin/bash
# Zoe Assistant — Deploy Script
# Usage: bash deploy.sh

set -e

echo "🚀 Deploying Zoe Assistant..."

# Check .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Copy .env.example to .env first."
    exit 1
fi

# Server config
SERVER="a-mansour.com"
USER="u227212075"
SSH_PORT=65002

# zoe-app deployment
echo "📦 Deploying zoe-app..."
ssh -p $SSH_PORT $USER@$SERVER "mkdir -p ~/domains/a-mansour.com/zoe-app/{public/api,cron,data,includes}"
scp -P $SSH_PORT -r shared/ $USER@$SERVER:~/domains/a-mansour.com/zoe-app/
scp -P $SSH_PORT zoe-app/includes/*.php $USER@$SERVER:~/domains/a-mansour.com/zoe-app/includes/
scp -P $SSH_PORT zoe-app/public/api/*.php $USER@$SERVER:~/domains/a-mansour.com/zoe-app/public/api/
scp -P $SSH_PORT zoe-app/cron/*.php $USER@$SERVER:~/domains/a-mansour.com/zoe-app/cron/
scp -P $SSH_PORT .env $USER@$SERVER:~/domains/a-mansour.com/zoe-app/.env

# zoe-dashboard deployment
echo "📦 Deploying zoe-dashboard..."
ssh -p $SSH_PORT $USER@$SERVER "mkdir -p ~/domains/zoe.a-mansour.com/public_html/{dashboard,assets/css,assets/js,includes}"
scp -P $SSH_PORT shared/ $USER@$SERVER:~/domains/zoe.a-mansour.com/public_html/
scp -P $SSH_PORT zoe-dashboard/includes/*.php $USER@$SERVER:~/domains/zoe.a-mansour.com/public_html/includes/
scp -P $SSH_PORT zoe-dashboard/public/* $USER@$SERVER:~/domains/zoe.a-mansour.com/public_html/
scp -P $SSH_PORT -r zoe-dashboard/public/assets/ $USER@$SERVER:~/domains/zoe.a-mansour.com/public_html/
scp -P $SSH_PORT .env $USER@$SERVER:~/domains/zoe.a-mansour.com/public_html/.env

# Init database
echo "🗄️ Initializing database..."
ssh -p $SSH_PORT $USER@$SERVER "mysql -u u227212075_zoe_assistant -p'$(grep DB_PASS .env | cut -d= -f2)' u227212075_zoe_assistant < ~/domains/zoe.a-mansour.com/public_html/shared/sql/schema.sql 2>/dev/null || true"

echo "✅ Deploy complete!"
echo ""
echo "Dashboard: https://zoe.a-mansour.com/login"
echo "Bot: https://a-mansour.com/zoe-app/"
