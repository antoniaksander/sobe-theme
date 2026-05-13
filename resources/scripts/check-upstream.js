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
  console.log('No upstream remote configured.');
  process.exit(0);
}

if (/WP-boilerplate-demo/i.test(upstream)) {
  console.error(
    `Invalid upstream remote: ${upstream}\n` +
      'Client repositories must track the thin WP-boilerplate upstream, not the demo repository.',
  );
  process.exit(1);
}

console.log(`Upstream remote ok: ${upstream}`);
