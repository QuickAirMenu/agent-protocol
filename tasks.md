# Zoe Assistant — Remaining Tasks

## 1. Cron Job Setup
- Register at https://cron-job.org
- URL: `https://zoe.a-mansour.com/cron/caller.php?secret=zoe_cron_2026`
- Schedule: Every 1 minute

## 2. Rotate Exposed Credentials
These credentials were exposed in git history and need rotation:
- **Bot Token:** (was exposed — revoke via @BotFather)
- **DB Password:** (was exposed — change in MariaDB)
- **DeepSeek API Key:** (was exposed — regenerate at https://platform.deepseek.com)

### How to rotate:
1. **Bot Token:** Message @BotFather → `/revoke` → new token → update `.env`
2. **DB Password:** Change in MariaDB + update `.env`
3. **DeepSeek Key:** Generate new key at https://platform.deepseek.com → update `.env`
