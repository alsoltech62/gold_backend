@echo off
cd /d "%~dp0"
echo ===================================================
echo Starting Daily Cron Jobs - %date% %time%
echo ===================================================

echo [1/2] Running Daily Notifications...
php api\cron\daily_notifications.php

echo.
echo [2/2] Running SIP Processing...
php api\cron\process_sip.php

echo.
echo ===================================================
echo Cron Jobs Completed - %date% %time%
echo ===================================================
