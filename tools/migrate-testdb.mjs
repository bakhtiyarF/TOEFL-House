#!/usr/bin/env node
// Builds the dedicated test database once, so PHPUnit does not have to run the
// migrations in-process (the php-wasm asyncify stack cannot take that depth).
//
// The env vars are injected inside the PHP process itself: passing them to
// runStream() alone does not reach Laravel's env() once .env has been loaded.
import path from 'node:path';
import { createPhp, cliWrapper, runPhp, CLI_ENV, SERVER_ROOT } from './php-core.mjs';

const db = process.env.TEST_DB || '/tmp/testdb.sqlite';
const inject = ['DB_CONNECTION=sqlite', `DB_DATABASE=${db}`]
  .map((kv) => {
    const [k, v] = kv.split('=');
    return `putenv('${k}=${v}'); $_ENV['${k}'] = '${v}'; $_SERVER['${k}'] = '${v}';`;
  })
  .join('\n');

const php = await createPhp();
const inner = cliWrapper(path.join(SERVER_ROOT, 'artisan'), ['migrate:fresh', '--force'])
  .replace(/^<\?php\n/, '');
const { stdout, stderr, exitCode } = await runPhp(
  php,
  `<?php\n${inject}\n${inner}`,
  { envVars: CLI_ENV },
);
process.stdout.write(stdout);
if (stderr) process.stderr.write(stderr);
console.error(`\n[testdb] ${db} -> exit ${exitCode}`);
process.exit(exitCode || 0);
