# Changelog

## Unreleased

### Breaking

- None yet.

### Added

- None yet.

### Fixed

- None yet.

## v2.1.1 - 2026-05-17

### Added

- Added native client-namespace block manifest support so client blocks can declare explicit names such as `roxder/cta-banner` without patching platform block tests or pattern checks.
- Added optional client token override support for the editor `theme.json` build via `wpBoilerplate.themeJsonTokenOverrides` in `package.json`.

## v2.1.0 - 2026-05-17

### Fixed

- Fixed layout shell rendering so changing the client `prefix` no longer renames the default `sobe/site-header` and `sobe/site-footer` blocks.
- Added header navigation fallback output for fresh installs without an assigned primary menu.
- Fixed WooCommerce catalog column body classes so client `prefix` changes do not break the platform CSS selectors.
- Added default footer fallback links for fresh installs without footer widgets or a footer menu.
- Added a Blade fallback homepage for front pages without meaningful content.

## v2.0.2 - 2026-05-17

### Fixed

- Rewrote the client fork guide with a complete initial identity checklist, post-activation WordPress setup, client block workflow, and branch/PR upstream sync process.
- Clarified which `sobe` references client forks change and which remain upstream contracts.
- Aligned upstream sync and merge strategy docs with conflict-resolution guidance for client forks.

## v2.0.1

### Fixed

- Clarified repository hygiene after the v2 platform release, including ignored internal documentation and generated local files.

## v2.0.0

### Added

- Released the v2 platform contract for client forks, including the public block library, WooCommerce layer, search, side cart, dark mode, token system, hook contracts, and validation tooling.
