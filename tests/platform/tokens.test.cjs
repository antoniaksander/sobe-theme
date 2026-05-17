/**
 * Guard platform-owned token infrastructure from accidental client-fork edits.
 */

const { createHash } = require('node:crypto');
const { readFileSync } = require('node:fs');
const { join, resolve } = require('node:path');

const ROOT = resolve(__dirname, '../..');
const TOKENS_PATH = join(ROOT, 'resources/css/tokens.css');
const BASELINE_PATH = join(ROOT, 'tests/fixtures/tokens-css.sha256');

function sha256(buffer) {
  return createHash('sha256').update(buffer).digest('hex');
}

function readExpectedHash() {
  return readFileSync(BASELINE_PATH, 'utf8').trim().split(/\s+/)[0];
}

describe('platform tokens guard', () => {
  test('resources/css/tokens.css matches the committed platform baseline', () => {
    const expectedHash = readExpectedHash();
    const currentHash = sha256(readFileSync(TOKENS_PATH));

    if (currentHash !== expectedHash) {
      throw new Error(
        [
          'resources/css/tokens.css has diverged from the platform baseline.',
          '',
          'Do not edit tokens.css in a client fork. Put brand color, font, and token',
          'overrides in resources/css/client-tokens.css instead.',
          '',
          'client-tokens.css is configured by package.json ->',
          'wpBoilerplate.themeJsonTokenOverrides and is loaded after tokens.css so',
          'its values win the cascade.',
          '',
          'If this is an intentional PLATFORM token change, review the diff and',
          'update tests/fixtures/tokens-css.sha256 in the same commit.',
          '',
          `Expected: ${expectedHash}`,
          `Received: ${currentHash}`,
        ].join('\n'),
      );
    }
  });
});
