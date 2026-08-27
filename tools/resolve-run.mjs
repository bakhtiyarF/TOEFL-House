import fs from 'node:fs';
import { resolve } from './resolve.mjs';

const serverComposer = JSON.parse(fs.readFileSync('/home/user/TOEFL-House/toefl-house-v3/server/composer.json', 'utf8'));
const root = serverComposer.require;
console.log('root requires:', JSON.stringify(root));

const { packages, problems } = await resolve(root, { log: (m) => process.stderr.write('  ' + m + '\n') });

const sorted = Object.fromEntries(Object.entries(packages).sort(([a], [b]) => a.localeCompare(b)));
fs.writeFileSync('/home/user/TOEFL-House/tools/packages.json', JSON.stringify(sorted, null, 2));
console.log(`\nresolved ${Object.keys(sorted).length} packages -> /home/user/TOEFL-House/tools/packages.json`);
if (problems.length) {
  console.log('\nPROBLEMS:');
  problems.forEach((p) => console.log(' -', p));
}
process.exit(0);
