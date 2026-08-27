// Shared helpers: boot PHP 8.4 (php-wasm) with the host filesystem mounted.
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';

export const SERVER_ROOT = '/home/user/TOEFL-House/toefl-house-v3/server';

let pidCounter = 1000;

export async function createPhp({ quiet = false } = {}) {
  const php = new PHP(
    await loadNodeRuntime('8.4', { emscriptenOptions: { processId: ++pidCounter } }),
  );
  useHostFilesystem(php);
  if (!quiet) {
    php.onError = (e) => process.stderr.write(e.detail[0]);
  }
  return php;
}

// Wraps a PHP script so it behaves like a CLI invocation.
export function cliWrapper(script, args = []) {
  return `<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '1536M');
ini_set('max_execution_time', '0');
putenv('HOME=/home/user');
putenv('XDG_CONFIG_HOME=/home/user/.config');
putenv('XDG_DATA_HOME=/home/user/.local/share');
$_SERVER['HOME'] = '/home/user';
$_ENV['HOME'] = '/home/user';
foreach (['/home/user/.config/psysh', '/home/user/.local/share/psysh'] as $d) {
    if (!is_dir($d)) @mkdir($d, 0777, true);
}
$__argv = json_decode(<<<'TOEFLJSON'
${JSON.stringify([script, ...args])}
TOEFLJSON, true);
$_SERVER['argv'] = $__argv;
$_SERVER['argc'] = count($__argv);
$argv = $__argv;
$argc = count($__argv);
chdir(dirname($__argv[0]));
require $__argv[0];
`;
}

export const CLI_ENV = {
  HOME: '/home/user',
  APP_ENV: 'local',
  COMPOSER_ALLOW_SUPERUSER: '1',
};

export async function runPhp(php, code, { envVars = CLI_ENV } = {}) {
  const res = await php.runStream({ code, envVars });
  return {
    stdout: await res.stdoutText,
    stderr: await res.stderrText,
    exitCode: (await res.exitCode) ?? 0,
  };
}
