/**
 * Block scaffold — creates all required files for a new custom block.
 *
 * Usage:  npm run make:block -- my-block-slug [--category=sobe-general]
 *         npm run make:block -- namespace/my-block-slug [--category=namespace]
 *
 * Default category: sobe-general for shorthand Sobe blocks, otherwise namespace.
 *
 * Creates:
 *   resources/blocks/{namespace}/{slug}/block.json
 *   resources/blocks/{namespace}/{slug}/index.jsx
 *   resources/blocks/{namespace}/{slug}/edit.jsx
 *   resources/blocks/{namespace}/{slug}/save.jsx
 *   resources/blocks/{namespace}/{slug}/style.scss
 *   resources/blocks/{namespace}/{slug}/editor.scss
 *   resources/views/blocks/{namespace}/{slug}.blade.php
 *   resources/blocks/blocks-manifest.json  (entry added)
 */

import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

// ── Parse args ───────────────────────────────────────────────────────────────

const inputPath = process.argv[2];

if (!inputPath) {
  console.error('Error: block slug is required.\nUsage: npm run make:block -- my-block-slug [--category=sobe-general]');
  process.exit(1);
}

if (inputPath.startsWith('--')) {
  console.error('Error: slug must come before flags.\nUsage: npm run make:block -- my-block-slug [--category=sobe-general]');
  process.exit(1);
}

const blockPath = inputPath.includes('/') ? inputPath : `sobe/${inputPath}`;
const [namespace, slug, ...extra] = blockPath.split('/');

if (extra.length || !/^[a-z][a-z0-9_-]*$/.test(namespace) || !/^[a-z][a-z0-9-]*$/.test(slug)) {
  console.error('Error: block path must be namespace/slug with lowercase letters, numbers, hyphens, or underscores in the namespace.');
  process.exit(1);
}

const categoryArg = process.argv.find((a) => a.startsWith('--category='));
const defaultCategory = namespace === 'sobe' ? 'sobe-general' : namespace;
const category = categoryArg ? categoryArg.split('=')[1] : defaultCategory;

if (!/^[a-z][a-z0-9_-]*$/.test(category)) {
  console.error('Error: category must be a lowercase block category slug.');
  process.exit(1);
}

// ── Derived values ───────────────────────────────────────────────────────────

const toTitle = (s) => s.split('-').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
const title = toTitle(slug);

const BLOCK_DIR  = resolve(`resources/blocks/${blockPath}`);
const BLADE_PATH = resolve(`resources/views/blocks/${blockPath}.blade.php`);
const MANIFEST   = resolve('resources/blocks/blocks-manifest.json');

// ── Guard: duplicate ─────────────────────────────────────────────────────────

if (existsSync(BLOCK_DIR)) {
  console.error(`Error: block "${slug}" already exists at ${BLOCK_DIR}`);
  process.exit(1);
}

// ── Create files ─────────────────────────────────────────────────────────────

mkdirSync(BLOCK_DIR, { recursive: true });
mkdirSync(dirname(BLADE_PATH), { recursive: true });

writeFileSync(`${BLOCK_DIR}/block.json`, JSON.stringify({
  '$schema': 'https://schemas.wp.org/trunk/block.json',
  apiVersion: 3,
  name: blockPath,
  version: '0.1.0',
  title,
  category,
  description: '',
  textdomain: 'sobe',
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
        <PanelBody title={__('Settings', 'sobe')} initialOpen={true}>
          {/* Add controls here */}
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <p>{__('${title} — editor preview', 'sobe')}</p>
      </div>
    </>
  );
}
`);

writeFileSync(`${BLOCK_DIR}/save.jsx`,
`// Returning null is intentional: this is a dynamic block.
// WordPress never uses the client-side save output — the Blade template
// at resources/views/blocks/${blockPath}.blade.php renders the frontend HTML
// via the render_callback registered in app/blocks.php.
export default function save() {
  return null;
}
`);

writeFileSync(`${BLOCK_DIR}/style.scss`,
`// Frontend styles for the ${slug} block.
// Prefer Tailwind utilities in the Blade template.
// Only add CSS here for things Tailwind cannot express.

.${slug} {
  // Block base styles.
}

.${slug}--${namespace} {
  // Namespace-specific overrides.
}
`);

writeFileSync(`${BLOCK_DIR}/editor.scss`,
`// Editor-only styles for the ${slug} block.
`);

writeFileSync(BLADE_PATH,
`@php
  /** @var array \$attributes */
  \$componentClass = \$blockBaseClass ?? '${slug}';
  \$namespaceClass = \$blockNamespaceClass ?? "{\$componentClass}--${namespace}";

  \$wrapperAttrs = get_block_wrapper_attributes([
    'class' => trim("{\$componentClass} {\$namespaceClass}"),
  ]);
@endphp

<section {!! \$wrapperAttrs !!}>
  {{-- ${title} block output --}}
</section>
`);

// ── Update blocks-manifest.json ──────────────────────────────────────────────

const manifest = JSON.parse(readFileSync(MANIFEST, 'utf8'));

if (blockPath in manifest) {
  console.warn(`Warning: "${blockPath}" is already in blocks-manifest.json — skipping manifest update.`);
} else {
  manifest[blockPath] = { category };
  writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2) + '\n');
}

// ── Done ─────────────────────────────────────────────────────────────────────

console.log(`
✅  Block "${blockPath}" scaffolded successfully.

Files created:
  resources/blocks/${blockPath}/block.json
  resources/blocks/${blockPath}/index.jsx
  resources/blocks/${blockPath}/edit.jsx
  resources/blocks/${blockPath}/save.jsx
  resources/blocks/${blockPath}/style.scss
  resources/blocks/${blockPath}/editor.scss
  resources/views/blocks/${blockPath}.blade.php

Manifest updated:
  resources/blocks/blocks-manifest.json  (category: ${category})

Next steps:
  1. Define your attributes in block.json
  2. Add InspectorControls to edit.jsx
  3. Build the output in resources/views/blocks/${blockPath}.blade.php
  4. Run: npm run dev
`);
