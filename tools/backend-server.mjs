// HTTP front-end for the Laravel backend.
//
// There is no native PHP in this sandbox, so requests are served by PHP 8.4
// running in WebAssembly (php-wasm) with the host filesystem mounted. Every
// request goes through Laravel's real HTTP kernel (bootstrap/app.php +
// Kernel::handle), so routing, middleware, Sanctum, Eloquent and the API
// resources are the genuine application code.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { createPhp, runPhp, SERVER_ROOT } from './php-core.mjs';

const PORT = Number(process.env.PORT || 8000);
const POOL_SIZE = Number(process.env.PHP_POOL || 2);
const FRESH_PER_REQUEST = process.env.PHP_FRESH === '1';
const MARKER = '__TOEFL_RESPONSE__';

const pool = [];
const waiters = [];

async function acquire() {
  const php = pool.pop();
  if (php) return php;
  if (FRESH_PER_REQUEST) return createPhp({ quiet: true });
  return new Promise((resolve) => waiters.push(resolve));
}

function release(php) {
  if (FRESH_PER_REQUEST) return;
  const waiter = waiters.shift();
  if (waiter) waiter(php);
  else pool.push(php);
}

function frontController(payload) {
  return `<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '0');
$__in = json_decode(<<<'TOEFLJSON'
${JSON.stringify(payload)}
TOEFLJSON, true);

$base = ${JSON.stringify(SERVER_ROOT)};

$server = ['REMOTE_ADDR' => '127.0.0.1', 'SERVER_PROTOCOL' => 'HTTP/1.1'];
foreach (($__in['headers'] ?? []) as $name => $value) {
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    $server[$key] = is_array($value) ? implode(', ', $value) : $value;
    if (strtolower($name) === 'content-type') $server['CONTENT_TYPE'] = $value;
    if (strtolower($name) === 'content-length') $server['CONTENT_LENGTH'] = $value;
}
$server['REQUEST_METHOD'] = $__in['method'];
$server['REQUEST_URI'] = $__in['uri'];
$server['SCRIPT_NAME'] = '/index.php';
$server['SCRIPT_FILENAME'] = $base . '/public/index.php';
$server['PHP_SELF'] = '/index.php';
if (!empty($__in['https'])) $server['HTTPS'] = 'on';

$_SERVER = $server;
$_GET = [];
$_POST = [];
$_COOKIE = $__in['cookies'] ?? [];
$_FILES = [];
$_REQUEST = [];

$loader = include $base . '/vendor/autoload.php';
$app = include $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Http\\Kernel::class);

$content = isset($__in['body']) ? base64_decode($__in['body']) : null;
$request = Illuminate\\Http\\Request::create(
    $__in['uri'],
    $__in['method'],
    [],
    $_COOKIE,
    [],
    $server,
    $content
);

$response = $kernel->handle($request);

if (is_string($response->getContent())) {
    $body = $response->getContent();
} else {
    ob_start();
    $response->sendContent();
    $body = (string) ob_get_clean();
}

$headers = $response->headers->allPreserveCaseWithoutCookies();
$cookies = [];
foreach ($response->headers->getCookies() as $cookie) {
    $parts = [rawurlencode($cookie->getName()) . '=' . rawurlencode((string) $cookie->getValue())];
    if ($cookie->getExpiresTime()) $parts[] = 'Expires=' . gmdate('D, d M Y H:i:s', $cookie->getExpiresTime()) . ' GMT';
    if ($cookie->getMaxAge()) $parts[] = 'Max-Age=' . $cookie->getMaxAge();
    $parts[] = 'Path=' . $cookie->getPath();
    if ($cookie->getDomain()) $parts[] = 'Domain=' . $cookie->getDomain();
    if ($cookie->isSecure()) $parts[] = 'Secure';
    if ($cookie->isHttpOnly()) $parts[] = 'HttpOnly';
    if ($cookie->getSameSite()) $parts[] = 'SameSite=' . $cookie->getSameSite();
    $cookies[] = implode('; ', $parts);
}

// (terminable middleware runs after the response has been emitted, see below)

echo "\\n${MARKER}" . json_encode([
    'status' => $response->getStatusCode(),
    'headers' => $headers,
    'cookies' => $cookies,
    'body' => base64_encode($body),
]);

try {
    $kernel->terminate($request, $response);
} catch (Throwable $terminateError) {
    error_log('terminate failed: ' . $terminateError->getMessage());
}
`;
}

function parseCookies(header) {
  const out = {};
  if (!header) return out;
  for (const part of header.split(';')) {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  }
  return out;
}

function serveStatic(res, relPath) {
  const root = path.join(SERVER_ROOT, 'storage/app/public');
  const file = path.join(root, relPath);
  if (!file.startsWith(root) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {
    res.writeHead(404, { 'content-type': 'text/plain' });
    res.end('not found');
    return;
  }
  const type = file.endsWith('.png') ? 'image/png'
    : file.endsWith('.jpg') || file.endsWith('.jpeg') ? 'image/jpeg'
    : file.endsWith('.svg') ? 'image/svg+xml'
    : file.endsWith('.pdf') ? 'application/pdf'
    : 'application/octet-stream';
  res.writeHead(200, { 'content-type': type, 'cache-control': 'public, max-age=60' });
  fs.createReadStream(file).pipe(res);
}

const server = http.createServer(async (req, res) => {
  const url = req.url || '/';
  if (url.startsWith('/storage/')) return serveStatic(res, decodeURIComponent(url.slice(9)));

  const chunks = [];
  for await (const chunk of req) chunks.push(chunk);
  const raw = Buffer.concat(chunks);

  const payload = {
    method: req.method || 'GET',
    uri: url,
    headers: req.headers,
    cookies: parseCookies(req.headers.cookie),
    body: raw.length ? raw.toString('base64') : null,
    https: String(req.headers['x-forwarded-proto'] || '') === 'https',
  };

  if (process.env.DEBUG_COOKIES) {
    console.error(`[debug] ${url} cookie-header=${JSON.stringify(req.headers.cookie)} parsed=${JSON.stringify(Object.keys(payload.cookies))}`);
  }

  const started = Date.now();
  const php = await acquire();
  try {
    const { stdout, stderr, exitCode } = await runPhp(php, frontController(payload));
    const at = stdout.lastIndexOf(MARKER);
    if (at !== -1) {
      const decoded = JSON.parse(stdout.slice(at + MARKER.length));
      const headers = {};
      for (const [k, v] of Object.entries(decoded.headers || {})) {
        headers[k.toLowerCase()] = Array.isArray(v) ? v.join(', ') : v;
      }
      delete headers['transfer-encoding'];
      delete headers['content-length'];
      for (const c of decoded.cookies || []) {
        headers['set-cookie'] = headers['set-cookie'] ? [].concat(headers['set-cookie'], c) : c;
      }
      const body = Buffer.from(decoded.body || '', 'base64');
      headers['content-length'] = body.length;
      headers['x-toefl-runtime'] = `php-wasm ${Date.now() - started}ms`;
      res.writeHead(decoded.status, headers);
      res.end(body);
      const authSrc = req.headers.authorization
        ? 'bearer'
        : (payload.cookies['toefl_house_erp_session'] ? 'cookie' : 'none');
      console.log(
        `${req.method} ${url} -> ${decoded.status} (${Date.now() - started}ms)` +
        ` auth=${authSrc} origin=${req.headers.origin || '-'}` +
        ` ua="${String(req.headers['user-agent'] || '').slice(0, 45)}"`
      );
      return;
    }
    console.error(`no response marker for ${url} (exit ${exitCode})\nstdout: ${stdout.slice(0, 4000)}\nstderr: ${stderr.slice(0, 4000)}`);
    res.writeHead(500, { 'content-type': 'text/plain; charset=utf-8' });
    res.end(`Laravel produced no response.\n--- stdout ---\n${stdout}\n--- stderr ---\n${stderr}`);
  } catch (e) {
    console.error(e);
    res.writeHead(500, { 'content-type': 'text/plain; charset=utf-8' });
    res.end('backend error: ' + (e?.stack || e?.message || String(e)));
  } finally {
    release(php);
  }
});

for (let i = 0; i < POOL_SIZE; i++) pool.push(await createPhp({ quiet: true }));
console.log(`Laravel (php-wasm) listening on http://0.0.0.0:${PORT} — pool ${FRESH_PER_REQUEST ? 'fresh-per-request' : POOL_SIZE}`);
server.listen(PORT, '0.0.0.0');
