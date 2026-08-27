// Minimal composer-style resolver that works without packagist.org:
// it reads versions + composer.json straight from the GitHub API and picks the
// newest tag satisfying each constraint.
import fs from 'node:fs';

const API = 'https://api.github.com';
const cacheDir = '/home/user/TOEFL-House/tools/.meta';
fs.mkdirSync(cacheDir, { recursive: true });

import { repoFor } from './pkg.mjs';

// Keep the dependency tree consistent with the versions Laravel 12 ships with.
const PREFERENCES = [
  { match: (n) => n.startsWith('symfony/') && !n.includes('polyfill'), prefer: '^7.2' },
  { match: (n) => n === 'egulias/email-validator', prefer: '^4.0' },
  { match: (n) => n === 'sabberworm/php-css-parser', prefer: '^8.5' },
  { match: (n) => n === 'psy/psysh', prefer: '^0.12' },
];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function gh(url, tries = 4) {
  for (let i = 0; i < tries; i++) {
    try {
      const res = await fetch(url, { headers: { 'user-agent': 'toefl-vendor-builder' } });
      if (res.status === 403 || res.status === 429) {
        const reset = res.headers.get('x-ratelimit-reset');
        const wait = reset ? Math.max(1000, (Number(reset) * 1000 - Date.now())) : 15000;
        process.stderr.write(`rate limited on ${url}, waiting ${Math.round(wait / 1000)}s\n`);
        await sleep(Math.min(wait, 60000));
        continue;
      }
      if (res.status === 404) return null;
      if (!res.ok) throw new Error(`${res.status} ${url}`);
      return await res.json();
    } catch (e) {
      if (i === tries - 1) throw e;
      await sleep(2000 * (i + 1));
    }
  }
  return null;
}

const cache = (key, fn) => async () => {
  const f = `${cacheDir}/${key.replace(/[^a-z0-9._-]/gi, '_')}.json`;
  if (fs.existsSync(f)) return JSON.parse(fs.readFileSync(f, 'utf8'));
  const v = await fn();
  if (v !== null && v !== undefined) fs.writeFileSync(f, JSON.stringify(v));
  return v;
};

const tagsCache = {};
async function tagsOf(repo) {
  if (tagsCache[repo]) return tagsCache[repo];
  const out = [];
  for (let page = 1; page <= 4; page++) {
    const list = await gh(`${API}/repos/${repo}/tags?per_page=100&page=${page}`);
    if (!list || !list.length) break;
    out.push(...list.map((t) => t.name));
    if (list.length < 100) break;
  }
  tagsCache[repo] = out;
  return out;
}

const metaOf = (repo, tag) => cache(`meta_${repo}_${tag}`, () =>
  fetch(`${API}/repos/${repo}/contents/composer.json?ref=${encodeURIComponent(tag)}`, {
    headers: { accept: 'application/vnd.github.raw', 'user-agent': 'toefl-vendor-builder' },
  }).then(async (r) => (r.ok ? r.json() : null)).catch(() => null))();

// ---- semver ----------------------------------------------------------------
const parse = (v) => {
  const m = String(v).replace(/^v/i, '').match(/^(\d+)(?:\.(\d+))?(?:\.(\d+))?(?:[-.]?(dev|alpha|beta|rc|RC)[.\-]?(\d+)?)?/);
  if (!m) return null;
  return {
    major: +m[1], minor: m[2] === undefined ? 0 : +m[2], patch: m[3] === undefined ? 0 : +m[3],
    pre: m[4] ? `${m[4]}${m[5] || ''}` : '', raw: String(v),
  };
};
const cmp = (a, b) => {
  if (a.major !== b.major) return a.major - b.major;
  if (a.minor !== b.minor) return a.minor - b.minor;
  if (a.patch !== b.patch) return a.patch - b.patch;
  if (a.pre && !b.pre) return -1;
  if (!a.pre && b.pre) return 1;
  return a.pre < b.pre ? -1 : a.pre > b.pre ? 1 : 0;
};
const one = (v, c) => {
  c = c.trim();
  if (!c || c === '*') return true;
  const cv = parse(c.replace(/^[\^~><=!v]+/, ''));
  if (!cv) return true;
  const op = c.startsWith('^') ? '^' : c.startsWith('~') ? '~' : c.startsWith('>=') ? '>=' : c.startsWith('>') ? '>'
    : c.startsWith('<=') ? '<=' : c.startsWith('<') ? '<' : c.startsWith('!=') ? '!=' : '=';
  if (op === '^') {
    if (cmp(v, cv) < 0) return false;
    const upper = cv.major > 0 ? { ...cv, major: cv.major + 1, minor: 0, patch: 0, pre: '' }
      : cv.minor > 0 ? { ...cv, major: 0, minor: cv.minor + 1, patch: 0, pre: '' }
        : { ...cv, patch: cv.patch + 1, pre: '' };
    return cmp(v, upper) < 0;
  }
  if (op === '~') {
    if (cmp(v, cv) < 0) return false;
    // composer: ~1.2 => >=1.2 <2.0.0 ; ~1.2.3 => >=1.2.3 <1.3.0
    const specifiedPatch = /^~v?\d+(\.\d+){2}/.test(c);
    const upper = specifiedPatch
      ? { ...cv, minor: cv.minor + 1, patch: 0, pre: '' }
      : { ...cv, major: cv.major + 1, minor: 0, patch: 0, pre: '' };
    return cmp(v, upper) < 0;
  }
  const d = cmp(v, cv);
  switch (op) {
    case '>=': return d >= 0;
    case '>': return d > 0;
    case '<=': return d <= 0;
    case '<': return d < 0;
    case '!=': return d !== 0;
    default: return d === 0;
  }
};
export function satisfies(version, constraint) {
  if (!constraint || constraint === '*' || constraint === 'dev-main') return true;
  return constraint.split('|').filter((p) => p.trim()).some((group) =>
    group.split(/\s*,\s*|\s+/).filter(Boolean).every((c) => one(parse(version), c)),
  );
}

// ---- resolution ------------------------------------------------------------
const SKIP = /^(php$|ext-|lib-|composer|composer-runtime-api|composer-plugin-api)/;

export async function resolve(rootRequires, { log = () => {} } = {}) {
  const resolved = new Map(); // name -> {version, meta, repo, tag}
  const replaced = new Map(); // provided/replaced name -> provider
  const queue = Object.entries(rootRequires).filter(([n]) => !SKIP.test(n));
  const problems = [];

  while (queue.length) {
    const [name, constraint] = queue.shift();
    if (SKIP.test(name)) continue;
    if (replaced.has(name)) continue;
    if (resolved.has(name)) {
      if (!satisfies(resolved.get(name).version, constraint)) {
        problems.push(`${name}: ${resolved.get(name).version} does not satisfy ${constraint}`);
      }
      continue;
    }
    const repo = repoFor(name);
    const tags = await tagsOf(repo);
    if (!tags.length) { problems.push(`${name}: no tags for ${repo}`); continue; }
    const candidates = tags
      .map((t) => ({ tag: t, v: parse(t) }))
      .filter((c) => c.v && satisfies(c.tag, constraint))
      .sort((a, b) => cmp(b.v, a.v));
    // prefer the branch the rest of the tree expects, then stable releases
    let pool = candidates;
    for (const pref of PREFERENCES) {
      if (pref.match(name)) {
        const narrowed = pool.filter((c) => satisfies(c.tag, pref.prefer));
        if (narrowed.length) pool = narrowed;
      }
    }
    const stable = pool.find((c) => !c.v.pre) || pool[0];
    if (!stable) { problems.push(`${name}: no tag satisfies ${constraint}`); continue; }

    const meta = await metaOf(repo, stable.tag);
    if (!meta) { problems.push(`${name}: no composer.json at ${repo}@${stable.tag}`); continue; }
    const version = String(meta.version || stable.tag).replace(/^v/, '');
    log(`${name} -> ${repo}@${stable.tag}`);
    resolved.set(name, { version, repo, tag: stable.tag, meta });

    for (const [provided, ver] of Object.entries(meta.replace || {})) {
      replaced.set(provided, name);
      if (!resolved.has(provided) && !SKIP.test(provided)) resolved.set(provided, { version: ver, repo, tag: stable.tag, meta, aliasOf: name });
    }
    for (const provided of Object.keys(meta.provide || {})) replaced.set(provided, name);

    for (const [dep, depConstraint] of Object.entries(meta.require || {})) {
      if (SKIP.test(dep) || replaced.has(dep)) continue;
      queue.push([dep, depConstraint]);
    }
  }

  const out = {};
  for (const [name, info] of resolved) {
    if (info.aliasOf) continue; // provided by another package
    out[name] = info.tag;
  }
  return { packages: out, problems, details: resolved };
}
