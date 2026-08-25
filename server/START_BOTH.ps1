Write-Host "=== TOEFL House ERP v3 - Full Stack Starter ===" -ForegroundColor Cyan

# === BACKEND SETUP ===
cd server

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

New-Item -ItemType Directory -Force database | Out-Null
New-Item -ItemType File -Force database/database.sqlite | Out-Null

Write-Host "`n[Backend] .env configured for SQLite" -ForegroundColor Green

php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder

Write-Host "`n[Backend] Database ready with 10 roles + live data" -ForegroundColor Green

Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; php artisan serve"

Write-Host "`n[Backend] Starting on http://localhost:8000 ..." -ForegroundColor Cyan

# === FRONTEND ===
cd ../client

npm run dev
