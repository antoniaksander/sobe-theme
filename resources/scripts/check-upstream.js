import { execSync } from 'node:child_process';

function getUpstreamUrl() {
  try {
    return execSync('git remote get-url upstream', {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
  } catch {
    return '';
  }
}

const upstream = getUpstreamUrl();

if (!upstream) {
  console.error(
    'No upstream remote configured.\n' +
      'Every client fork must track the platform as `upstream` so platform ' +
      'fixes and features can be synced in later — see docs/client-fork-guide.md.\n' +
      'Run `npm run client:identify` (adds it automatically), or manually:\n' +
      '  git remote add upstream https://github.com/antoniaksander/sobe-theme.git',
  );
  process.exit(1);
}

if (/WP-boilerplate-demo/i.test(upstream)) {
  console.error(
    `Invalid upstream remote: ${upstream}\n` +
      'Client repositories must track the thin WP-boilerplate upstream, not the demo repository.',
  );
  process.exit(1);
}

console.log(`Upstream remote ok: ${upstream}`);
