# Fix: "barryvdh/laravel-dompdf is not present in the lock file" + broken vendor

## Problem
You edited `composer.json` to add dompdf, but ran `composer install` (which uses the old `composer.lock`).
The lock file is out of sync → vendor is incomplete → `php artisan` dies with autoload errors.

## Exact Commands (copy-paste in PowerShell)

```powershell
# 1. Go to the server folder (use quotes because of space in path)
cd "C:\Users\Allah Yar Frotan\Videos\TOEFL House\toefl-house-v3\server"

# 2. DELETE the broken vendor + old lock file (this is safe)
Remove-Item -Recurse -Force vendor, composer.lock -ErrorAction SilentlyContinue

# 3. Force a full update (this will regenerate composer.lock with dompdf)
composer update

# 4. Install everything cleanly
composer install

# 5. Now the artisan commands should work
php artisan key:generate

# 6. Create a fresh database + seed all 10 roles + realistic data
php artisan migrate:fresh --seed --class=DatabaseSeeder

# 7. Start the backend
php artisan serve
```

## After `php artisan serve` succeeds

Open a **new** PowerShell window and start the frontend:

```powershell
cd "C:\Users\Allah Yar Frotan\Videos\TOEFL House\toefl-house-v3\client"
npm install
npm run dev
```

Then open http://localhost:5173

Use the **bottom-right Role Switcher** to test the 10 roles.

## Quick .env fix (recommended for Windows)

After copying `.env.example`, edit `.env` and make it use SQLite (easiest on Windows):

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Then create the database file:

```powershell
New-Item -ItemType Directory -Force database
New-Item -ItemType File -Force database/database.sqlite
```

## If you still get errors after the above

Run these diagnostics and paste the output:

```powershell
cd "C:\Users\Allah Yar Frotan\Videos\TOEFL House\toefl-house-v3\server"
dir vendor\composer\autoload_real.php
php --version
composer --version
```

## Why this happened
The project intentionally added `barryvdh/laravel-dompdf` in a recent cycle for real PDF certificate generation.
`composer update` (not just `install`) is required when `composer.json` changes.

