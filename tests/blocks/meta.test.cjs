/**
 * Block metadata tests.
 * Validates block.json for every slug in blocks-manifest.json.
 */

const { readFileSync, existsSync } = require('node:fs');
const { resolve, join } = require('node:path');

const ROOT = resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(join(ROOT, 'resources/blocks/blocks-manifest.json'), 'utf8'));
const slugs = Object.keys(manifest);

describe.each(slugs)('block "%s" — block.json', (slug) => {
  let meta;

  beforeAll(() => {
    const blockJsonPath = join(ROOT, `resources/blocks/${slug}/block.json`);
    expect(existsSync(blockJsonPath)).toBe(true);
    meta = JSON.parse(readFileSync(blockJsonPath, 'utf8'));
  });

  test('name matches slug', () => {
    expect(meta.name).toBe(`sobe/${slug}`);
  });

  test('apiVersion is 3', () => {
    expect(meta.apiVersion).toBe(3);
  });

  test('supports.html is false', () => {
    expect(meta.supports?.html).toBe(false);
  });

  test('category matches manifest', () => {
    expect(meta.category).toBe(manifest[slug].category);
  });
});
