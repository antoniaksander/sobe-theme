const { execFileSync } = require('node:child_process');
const { mkdtempSync, mkdirSync, readFileSync, rmSync, writeFileSync } = require('node:fs');
const { tmpdir } = require('node:os');
const { join, resolve } = require('node:path');

const ROOT = resolve(__dirname, '../..');
const SCRIPT = join(ROOT, 'resources/scripts/make-block.js');

function createFixture() {
  const cwd = mkdtempSync(join(tmpdir(), 'sobe-make-block-'));
  mkdirSync(join(cwd, 'resources/blocks'), { recursive: true });
  writeFileSync(join(cwd, 'resources/blocks/blocks-manifest.json'), '{}\n');

  return cwd;
}

function runMakeBlock(cwd, args) {
  return execFileSync(process.execPath, [SCRIPT, ...args], {
    cwd,
    encoding: 'utf8',
  });
}

function readJson(path) {
  return JSON.parse(readFileSync(path, 'utf8'));
}

describe('make-block scaffold', () => {
  let fixtures = [];

  afterEach(() => {
    for (const cwd of fixtures) {
      rmSync(cwd, { recursive: true, force: true });
    }
    fixtures = [];
  });

  test('accepts client category slugs', () => {
    const cwd = createFixture();
    fixtures.push(cwd);

    runMakeBlock(cwd, ['client/feature-card', '--category=client-widgets']);

    const blockJson = readJson(join(cwd, 'resources/blocks/client/feature-card/block.json'));
    const manifest = readJson(join(cwd, 'resources/blocks/blocks-manifest.json'));

    expect(blockJson.name).toBe('client/feature-card');
    expect(blockJson.category).toBe('client-widgets');
    expect(manifest['client/feature-card']).toEqual({ category: 'client-widgets' });
  });

  test('defaults client-namespaced blocks to the namespace category', () => {
    const cwd = createFixture();
    fixtures.push(cwd);

    runMakeBlock(cwd, ['client/feature-card']);

    const blockJson = readJson(join(cwd, 'resources/blocks/client/feature-card/block.json'));
    const manifest = readJson(join(cwd, 'resources/blocks/blocks-manifest.json'));

    expect(blockJson.category).toBe('client');
    expect(manifest['client/feature-card']).toEqual({ category: 'client' });
  });

  test('keeps shorthand platform blocks in the Sobe general category', () => {
    const cwd = createFixture();
    fixtures.push(cwd);

    runMakeBlock(cwd, ['feature-card']);

    const blockJson = readJson(join(cwd, 'resources/blocks/sobe/feature-card/block.json'));
    const manifest = readJson(join(cwd, 'resources/blocks/blocks-manifest.json'));

    expect(blockJson.name).toBe('sobe/feature-card');
    expect(blockJson.category).toBe('sobe-general');
    expect(manifest['sobe/feature-card']).toEqual({ category: 'sobe-general' });
  });

  test('generates namespace-aware root class templates', () => {
    const cwd = createFixture();
    fixtures.push(cwd);

    runMakeBlock(cwd, ['client/feature-card']);

    const blade = readFileSync(join(cwd, 'resources/views/blocks/client/feature-card.blade.php'), 'utf8');
    const style = readFileSync(join(cwd, 'resources/blocks/client/feature-card/style.scss'), 'utf8');

    expect(blade).toContain("$componentClass = $blockBaseClass ?? 'feature-card';");
    expect(blade).toContain('$namespaceClass = $blockNamespaceClass ?? "{$componentClass}--client";');
    expect(blade).toContain("'class' => trim(\"{$componentClass} {$namespaceClass}\"),");
    expect(style).toContain('.feature-card {');
    expect(style).toContain('.feature-card--client {');
  });
});
