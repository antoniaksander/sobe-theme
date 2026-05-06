/**
 * Block save() tests.
 * Verifies every block's save() returns null (Blade renders the frontend).
 */

const { readFileSync } = require('node:fs');
const { resolve, join } = require('node:path');

const ROOT = resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(join(ROOT, 'resources/blocks/blocks-manifest.json'), 'utf8'));
const slugs = Object.keys(manifest);

describe.each(slugs)('block "%s" — save()', (slug) => {
  let save;

  beforeAll(() => {
    // babel-jest transforms JSX → CJS in the test env (see babel.config.json).
    // export default → module.exports.default after CJS transform.
    save = require(join(ROOT, `resources/blocks/${slug}/save.jsx`)).default;
  });

  test('returns null (frontend rendered by Blade)', () => {
    expect(save()).toBeNull();
  });
});
