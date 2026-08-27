// Downloads PHP packages as GitHub tarballs (packagist.org is unreachable from
// this sandbox) and lays them out for the vendor builder.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

// package name -> GitHub repository (only where it differs from vendor/name)
const REPO_MAP = {
  'psr/log': 'php-fig/log',
  'psr/container': 'php-fig/container',
  'psr/clock': 'php-fig/clock',
  'psr/event-dispatcher': 'php-fig/event-dispatcher',
  'psr/http-client': 'php-fig/http-client',
  'psr/http-factory': 'php-fig/http-factory',
  'psr/http-message': 'php-fig/http-message',
  'psr/simple-cache': 'php-fig/simple-cache',
  'seld/jsonlint': 'Seldaek/jsonlint',
  'seld/phar-utils': 'Seldaek/phar-utils',
  'seld/signal-handler': 'Seldaek/signal-handler',
  'monolog/monolog': 'Seldaek/monolog',
  'react/promise': 'reactphp/promise',
  'nesbot/carbon': 'briannesbitt/Carbon',
  'carbonphp/carbon-doctrine-types': 'CarbonPHP/carbon-doctrine-types',
  'league/commonmark': 'thephpleague/commonmark',
  'league/config': 'thephpleague/config',
  'league/flysystem': 'thephpleague/flysystem',
  'league/flysystem-local': 'thephpleague/flysystem-local',
  'league/mime-type-detection': 'thephpleague/mime-type-detection',
  'league/uri': 'thephpleague/uri',
  'league/uri-interfaces': 'thephpleague/uri-interfaces',
  'guzzlehttp/guzzle': 'guzzle/guzzle',
  'guzzlehttp/promises': 'guzzle/promises',
  'guzzlehttp/psr7': 'guzzle/psr7',
  'guzzlehttp/uri-template': 'guzzle/uri-template',
  'egulias/email-validator': 'egulias/EmailValidator',
  'phpoption/phpoption': 'schmittjoh/php-option',
  'webmozart/assert': 'webmozarts/assert',
  'dasprid/enum': 'DASPRiD/Enum',
  'nikic/php-parser': 'nikic/PHP-Parser',
  'psy/psysh': 'bobthecow/psysh',
  'masterminds/html5': 'Masterminds/html5-php',
  'sabberworm/php-css-parser': 'MyIntervals/PHP-CSS-Parser',
  'symfony/css-selector': 'symfony/css-selector',
  'nette/utils': 'nette/utils',
  'nette/schema': 'nette/schema',
  'dflydev/dot-access-data': 'dflydev/dflydev-dot-access-data',
  'graham-campbell/result-type': 'GrahamCampbell/Result-Type',
  'tijsverkoyen/css-to-inline-styles': 'tijsverkoyen/CssToInlineStyles',
  'thecodingmachine/safe': 'thecodingmachine/safe',
  'doctrine/lexer': 'doctrine/lexer',
  'ralouphie/getallheaders': 'ralouphie/getallheaders',
  'dompdf/php-font-lib': 'dompdf/php-font-lib',
  'dompdf/php-svg-lib': 'dompdf/php-svg-lib',
  // PHPUnit's ecosystem lives under the sebastianbergmann GitHub org, and
  // deep-copy's repo is capitalised - the vendor/pkg default misses both.
  'phpunit/phpunit': 'sebastianbergmann/phpunit',
  'phpunit/php-code-coverage': 'sebastianbergmann/php-code-coverage',
  'phpunit/php-file-iterator': 'sebastianbergmann/php-file-iterator',
  'phpunit/php-invoker': 'sebastianbergmann/php-invoker',
  'phpunit/php-text-template': 'sebastianbergmann/php-text-template',
  'phpunit/php-timer': 'sebastianbergmann/php-timer',
  'sebastian/cli-parser': 'sebastianbergmann/cli-parser',
  'sebastian/code-unit': 'sebastianbergmann/code-unit',
  'sebastian/code-unit-reverse-lookup': 'sebastianbergmann/code-unit-reverse-lookup',
  'sebastian/comparator': 'sebastianbergmann/comparator',
  'sebastian/complexity': 'sebastianbergmann/complexity',
  'sebastian/diff': 'sebastianbergmann/diff',
  'sebastian/environment': 'sebastianbergmann/environment',
  'sebastian/exporter': 'sebastianbergmann/exporter',
  'sebastian/global-state': 'sebastianbergmann/global-state',
  'sebastian/lines-of-code': 'sebastianbergmann/lines-of-code',
  'sebastian/object-enumerator': 'sebastianbergmann/object-enumerator',
  'sebastian/object-reflector': 'sebastianbergmann/object-reflector',
  'sebastian/recursion-context': 'sebastianbergmann/recursion-context',
  'sebastian/type': 'sebastianbergmann/type',
  'sebastian/version': 'sebastianbergmann/version',
  'myclabs/deep-copy': 'myclabs/DeepCopy',
};

export function repoFor(name) {
  if (REPO_MAP[name]) return REPO_MAP[name];
  const [vendor, pkg] = name.split('/');
  const orgs = {
    composer: 'composer', symfony: 'symfony', laravel: 'laravel',
    illuminate: 'laravel/framework', league: 'thephpleague', guzzlehttp: 'guzzle',
    psr: 'php-fig', doctrine: 'doctrine', ramsey: 'ramsey',
  };
  if (orgs[vendor]) return `${orgs[vendor]}/${pkg}`;
  return `${vendor}/${pkg}`;
}

export function download(repo, tag, destDir) {
  fs.rmSync(destDir, { recursive: true, force: true });
  fs.mkdirSync(destDir, { recursive: true });
  const url = `https://codeload.github.com/${repo}/tar.gz/refs/tags/${encodeURIComponent(tag)}`;
  execFileSync('bash', ['-c', `curl -sfL ${JSON.stringify(url)} | tar xz --strip-components=1 -C ${JSON.stringify(destDir)}`], {
    stdio: ['ignore', 'inherit', 'inherit'],
    timeout: 300000,
  });
  if (!fs.existsSync(path.join(destDir, 'composer.json'))) {
    throw new Error(`${repo}@${tag} has no composer.json at ${destDir}`);
  }
  return path.join(destDir, 'composer.json');
}

export function fetchPackage(name, tagOrVersion, destRoot) {
  const repo = repoFor(name);
  const dest = path.join(destRoot, name);
  const metaPath = download(repo, tagOrVersion, dest);
  const meta = JSON.parse(fs.readFileSync(metaPath, 'utf8'));
  meta.__fetched = { repo, tag: tagOrVersion };
  fs.writeFileSync(metaPath, JSON.stringify(meta, null, 4));
  return { name, repo, tag: tagOrVersion, dest, meta };
}
