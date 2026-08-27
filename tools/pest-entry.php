<?php
// Entry shim: runs the Pest CLI under the php-wasm runtime.
//
// tools/artisan.mjs `require`s the target script, and PHP only strips a leading
// "#!" line from the *main* script - so requiring vendor/pestphp/pest/bin/pest
// directly dies with "strict_types declaration must be the very first statement".
// This shim copies the binary with the shebang removed and then requires it.
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '1536M');
set_time_limit(0);

$root = '/home/user/TOEFL-House/toefl-house-v3/server';
$bin  = $root . '/vendor/pestphp/pest/bin/pest';
chdir($root);

$src = file_get_contents($bin);
$src = preg_replace('/^#!.*\R/', '', $src, 1);
// The copy must stay inside bin/ : pest resolves its autoloader with
// dirname(__DIR__, 4), so moving it elsewhere breaks the vendor path.
$entry = dirname($bin) . '/.pest-entry-run.php';
file_put_contents($entry, $src);

$args = array_slice($_SERVER['argv'] ?? ['pest'], 1);
$_SERVER['argv'] = array_merge([$bin], $args);
$_SERVER['argc'] = count($_SERVER['argv']);
$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];

require $entry;
