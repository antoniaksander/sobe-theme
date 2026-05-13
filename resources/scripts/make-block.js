/**
 * Block scaffold — creates all required files for a new custom block.
 *
 * Usage:  npm run make:block -- my-block-slug [--category=sobe-general]
 *
 * Categories: sobe-general (default) | sobe-content
 *
 * Creates:
 *   resources/blocks/{slug}/block.json
 *   resources/blocks/{slug}/index.jsx
 *   resources/blocks/{slug}/edit.jsx
 *   resources/blocks/{slug}/save.jsx
 *   resources/blocks/{slug}/style.scss
 *   resources/blocks/{slug}/editor.scss
 *   resources/views/blocks/{slug}.blade.php
 *   resources/blocks/blocks-manifest.json  (entry added)
 */

import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

// ── Parse args ───────────────────────────────────────────────────────────────

const slug = process.argv[2];

if (!slug) {
  console.error('Error: block slug is required.\nUsage: npm run make:block -- my-block-slug [--category=sobe-general]');
  process.exit(1);
}

if (slug.startsWith('--')) {
  console.error('Error: slug must come before flags.\nUsage: npm run make:block -- my-block-slug [--category=sobe-general]');
  process.exit(1);
}

if (!/^[a-z][a-z0-9-]*$/.test(slug)) {
  console.error('Error: slug must be lowercase letters, numbers, and hyphens only (e.g. my-block).');
  process.exit(1);
}

const VALID_CATEGORIES = ['sobe-general', 'sobe-content'];
const categoryArg = process.argv.find((a) => a.startsWith('--category='));
const category = categoryArg ? categoryArg.split('=')[1] : 'sobe-general';

if (!VALID_CATEGORIES.includes(category)) {
  console.error(`Error: unknown category "${category}".\nValid options: ${VALID_CATEGORIES.join(', ')}`);
  process.exit(1);
}

// ── Derived values ───────────────────────────────────────────────────────────

const toTitle = (s) => s.split('-').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
const title = toTitle(slug);

const BLOCK_DIR  = resolve(`resources/blocks/${slug}`);
const BLADE_PATH = resolve(`resources/views/blocks/${slug}.blade.php`);
const MANIFEST   = resolve('resources/blocks/blocks-manifest.json');

// ── Guard: duplicate ─────────────────────────────────────────────────────────

if (existsSync(BLOCK_DIR)) {
  console.error(`Error: block "${slug}" already exists at ${BLOCK_DIR}`);
  process.exit(1);
}

// ── Create files ─────────────────────────────────────────────────────────────

mkdirSync(BLOCK_DIR, { recursive: true });

writeFileSync(`${BLOCK_DIR}/block.json`, JSON.stringify({
  '$schema': 'https://schemas.wp.org/trunk/block.json',
  apiVersion: 3,
  name: `sobe/${slug}`,
  version: '0.1.0',
  title,
  category,
  description: '',
  textdomain: 'sage',
  supports: {
    html: false,
    anchor: true,
    className: true,
    align: ['wide', 'full'],
  },
  attributes: {
    align: { type: 'string', default: 'wide' },
  },
}, null, 2) + '\n');

writeFileSync(`${BLOCK_DIR}/index.jsx`,
`const { registerBlockType } = wp.blocks;

import metadata from './block.json';
import Edit from './edit.jsx';
import save from './save.jsx';

import './style.scss';

registerBlockType(metadata, {
  edit: Edit,
  save,
});
`);

writeFileSync(`${BLOCK_DIR}/edit.jsx`,
`// Access WordPress packages as globals — do NOT import from '@wordpress/...'
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, PanelRow } = wp.components;
const { __ } = wp.i18n;

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Settings', 'sage')} initialOpen={true}>
          {/* Add controls here */}
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <p>{__('${title} — editor preview', 'sage')}</p>
      </div>
    </>
  );
}
`);

writeFileSync(`${BLOCK_DIR}/save.jsx`,
`// Returning null is intentional: this is a dynamic block.
// WordPress never uses the client-side save output — the Blade template
// at resources/views/blocks/${slug}.blade.php renders the frontend HTML
// via the render_callback registered in app/setup.php.
export default function save() {
  return null;
}
`);

writeFileSync(`${BLOCK_DIR}/style.scss`,
`// Frontend styles for the ${slug} block.
// Prefer Tailwind utilities in the Blade template.
// Only add CSS here for things Tailwind cannot express.
`);

writeFileSync(`${BLOCK_DIR}/editor.scss`,
`// Editor-only styles for the ${slug} block.
`);

writeFileSync(BLADE_PATH,
`@php
  /** @var array \$attributes */
  \$wrapperAttrs = get_block_wrapper_attributes();
@endphp

<section {!! \$wrapperAttrs !!}>
  {{-- ${title} block output --}}
</section>
`);

// ── Update blocks-manifest.json ──────────────────────────────────────────────

const manifest = JSON.parse(readFileSync(MANIFEST, 'utf8'));

if (slug in manifest) {
  console.warn(`Warning: "${slug}" is already in blocks-manifest.json — skipping manifest update.`);
} else {
  manifest[slug] = { category };
  writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2) + '\n');
}

// ── Done ─────────────────────────────────────────────────────────────────────

console.log(`
✅  Block "${slug}" scaffolded successfully.

Files created:
  resources/blocks/${slug}/block.json
  resources/blocks/${slug}/index.jsx
  resources/blocks/${slug}/edit.jsx
  resources/blocks/${slug}/save.jsx
  resources/blocks/${slug}/style.scss
  resources/blocks/${slug}/editor.scss
  resources/views/blocks/${slug}.blade.php

Manifest updated:
  resources/blocks/blocks-manifest.json  (category: ${category})

Next steps:
  1. Define your attributes in block.json
  2. Add InspectorControls to edit.jsx
  3. Build the output in resources/views/blocks/${slug}.blade.php
  4. Run: npm run dev
`);
