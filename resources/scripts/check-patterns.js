/**
 * Pattern allowlist checker.
 *
 * Reads every file in resources/patterns/ and extracts block names from
 * Gutenberg HTML comments (<!-- wp:namespace/name -->).
 * Exits 1 if any block is not in the allowed_block_types_all list from app/blocks.php.
 *
 * Usage: npm run check:patterns
 *        node resources/scripts/check-patterns.js
 */

import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { join, resolve } from 'node:path';

// ── Allowlist (sourced from resources/config/core-allowed-blocks.json,
//    the same file read by allowed_block_types_all in app/blocks.php) ─────────

const { core, woocommerce } = JSON.parse(
  readFileSync(resolve('resources/config/core-allowed-blocks.json'), 'utf8'),
);
const CORE_ALLOWED = new Set(core);
const WC_ALLOWED = new Set(woocommerce);

// Derive custom blocks from the manifest. Names default to sobe/<slug> unless
// a manifest entry declares an explicit full name for a client namespace block.
const manifest = JSON.parse(readFileSync(resolve('resources/blocks/blocks-manifest.json'), 'utf8'));
const MANIFEST_ALLOWED = new Set(
  Object.entries(manifest).map(([slug, entry]) => entry.name ?? `sobe/${slug}`),
);

function isAllowed(name) {
  return CORE_ALLOWED.has(name) || WC_ALLOWED.has(name) || MANIFEST_ALLOWED.has(name);
}

// ── Check patterns ────────────────────────────────────────────────────────────

const patternsDir = resolve('resources/patterns');
const files = existsSync(patternsDir)
  ? readdirSync(patternsDir).filter((f) => f.endsWith('.php'))
  : [];

let errors = 0;

for (const file of files) {
  const content = readFileSync(join(patternsDir, file), 'utf8');

  // Match block names; blocks without a namespace (e.g. <!-- wp:paragraph -->) default to core/.
  const names = [...content.matchAll(/<!-- wp:([a-z0-9_-]+(?:\/[a-z0-9_-]+)?)/g)].map((m) =>
    m[1].includes('/') ? m[1] : `core/${m[1]}`,
  );

  for (const name of names) {
    if (!isAllowed(name)) {
      console.error(`❌  ${file}: disallowed block "${name}"`);
      errors++;
    }
  }
}

if (errors > 0) {
  console.error(
    `\n${errors} violation(s). Add the block to resources/config/core-allowed-blocks.json or remove it from the pattern.`,
  );
  process.exit(1);
}

console.log(`✅  ${files.length} pattern file(s) checked — all blocks are in the allowlist.`);
