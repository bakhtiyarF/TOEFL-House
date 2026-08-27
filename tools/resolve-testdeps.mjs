#!/usr/bin/env node
// Resolves the PHP test toolchain (PHPUnit 11 + Pest 3 + Mockery + Faker) to
// concrete GitHub tags and appends them to a manifest the vendor builder can
// consume. packagist.org is unreachable here, so tags come from api.github.com.
import fs from 'node:fs';
import { execFileSync } from 'node:child_process';

// name -> [repo, wanted tag prefix]
const WANT = {
  'phpunit/phpunit':                    ['sebastianbergmann/phpunit', '11.5'],
  'phpunit/php-code-coverage':          ['sebastianbergmann/php-code-coverage', '11.0'],
  'phpunit/php-file-iterator':          ['sebastianbergmann/php-file-iterator', '5.1'],
  'phpunit/php-invoker':                ['sebastianbergmann/php-invoker', '5.0'],
  'phpunit/php-text-template':          ['sebastianbergmann/php-text-template', '4.0'],
  'phpunit/php-timer':                  ['sebastianbergmann/php-timer', '7.0'],
  'sebastian/cli-parser':               ['sebastianbergmann/cli-parser', '3.0'],
  'sebastian/code-unit':                ['sebastianbergmann/code-unit', '3.0'],
  'sebastian/code-unit-reverse-lookup': ['sebastianbergmann/code-unit-reverse-lookup', '4.0'],
  'sebastian/comparator':               ['sebastianbergmann/comparator', '6.3'],
  'sebastian/complexity':               ['sebastianbergmann/complexity', '4.0'],
  'sebastian/diff':                     ['sebastianbergmann/diff', '6.0'],
  'sebastian/environment':              ['sebastianbergmann/environment', '7.2'],
  'sebastian/exporter':                 ['sebastianbergmann/exporter', '6.3'],
  'sebastian/global-state':             ['sebastianbergmann/global-state', '7.0'],
  'sebastian/lines-of-code':            ['sebastianbergmann/lines-of-code', '3.0'],
  'sebastian/object-enumerator':        ['sebastianbergmann/object-enumerator', '6.0'],
  'sebastian/object-reflector':         ['sebastianbergmann/object-reflector', '4.0'],
  'sebastian/recursion-context':        ['sebastianbergmann/recursion-context', '6.0'],
  'sebastian/type':                     ['sebastianbergmann/type', '5.1'],
  'sebastian/version':                  ['sebastianbergmann/version', '5.0'],
  'phar-io/manifest':                   ['phar-io/manifest', '2.0'],
  'phar-io/version':                    ['phar-io/version', '3.2'],
  'theseer/tokenizer':                  ['theseer/tokenizer', '1.2'],
  'myclabs/deep-copy':                  ['myclabs/DeepCopy', '1.'],
  'mockery/mockery':                    ['mockery/mockery', '1.6'],
  'hamcrest/hamcrest-php':              ['hamcrest/hamcrest-php', 'v2.0'],
  'fakerphp/faker':                     ['FakerPHP/Faker', 'v1.2'],
  'pestphp/pest':                       ['pestphp/pest', 'v3.5'],
  'pestphp/pest-plugin':                ['pestphp/pest-plugin', 'v3.0'],
  'pestphp/pest-plugin-arch':           ['pestphp/pest-plugin-arch', 'v3.0'],
  'pestphp/pest-plugin-mutate':         ['pestphp/pest-plugin-mutate', 'v3.0'],
  'brianium/paratest':                  ['paratestphp/paratest', 'v7.6'],
  'nunomaduro/collision':               ['nunomaduro/collision', 'v8.5'],
  'nunomaduro/termwind':                ['nunomaduro/termwind', 'v2.2'],
  'filp/whoops':                        ['filp/whoops', '2.1'],
  'jean85/pretty-package-versions':     ['Jean85/pretty-package-versions', '2.0'],
  'staabm/side-effects-detector':       ['staabm/side-effects-detector', '1.0'],
};

const CA = process.env.NODE_EXTRA_CA_CERTS;
process.env.NODE_EXTRA_CA_CERTS = CA || '/usr/local/share/ca-certificates/e2b-ca.crt';

function latestTag(repo, prefix) {
  const url = `https://api.github.com/repos/${repo}/tags?per_page=100`;
  const out = execFileSync('curl', ['-sfL', url], { encoding: 'utf8', maxBuffer: 32 * 1024 * 1024 });
  const tags = JSON.parse(out).map((t) => t.name);
  const match = tags.filter((t) => t.startsWith(prefix));
  if (!match.length) return { chosen: null, sample: tags.slice(0, 6) };
  // highest by numeric segments
  const cmp = (a, b) => {
    const pa = a.replace(/^v/, '').split('.').map(Number);
    const pb = b.replace(/^v/, '').split('.').map(Number);
    for (let i = 0; i < Math.max(pa.length, pb.length); i++) {
      const d = (pa[i] || 0) - (pb[i] || 0);
      if (d) return d;
    }
    return 0;
  };
  match.sort(cmp);
  return { chosen: match[match.length - 1], all: match.length };
}

const resolved = {};
const problems = [];
for (const [name, [repo, prefix]] of Object.entries(WANT)) {
  try {
    const { chosen, sample } = latestTag(repo, prefix);
    if (!chosen) { problems.push(`${name}: no tag with prefix ${prefix} in ${repo} (sample ${sample})`); continue; }
    resolved[name] = chosen;
    process.stdout.write(`  ${name} -> ${repo}@${chosen}\n`);
  } catch (e) {
    problems.push(`${name}: ${e.message.split('\n')[0]}`);
  }
}
fs.writeFileSync('/tmp/test-packages.json', JSON.stringify(resolved, null, 2));
process.stdout.write(`\nresolved ${Object.keys(resolved).length}/${Object.keys(WANT).length}\n`);
if (problems.length) process.stdout.write('PROBLEMS:\n  ' + problems.join('\n  ') + '\n');
