// Builds server/vendor from tools/packages.json ({name: git-tag}) using GitHub
// tarballs, because packagist.org is unreachable from this sandbox.
//
// It writes vendor/<vendor>/<pkg>, vendor/composer/installed.json +
// installed.php and generates vendor/autoload.php around a real
// Composer\Autoload\ClassLoader — which is what Laravel's PackageManifest and
// Composer\InstalledVersions expect.
import fs from 'node:fs';
import path from 'node:path';
import { fetchPackage } from './pkg.mjs';

const SERVER_ROOT = '/home/user/TOEFL-House/toefl-house-v3/server';
const TOOLS = '/home/user/TOEFL-House/tools';
const PACKAGES_DIR = path.join(TOOLS, '.packages');
const RUNTIME_DIR = path.join(TOOLS, 'composer-runtime');
const VENDOR = path.join(SERVER_ROOT, 'vendor');

const manifestPath = process.argv[2] || path.join(TOOLS, 'packages.json');
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const entries = Object.entries(manifest);

fs.mkdirSync(PACKAGES_DIR, { recursive: true });
fs.mkdirSync(VENDOR, { recursive: true });

const installed = [];
const psr4 = {};
const psr0 = {};
const files = [];
const classmapDirs = [];
const errors = [];

for (const [name, tag] of entries) {
  const src = path.join(PACKAGES_DIR, name);
  try {
    if (!fs.existsSync(path.join(src, 'composer.json'))) {
      process.stderr.write(`fetch ${name} (${tag})\n`);
      fetchPackage(name, tag, PACKAGES_DIR);
    }
  } catch (e) {
    errors.push(`${name}@${tag}: ${e.message}`);
    continue;
  }

  const dest = path.join(VENDOR, name);
  fs.rmSync(dest, { recursive: true, force: true });
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.cpSync(src, dest, { recursive: true });
  fs.rmSync(path.join(dest, '.git'), { recursive: true, force: true });

  const meta = JSON.parse(fs.readFileSync(path.join(dest, 'composer.json'), 'utf8'));
  const rel = `vendor/${name}`;
  const a = meta.autoload || {};
  // composer allows "src/A/,src/B/" — each part is relative to the package root
  // "" means "the package root" (symfony components use it)
  const expand = (dir) => {
    const parts = String(dir).split(',').map((d) => d.trim().replace(/^\/+|\/+$/g, ''));
    const out = parts.filter(Boolean).map((d) => `${rel}/${d}`);
    return out.length ? out : [rel];
  };
  for (const [prefix, dir] of Object.entries(a['psr-4'] || {})) {
    for (const part of expand(dir)) (psr4[prefix] ||= []).push(part);
  }
  for (const [prefix, dir] of Object.entries(a['psr-0'] || {})) {
    for (const part of expand(dir)) (psr0[prefix] ||= []).push(part);
  }
  for (const f of a.files || []) files.push(`${rel}/${f}`);
  for (const c of a.classmap || []) classmapDirs.push({ abs: path.join(dest, c), rel: `${rel}/${c}` });

  installed.push({
    name,
    version: String(tag).replace(/^v/, ''),
    type: meta.type || 'library',
    autoload: a,
    extra: meta.extra || {},
    'installation-source': 'dist',
    description: meta.description || '',
  });
}

if (errors.length) process.stderr.write('\nFAILED:\n' + errors.join('\n') + '\n');

// ---- the application's own autoload section (App\, Database\Seeders\, ...) ----
{
  const root = JSON.parse(fs.readFileSync(path.join(SERVER_ROOT, 'composer.json'), 'utf8'));
  // Composer merges autoload-dev into the dev autoloader, so Tests\ -> tests/ has
  // to be registered too or PHPUnit/Pest cannot resolve Tests\TestCase.
  const a = {
    'psr-4': { ...(root.autoload || {})['psr-4'], ...(root['autoload-dev'] || {})['psr-4'] },
    files: [...((root.autoload || {}).files || []), ...((root['autoload-dev'] || {}).files || [])],
  };
  const expandRoot = (dir) => {
    const parts = String(dir).split(',').map((d) => d.trim().replace(/^\/+|\/+$/g, ''));
    const out = parts.filter(Boolean);
    return out.length ? out : ['.'];
  };
  for (const [prefix, dir] of Object.entries(a['psr-4'] || {})) {
    for (const part of expandRoot(dir)) (psr4[prefix] ||= []).push(part === '.' ? '.' : part);
  }
  for (const f of a.files || []) files.push(f);
}

// ---- composer autoloader runtime -------------------------------------------
const composerDir = path.join(VENDOR, 'composer');
fs.mkdirSync(composerDir, { recursive: true });
for (const f of ['ClassLoader.php', 'InstalledVersions.php']) {
  const from = path.join(RUNTIME_DIR, f);
  if (!fs.existsSync(from)) throw new Error(`missing ${from} — run tools/setup.sh first`);
  fs.copyFileSync(from, path.join(composerDir, f));
}

// ---- classmap ---------------------------------------------------------------
const classmap = {};
const mapClasses = (abs, rel) => {
  const src = fs.readFileSync(abs, 'utf8');
  const nsMatch = /^\s*namespace\s+([A-Za-z0-9_\\]+)\s*[;{]/m.exec(src);
  const ns = nsMatch ? nsMatch[1] + '\\' : '';
  const re = /^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/gm;
  let m;
  while ((m = re.exec(src))) classmap[ns + m[1]] = rel;
};
const walk = (abs, rel) => {
  if (!fs.existsSync(abs)) return;
  if (fs.statSync(abs).isFile()) {
    if (abs.endsWith('.php')) mapClasses(abs, rel);
    return;
  }
  for (const entry of fs.readdirSync(abs, { withFileTypes: true })) {
    if (entry.name === '.git') continue;
    walk(path.join(abs, entry.name), `${rel}/${entry.name}`);
  }
};
for (const c of classmapDirs) walk(c.abs, c.rel);

const phpLiteral = (value) => {
  if (Array.isArray(value)) return `[${value.map(phpLiteral).join(', ')}]`;
  if (value && typeof value === 'object') {
    return `[${Object.entries(value).map(([k, v]) => `${phpLiteral(k)} => ${phpLiteral(v)}`).join(', ')}]`;
  }
  if (typeof value === 'number') return String(value);
  if (typeof value === 'boolean') return value ? 'true' : 'false';
  if (value === null) return 'null';
  return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
};

// ---- installed.json / installed.php ----------------------------------------
fs.writeFileSync(
  path.join(composerDir, 'installed.json'),
  JSON.stringify({ packages: installed, dev: false, 'dev-package-names': [] }, null, 2),
);

const versions = {};
for (const p of installed) {
  versions[p.name] = {
    pretty_version: p.version,
    version: `${p.version.replace(/-.*/, '')}.0`,
    aliases: [],
    reference: null,
    replaced: [],
    provided: [],
    install_path: `../${p.name}`,
    type: p.type,
    dev_requirement: false,
  };
}
fs.writeFileSync(
  path.join(composerDir, 'installed.php'),
  `<?php\n\n// Generated by tools/build-laravel-vendor.mjs\nreturn ${phpLiteral({
    root: {
      name: 'toefl-house/v3-server',
      pretty_version: 'dev-main',
      version: 'dev-main',
      aliases: [],
      reference: null,
      type: 'project',
      install_path: '../../',
      dev_requirement: false,
    },
    versions,
  })};\n`,
);

// ---- the autoload_*.php stubs some tooling (e.g. PsySH/tinker) includes ----
const phpReturn = (file, value) => fs.writeFileSync(
  path.join(composerDir, file),
  `<?php\n\n// Generated by tools/build-laravel-vendor.mjs\nreturn ${phpLiteral(value)};\n`,
);
phpReturn('autoload_psr4.php', psr4);
phpReturn('autoload_namespaces.php', psr0);
phpReturn('autoload_classmap.php', classmap);
phpReturn('autoload_files.php', Object.fromEntries(files.map((f, i) => [String(i).padStart(40, '0'), f])));

// ---- autoload.php ----------------------------------------------------------
const phpData = (name, value) => `\$${name} = json_decode(<<<'TOEFLJSON'
${JSON.stringify(value)}
TOEFLJSON, true);
`;

fs.writeFileSync(path.join(VENDOR, 'autoload.php'), `<?php
// Generated by tools/build-laravel-vendor.mjs
// (stand-in for "composer dump-autoload": packagist.org is unreachable here)

require __DIR__ . '/composer/ClassLoader.php';
require __DIR__ . '/composer/InstalledVersions.php';

${phpData('psr4', psr4)}
${phpData('psr0', psr0)}
${phpData('classmap', classmap)}
${phpData('autoloadFiles', files)}

$loader = new Composer\\Autoload\\ClassLoader(__DIR__ . '/..');

foreach ($psr4 as $prefix => $dirs) {
    $loader->setPsr4($prefix, array_map(static fn ($d) => dirname(__DIR__) . '/' . $d, $dirs));
}
foreach ($psr0 as $prefix => $dirs) {
    $loader->set($prefix, array_map(static fn ($d) => dirname(__DIR__) . '/' . $d, $dirs));
}
$loader->addClassMap(array_map(static fn ($f) => dirname(__DIR__) . '/' . $f, $classmap));
$loader->register(true);

foreach ($autoloadFiles as $file) {
    $path = dirname(__DIR__) . '/' . $file;
    if (is_file($path)) require $path;
}

return $loader;
`);

process.stdout.write(
  `\nvendor ready: ${installed.length}/${entries.length} packages, `
  + `${Object.keys(psr4).length} psr-4 prefixes, ${Object.keys(classmap).length} classmap entries, ${files.length} files\n`,
);
if (errors.length) process.exitCode = 1;
