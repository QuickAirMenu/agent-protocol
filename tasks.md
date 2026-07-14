# Zoe Assistant — Remaining Tasks

## 1. Cron Job Setup
- Register at https://cron-job.org
- URL: `https://zoe.a-mansour.com/cron/caller.php?secret=zoe_cron_2026`
- Schedule: Every 1 minute

## 2. Rotate Exposed Credentials
These credentials were exposed in git history and need rotation:
- **Bot Token:** `8843357099:AAESNs6q7ZeQBPWehmYtU-UacjzwAE7ikfs`
- **DB Password:** `Admin#4Ksa`
- **DeepSeek API Key:** `sk-7be6a1b961c04223a15e7fcd6638d2e5`

### How to rotate:
1. **Bot Token:** Message @BotFather → `/revoke` → new token → update `.env`
2. **DB Password:** Change in MariaDB + update `.env`
3. **DeepSeek Key:** Generate new key at https://platform.deepseek.com → update `.env`
