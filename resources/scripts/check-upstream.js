/**
 * Verify this fork knows where the platform lives, so platform fixes and
 * features can always be synced in later.
 *
 * Two things are checked, because they answer different questions:
 *
 *   1. The DECLARED upstream — `wpBoilerplate.upstream` in package.json.
 *      This is committed, so it survives a fresh clone, shows up in review,
 *      and is the only part that can be verified in CI.
 *
 *   2. The CONFIGURED upstream — the `upstream` git remote.
 *      Git remotes live in .git/config and are never committed, so a CI
 *      checkout only ever has `origin`. This is therefore checked on a
 *      developer machine and reported (not failed) under CI.
 *
 * Usage:  npm run check:upstream
 */

import { execSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const FALLBACK_UPSTREAM = 'https://github.com/antoniaksander/sobe-theme.git';

/**
 * Compare repo URLs by identity rather than spelling: HTTPS and SSH forms,
 * a trailing `.git`, and case all vary without meaning a different repo.
 */
function normalizeRepoUrl(url) {
  if (!url) return '';

  return url
    .trim()
    .replace(/^git@([^:]+):/, 'https://$1/')
    .replace(/\.git$/, '')
    .replace(/\/+$/, '')
    .toLowerCase();
}

function gitRemoteUrl(name) {
  try {
    return execSync(`git remote get-url ${name}`, {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
  } catch {
    return '';
  }
}

function readDeclaredUpstream() {
  const pkg = JSON.parse(readFileSync(resolve('package.json'), 'utf8'));

  return pkg.wpBoilerplate?.upstream ?? '';
}

function fail(message) {
  console.error(message);
  process.exit(1);
}

// ── 1. The declared upstream (committed, verifiable in CI) ───────────────────

const declared = readDeclaredUpstream();

if (!declared) {
  fail(
    'No upstream declared in package.json.\n' +
      'Every fork must record the platform repo it was forked from so platform ' +
      'fixes can be synced in later — see docs/client-fork-guide.md.\n\n' +
      'Add it under the existing "wpBoilerplate" key:\n' +
      `  "wpBoilerplate": { "upstream": "${FALLBACK_UPSTREAM}" }\n`,
  );
}

if (/WP-boilerplate-demo/i.test(declared)) {
  fail(
    `Invalid declared upstream: ${declared}\n` +
      'Forks must track the thin platform repo, not the demo repository.\n' +
      `Set package.json -> wpBoilerplate.upstream to ${FALLBACK_UPSTREAM}\n`,
  );
}

if (!/^(https?:\/\/|git@)/.test(declared)) {
  fail(
    `Declared upstream does not look like a repo URL: ${declared}\n` +
      `Expected an https:// or git@ URL, for example ${FALLBACK_UPSTREAM}\n`,
  );
}

// ── 2. This repo's identity ─────────────────────────────────────────────────

const origin = gitRemoteUrl('origin');

// The platform repo declares itself as its own upstream; it has no separate
// `upstream` remote to configure, so there is nothing further to check here.
if (origin && normalizeRepoUrl(origin) === normalizeRepoUrl(declared)) {
  console.log(`Upstream ok: this is the platform repo (${declared})`);
  process.exit(0);
}

// ── 3. The configured upstream remote (developer machines only) ─────────────

const configured = gitRemoteUrl('upstream');

if (!configured) {
  // Git remotes are not part of the repository, so a CI checkout legitimately
  // has none. Failing here would break every fork's pipeline for something CI
  // cannot fix; the declared upstream above is the part CI can vouch for.
  if (process.env.CI) {
    console.log(
      `Declared upstream ok: ${declared}\n` +
        'No `upstream` git remote in this checkout — expected under CI, since ' +
        'remotes are local config and are never committed. Skipping the remote check.',
    );
    process.exit(0);
  }

  fail(
    'No `upstream` git remote configured.\n' +
      'Every client fork must track the platform as `upstream` so platform ' +
      'fixes and features can be synced in later — see docs/client-fork-guide.md.\n\n' +
      'Run `npm run client:identify` (adds it automatically), or manually:\n' +
      `  git remote add upstream ${declared}\n`,
  );
}

if (/WP-boilerplate-demo/i.test(configured)) {
  fail(
    `Invalid upstream remote: ${configured}\n` +
      'Client repositories must track the thin platform upstream, not the demo repository.\n' +
      `  git remote set-url upstream ${declared}\n`,
  );
}

if (normalizeRepoUrl(configured) !== normalizeRepoUrl(declared)) {
  fail(
    'The `upstream` git remote does not match the upstream declared in package.json.\n' +
      `  remote:   ${configured}\n` +
      `  declared: ${declared}\n\n` +
      'Point the remote at the declared upstream:\n' +
      `  git remote set-url upstream ${declared}\n` +
      'or update package.json -> wpBoilerplate.upstream if the remote is the correct one.\n',
  );
}

console.log(`Upstream remote ok: ${configured}`);
