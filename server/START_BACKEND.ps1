# Run this from the server folder in PowerShell
Write-Host "=== TOEFL House ERP - Backend Starter ===" -ForegroundColor Cyan

# Ensure correct .env (SQLite)
@"
APP_NAME="TOEFL House ERP"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Kabul
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
"@ | Set-Content .env -Encoding UTF8

# Create DB file
New-Item -ItemType Directory -Force database | Out-Null
New-Item -ItemType File -Force database/database.sqlite | Out-Null

Write-Host "`n[1/4] .env set to SQLite" -ForegroundColor Green

# Migrate
Write-Host "`n[2/4] Running migrations..." -ForegroundColor Yellow
php artisan migrate --force

# Seed
Write-Host "`n[3/4] Seeding realistic data (10 roles + students + certs + donations...)" -ForegroundColor Yellow
php artisan db:seed --class=DatabaseSeeder

Write-Host "`n[4/4] Starting Laravel server..." -ForegroundColor Green
Write-Host "Backend will be at http://localhost:8000" -ForegroundColor Cyan
Write-Host "Keep this window open!" -ForegroundColor Yellow
Write-Host ""

php artisan serve
