#!/bin/bash

# Setup Laravel Scheduler Cron Job
echo "Setting up Laravel Scheduler..."

# Add Laravel scheduler to crontab
(crontab -l 2>/dev/null; echo "* * * * * cd /home/ambrian/signal-bot && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo "✅ Laravel Scheduler cron job added!"
echo "📅 Crypto analysis will run every 12 minutes"
echo "📱 Telegram notifications will be sent when signals are found"
echo ""
echo "To check cron jobs: crontab -l"
echo "To remove cron jobs: crontab -r"
echo "To view logs: tail -f /home/ambrian/signal-bot/storage/logs/crypto_analysis.log"
