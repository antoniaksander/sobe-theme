/** @type {import('jest').Config} */
module.exports = {
  testEnvironment: 'node',

  // Babel transforms .cjs, .js, and .jsx — the Babel test env (see babel.config.json)
  // converts import/export to CommonJS so Jest can load them without --experimental-vm-modules.
  transform: {
    '^.+\\.(cjs|js|jsx)$': 'babel-jest',
  },

  // Only match .test.cjs files so the .cjs extension explicitly opts out of
  // package.json "type": "module" and Jest loads them as CommonJS.
  testMatch: ['**/tests/**/*.test.cjs'],

  moduleNameMapper: {
    // SCSS imports in block files are irrelevant to unit tests
    '\\.scss$': '<rootDir>/tests/__mocks__/style.cjs',
  },
};
