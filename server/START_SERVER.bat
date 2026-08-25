@echo off
echo ==========================================
echo   TOEFL House ERP v3 — XAMPP MySQL Starter
echo ==========================================
cd /d %~dp0

echo.
echo [1/6] Checking .env for MySQL...
if not exist .env (
    echo .env missing — copying template
    copy .env.mysql-xampp .env >nul
)

echo.
echo [2/6] Ensuring APP_KEY...
php artisan key:generate --force

echo.
echo [3/6] Creating required folders...
if not exist "storage\app\public\reports" mkdir "storage\app\public\reports"
if not exist "storage\app\reports" mkdir "storage\app\reports"

echo.
echo [4/6] Linking storage...
php artisan storage:link

echo.
echo [5/6] Running migrations + seeding (live MySQL)...
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder

echo.
echo [6/6] Starting Laravel dev server...
echo.
echo IMPORTANT: Keep this window open!
echo Frontend must be running separately: cd ..\client && npm run dev
echo.
php artisan serve --host=127.0.0.1 --port=8000

pause
