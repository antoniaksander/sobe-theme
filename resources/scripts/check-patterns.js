/**
 * Pattern allowlist checker.
 *
 * Reads every file in resources/patterns/ and extracts block names from
 * Gutenberg HTML comments (<!-- wp:namespace/name -->).
 * Exits 1 if any block is not in the allowed_block_types_all list from app/setup.php.
 *
 * Usage: npm run check:patterns
 *        node resources/scripts/check-patterns.js
 */

import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { join, resolve } from 'node:path';

// ── Allowlist (mirrors allowed_block_types_all in app/setup.php) ─────────────

const CORE_ALLOWED = new Set([
  'core/paragraph', 'core/heading', 'core/list', 'core/list-item',
  'core/image', 'core/quote', 'core/embed',
  'core/button', 'core/buttons', 'core/separator', 'core/spacer',
  'core/shortcode', 'core/table', 'core/group', 'core/columns', 'core/column',
]);

const WC_ALLOWED = new Set([
  'woocommerce/product-filters', 'woocommerce/active-filters',
  'woocommerce/all-products', 'woocommerce/product-search',
  'woocommerce/handpicked-products',
]);

// Derive sobe/* blocks from the manifest — stays in sync automatically.
const manifest = JSON.parse(readFileSync(resolve('resources/blocks/blocks-manifest.json'), 'utf8'));
const SOBE_ALLOWED = new Set(Object.keys(manifest).map((slug) => `sobe/${slug}`));

function isAllowed(name) {
  return CORE_ALLOWED.has(name) || WC_ALLOWED.has(name) || SOBE_ALLOWED.has(name);
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
    `\n${errors} violation(s). Add the block to allowed_block_types_all in app/setup.php or remove it from the pattern.`,
  );
  process.exit(1);
}

console.log(`✅  ${files.length} pattern file(s) checked — all blocks are in the allowlist.`);
