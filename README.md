# Zoe Assistant

Personal AI assistant — Telegram bot + Web dashboard.

## Stack

- PHP 8.2 / MySQL / MariaDB
- DeepSeek AI API
- Telegram Bot API
- Hostinger Shared Hosting

## Structure

```
shared/           ← Code shared between bot and dashboard
├── includes/     ← db.php, deepseek.php, helpers.php, logger.php
└── sql/          ← schema.sql (unified)

zoe-app/          ← Telegram bot
├── includes/     ← telegram.php
├── public/api/   ← webhook.php
└── cron/         ← reminders.php

zoe-dashboard/    ← Web dashboard
├── includes/     ← auth.php, layout.php
├── public/       ← login.php, dashboard/
└── setup_admin.php
```

## Setup

1. `cp .env.example .env` and fill in values
2. `mysql -u user -p dbname < shared/sql/schema.sql`
3. `php zoe-dashboard/setup_admin.php --username=admin --password=YourPassword --name="Admin"`
4. Set Telegram webhook: `bash zoe-app/setup.sh`

## Security

- No secrets in repo (`.env` is gitignored)
- CSRF protection on all forms
- Session cookie: Secure + HttpOnly + SameSite=Lax
- Password hashing with `password_hash()`
- Telegram webhook secret token verification
- SQL injection prevention (prepared statements only)
- Rate limiting on bot messages

## License

Private — A-Mansour.com
