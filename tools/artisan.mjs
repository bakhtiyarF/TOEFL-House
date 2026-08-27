#!/usr/bin/env node
// Runs a PHP CLI script (artisan, ...) through the php-wasm runtime.
//   node tools/artisan.mjs migrate --force
import path from 'node:path';
import { createPhp, cliWrapper, runPhp, SERVER_ROOT } from './php-core.mjs';

const [target, ...args] = process.argv.slice(2);
if (!target) {
  console.error('usage: artisan.mjs <artisan-subcommand|script.php> [args...]');
  process.exit(2);
}

const script = target.endsWith('.php') || target.startsWith('/')
  ? path.resolve(target)
  : path.join(SERVER_ROOT, 'artisan');
const cliArgs = target.endsWith('.php') || target.startsWith('/') ? args : [target, ...args];

const php = await createPhp();
const { stdout, stderr, exitCode } = await runPhp(php, cliWrapper(script, cliArgs));
if (stdout) process.stdout.write(stdout.endsWith('\n') ? stdout : stdout + '\n');
if (stderr) process.stderr.write(stderr.endsWith('\n') ? stderr : stderr + '\n');
process.exit(exitCode || 0);
