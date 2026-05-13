# Merge Strategy

This repo is the public starter. Client repositories should pull from it selectively.

## Version Policy

- `v1.x.x` (`v1.0.0-rich-sobe-starter`): full theme with blocks, patterns, customizer, sidecart, dark mode, wishlist, templates, WooCommerce presentation, and demo UI.
- `v2.x.x` (`v2.0.0-thin-infra`): infrastructure only, including block registration, Vite assets, generic WordPress setup, WooCommerce wrappers, minimal Blade shell, and hardening.

Existing clients that started on the rich Sobe tree should pin to the `v1.x.x` line. Do not treat `v2.0.0` as an automatic upgrade just because it is newer; it intentionally removes presentation files that rich clients still own.

New clients should fork the demo once for the initial working theme, then set `upstream` to `WP-boilerplate` and track the `v2.x.x` line.

Breaking changes are tagged as major versions. Minor and patch releases on the active thin-infra line are expected to be safe infrastructure updates.

## Repo Roles

- `WP-boilerplate`: public thin infrastructure upstream
- `WP-boilerplate-demo`: rich Sobe starter used only as a one-time fork source
- client repo, e.g. `Client-Theme`: private implementation for one client

Recommended remote layout inside a client repo:

- `origin` -> the private client repo
- `upstream` -> the public `WP-boilerplate` repo

Never set a client repo's `upstream` remote to `WP-boilerplate-demo`.

## Direction Of Flow

Default direction:

- generic improvements move from `WP-boilerplate` into client repos

Selective reverse flow:

- client repos may contribute generic fixes back into `WP-boilerplate`
- client-specific design, plugin logic, templates, and business rules do not

## What Belongs In The Starter

Usually safe to share:

- build tooling
- block scaffolding conventions
- token architecture
- generic utilities
- reusable WooCommerce infrastructure
- accessibility fixes
- performance fixes
- compatibility helpers that are broadly useful

Usually client-specific:

- branding
- templates and layouts
- headers and footers
- custom blocks for one client
- plugin-specific business logic
- checkout / cart behavior tied to a specific stack

## Sync Policy

When updating a new v2 client repo from upstream:

1. Fetch upstream changes.
2. Review the affected files before merging.
3. Merge or cherry-pick generic changes.
4. Record notable conflicts in the client repo docs.

For existing v1 rich clients:

1. Keep the client pinned to `v1.0.0-rich-sobe-starter` unless a v2 rebase is planned.
2. Do not merge `upstream/main` wholesale.
3. Cherry-pick only specific v2 security or infrastructure fixes.
4. Rebase onto v2 only during an intentional maintenance window after extracting client presentation into client-owned files.

## Conflict Rules

If a file conflicts repeatedly across clients, it is in the wrong layer.

Refactor to reduce shared volatility:

- move reusable logic into helpers or services
- keep client presentation in client templates
- isolate plugin adapters by plugin

Good sign:

- utility modules merge cleanly

Bad sign:

- the same header, Woo template, or checkout file conflicts in many clients

## Update Classes

### Must Adopt

- security fixes
- fatal bug fixes
- compatibility updates for WordPress / WooCommerce / PHP

### Usually Worth Adopting

- accessibility fixes
- performance improvements
- reusable architectural cleanups

### Optional Feature Updates

- new block families
- new integrations
- new design systems
- major editor feature additions

Optional feature updates should be adopted per client, not forced into every project.
