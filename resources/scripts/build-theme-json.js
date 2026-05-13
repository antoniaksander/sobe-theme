import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(__dirname, '../..');
const themeJsonPath = path.join(rootDir, 'public/build/assets/theme.json');
const tokensPath = path.join(rootDir, 'resources/css/tokens.css');

// Extract raw hex values directly from tokens.css
const tokensCss = fs.readFileSync(tokensPath, 'utf8');
const rootBlockMatch = tokensCss.match(/:root\s*{([^}]*)}/gs);
const rootBlock = rootBlockMatch ? rootBlockMatch.join('\n') : tokensCss;

const extractColor = (token) => {
  const regex = new RegExp(
    `${token}:\\s*(#[0-9a-fA-F]{3,8}|rgba?\\([^)]+\\));`,
  );
  const match = rootBlock.match(regex);
  return match ? match[1] : null;
};

const extractToken = (token) => {
  const regex = new RegExp(`${token}:\\s*([^;]+);`);
  const match = rootBlock.match(regex);
  return match ? match[1].trim() : null;
};

// Curated editor palette mapping
const paletteMapping = [
  { name: 'Background', slug: 'background', token: '--c-background' },
  { name: 'Surface Dark', slug: 'surface-invert', token: '--c-surface-invert' },
  { name: 'Heading', slug: 'heading', token: '--c-heading' },
  { name: 'Text Muted', slug: 'text-muted', token: '--c-text-muted' },
  { name: 'Text Subtle', slug: 'text-subtle', token: '--c-text-subtle' },
  {
    name: 'Text Inverse',
    slug: 'surface-invert-fg',
    token: '--c-surface-invert-fg',
  },
  { name: 'Accent', slug: 'accent', token: '--c-accent' },
  { name: 'Border', slug: 'border', token: '--c-border' },
  { name: 'White', slug: 'accent-fg', token: '--c-accent-fg' },
];

const editorPalette = paletteMapping.reduce((acc, item) => {
  const colorValue = extractColor(item.token);
  if (colorValue)
    acc.push({ name: item.name, slug: item.slug, color: colorValue });
  return acc;
}, []);

const editorFontSizes = [
  { name: 'XS', slug: 'xs', size: 'var(--font-size-xs)' },
  { name: 'SM', slug: 'sm', size: 'var(--font-size-sm)' },
  { name: 'Base', slug: 'base', size: 'var(--font-size-base)' },
  { name: 'LG', slug: 'lg', size: 'var(--font-size-lg)' },
  { name: 'XL', slug: 'xl', size: 'var(--font-size-xl)' },
  { name: '2XL', slug: '2xl', size: 'var(--font-size-2xl)' },
  { name: '3XL', slug: '3xl', size: 'var(--font-size-3xl)' },
  { name: '4XL', slug: '4xl', size: 'var(--font-size-4xl)' },
  { name: '5XL', slug: '5xl', size: 'var(--font-size-5xl)' },
  { name: '6XL', slug: '6xl', size: 'var(--font-size-6xl)' },
  { name: '7XL', slug: '7xl', size: 'var(--font-size-7xl)' },
];

const editorFonts = [
  {
    name: 'Sans',
    slug: 'sans',
    fontFamily: 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
  },
  {
    name: 'Serif',
    slug: 'serif',
    fontFamily: 'ui-serif, Georgia, Cambria, "Times New Roman", serif',
  },
  {
    name: 'Mono',
    slug: 'mono',
    fontFamily:
      'ui-monospace, "Cascadia Code", "Fira Code", Consolas, monospace',
  },
];

function injectEditorSettings(themeJson) {
  if (!themeJson.settings) themeJson.settings = {};
  if (!themeJson.settings.color) themeJson.settings.color = {};
  if (!themeJson.settings.typography) themeJson.settings.typography = {};

  themeJson.settings.color.palette = editorPalette;
  themeJson.settings.color.custom = false;
  themeJson.settings.typography.fontSizes = editorFontSizes;
  themeJson.settings.typography.fontFamilies = editorFonts;
  themeJson.settings.layout.contentSize =
    extractToken('--layout-content') ?? '48rem';
  themeJson.settings.layout.wideSize = extractToken('--layout-wide') ?? '80rem';

  return themeJson;
}

try {
  const themeJsonContent = fs.readFileSync(themeJsonPath, 'utf-8');
  const themeJson = JSON.parse(themeJsonContent);
  const merged = injectEditorSettings(themeJson);

  fs.writeFileSync(themeJsonPath, JSON.stringify(merged, null, 2) + '\n');

  console.log(`Injected editor settings into theme.json`);
  console.log(`   Colors: ${editorPalette.length} (Extracted from tokens.css)`);
  console.log(`   Font sizes: ${editorFontSizes.length}`);
  console.log(`   Font families: ${editorFonts.length}`);
} catch (err) {
  console.error(`Failed to inject editor settings: ${err.message}`);
  process.exit(1);
}
