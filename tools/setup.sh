#!/bin/bash
# Idempotent setup for the sandbox runtime (safe to re-run after a restart):
export NODE_EXTRA_CA_CERTS=${NODE_EXTRA_CA_CERTS:-/usr/local/share/ca-certificates/e2b-ca.crt}
#   - PHP 8.4 (WebAssembly) runtime for the Laravel backend
#   - Composer's ClassLoader/InstalledVersions (used by the generated autoloader)
#   - client npm dependencies
set -e
TOOLS="/home/user/TOEFL-House/tools"
ROOT="/home/user/TOEFL-House/toefl-house-v3"

echo "[1/3] php-wasm runtime"
cd "$TOOLS"
cat > package.json <<'JSON'
{
  "name": "toefl-house-tools",
  "private": true,
  "version": "1.0.0",
  "type": "module",
  "dependencies": {
    "@php-wasm/node": "3.1.51",
    "@php-wasm/node-8-4": "3.1.51",
    "@php-wasm/universal": "3.1.51"
  }
}
JSON
if [ ! -d node_modules/@php-wasm/node-8-4 ]; then
  npm install --no-audit --no-fund --silent
fi

echo "[2/3] composer autoloader runtime"
mkdir -p "$TOOLS/composer-runtime"
fetch_raw() { # <repo-path> <dest>
  curl -sfL -H "accept: application/vnd.github.raw" \
    "https://api.github.com/repos/composer/composer/contents/$1?ref=2.8.5" -o "$2"
}
[ -s "$TOOLS/composer-runtime/ClassLoader.php" ] || fetch_raw src/Composer/Autoload/ClassLoader.php "$TOOLS/composer-runtime/ClassLoader.php"
[ -s "$TOOLS/composer-runtime/InstalledVersions.php" ] || fetch_raw src/Composer/InstalledVersions.php "$TOOLS/composer-runtime/InstalledVersions.php"
ls -l "$TOOLS/composer-runtime"

echo "[3/3] client dependencies"
cd "$ROOT/client"
if [ ! -d node_modules ]; then
  npm install --no-audit --no-fund --silent
fi

echo "setup complete"
