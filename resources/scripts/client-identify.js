/**
 * Client identity setup — interactively applies the mechanical edits from the
 * "Identity Checklist" in docs/client-fork-guide.md.
 *
 * Usage:  npm run client:identify
 *
 * Edits:
 *   style.css        Theme Name, Theme URI, Description, Author, Author URI
 *   config/theme.php 'prefix' value
 *   composer.json     name, description
 *   package.json      name
 *
 * Also ensures the `upstream` git remote (this platform repo) is configured,
 * so every client fork can sync platform updates from day one rather than
 * that being a manual step someone has to remember (see
 * docs/client-fork-guide.md, "Initial Setup").
 *
 * Does NOT run `composer update` or `npm install` automatically here — those
 * are printed as next steps so the user can review the diff first.
 *
 * Not automated (judgment calls, see docs/client-fork-guide.md):
 *   README.md, LICENSE.md, CONTRIBUTING.md, CHANGELOG.md
 */

import { createInterface } from 'node:readline';
import { readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { execSync } from 'node:child_process';

const STYLE_CSS   = resolve('style.css');
const THEME_PHP   = resolve('config/theme.php');
const COMPOSER    = resolve('composer.json');
const PACKAGE     = resolve('package.json');

// Single source of truth, shared with check-upstream.js: the platform repo is
// declared in package.json so it survives a fresh clone and is visible in review.
const UPSTREAM_URL =
  JSON.parse(readFileSync(PACKAGE, 'utf8')).wpBoilerplate?.upstream ??
  'https://github.com/antoniaksander/sobe-theme.git';

function ensureUpstreamRemote() {
  let existing = '';
  try {
    existing = execSync('git remote get-url upstream', {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
  } catch {
    // No upstream remote yet — expected on a fresh fork, fall through to add it.
  }

  if (existing) {
    if (/WP-boilerplate-demo/i.test(existing)) {
      console.log(
        `⚠️  upstream points at the demo repo (${existing}) — client forks should track\n` +
          `   ${UPSTREAM_URL} instead. Fix with:\n` +
          `   git remote set-url upstream ${UPSTREAM_URL}\n`,
      );
    } else {
      console.log(`✅ upstream remote already configured -> ${existing}\n`);
    }
    return;
  }

  try {
    execSync(`git remote add upstream ${UPSTREAM_URL}`);
    console.log(`✅ Added upstream remote -> ${UPSTREAM_URL}\n`);
  } catch (err) {
    console.log(
      `⚠️  Could not add the upstream remote automatically (${err.message}).\n` +
        `   Add it manually: git remote add upstream ${UPSTREAM_URL}\n`,
    );
  }
}

ensureUpstreamRemote();

// Plain readline.question() auto-closes the interface once its input stream
// hits EOF (e.g. piped/non-interactive input), which silently breaks every
// question() call after the first. Listening for 'line' events directly and
// queueing them avoids that and works the same for a real TTY.
const rl = createInterface({ input: process.stdin });
const lineQueue = [];
const waiting = [];

rl.on('line', (line) => {
  if (waiting.length) waiting.shift()(line);
  else lineQueue.push(line);
});
rl.on('close', () => {
  while (waiting.length) waiting.shift()(null);
});

function readLine(prompt) {
  process.stdout.write(prompt);
  return new Promise((resolve) => {
    if (lineQueue.length) resolve(lineQueue.shift());
    else waiting.push(resolve);
  });
}

async function ask(question, { required = false, validate } = {}) {
  for (;;) {
    const raw = await readLine(question);

    if (raw === null) {
      console.log('\nInput ended unexpectedly.');
      process.exit(1);
    }

    const answer = raw.trim();

    if (!answer && required) {
      console.log('  This value is required.');
      continue;
    }

    if (answer && validate) {
      const error = validate(answer);
      if (error) {
        console.log(`  ${error}`);
        continue;
      }
    }

    return answer;
  }
}

const slugValidator = (value) =>
  /^[a-z][a-z0-9-]*$/.test(value)
    ? null
    : 'Use lowercase letters, numbers, and hyphens only, starting with a letter.';

console.log('Client identity setup — press Enter to skip an optional field.\n');

const themeName = await ask('Theme Name (e.g. Roxder): ', { required: true });
const prefix = await ask('Client prefix, lowercase (e.g. roxder): ', {
  required: true,
  validate: slugValidator,
});
const themeUri = await ask('Theme URI (e.g. https://roxder.com): ');
const description = await ask(`Description [${themeName} WordPress theme]: `) || `${themeName} WordPress theme`;
const author = await ask('Author: ');
const authorUri = await ask('Author URI: ');

rl.close();

// ── style.css ────────────────────────────────────────────────────────────────

let styleCss = readFileSync(STYLE_CSS, 'utf8');

const replaceHeaderField = (contents, field, value) => {
  if (!value) return contents;
  const pattern = new RegExp(`^(${field}:\\s*).*$`, 'm');
  if (!pattern.test(contents)) return contents;
  const padding = ' '.repeat(Math.max(1, 19 - field.length));
  return contents.replace(pattern, `${field}:${padding}${value}`);
};

styleCss = replaceHeaderField(styleCss, 'Theme Name', themeName);
styleCss = replaceHeaderField(styleCss, 'Theme URI', themeUri);
styleCss = replaceHeaderField(styleCss, 'Description', description);
styleCss = replaceHeaderField(styleCss, 'Author', author);
styleCss = replaceHeaderField(styleCss, 'Author URI', authorUri);

writeFileSync(STYLE_CSS, styleCss);

// ── config/theme.php ─────────────────────────────────────────────────────────

let themePhp = readFileSync(THEME_PHP, 'utf8');
themePhp = themePhp.replace(/'prefix'\s*=>\s*'[^']*',/, `'prefix' => '${prefix}',`);
writeFileSync(THEME_PHP, themePhp);

// ── composer.json ────────────────────────────────────────────────────────────

const composer = JSON.parse(readFileSync(COMPOSER, 'utf8'));
composer.name = `${prefix}/wp-theme`;
composer.description = description;
writeFileSync(COMPOSER, JSON.stringify(composer, null, 4) + '\n');

// ── package.json ─────────────────────────────────────────────────────────────

const pkg = JSON.parse(readFileSync(PACKAGE, 'utf8'));
pkg.name = prefix;
writeFileSync(PACKAGE, JSON.stringify(pkg, null, 2) + '\n');

// ── Done ─────────────────────────────────────────────────────────────────────

console.log(`
✅  Client identity applied.

Changed:
  style.css          Theme Name, Theme URI, Description, Author, Author URI
  config/theme.php   prefix -> '${prefix}'
  composer.json       name -> '${prefix}/wp-theme', description
  package.json        name -> '${prefix}'

Kept unchanged (upstream contract, see docs/client-fork-guide.md):
  Text Domain (style.css), 'textdomain' (config/theme.php) — stays 'sobe'
  sobe/* hook namespace and universal block names — stay 'sobe/*'

Not automated (judgment calls — edit these yourself):
  README.md, LICENSE.md, CONTRIBUTING.md, CHANGELOG.md

Next steps:
  git diff
  composer update
  npm install
  npm test
  npm run check:patterns
  npm run check:upstream
  npm run build
`);
