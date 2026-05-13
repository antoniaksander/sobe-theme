# Merge Strategy

This repo is the public starter. Client repositories should pull from it selectively.

## Repo Roles

- `WP-boilerplate`: public base starter
- client repo, e.g. `Client-Theme`: private implementation for one client

Recommended remote layout inside a client repo:

- `origin` -> the private client repo
- `upstream` -> the public `WP-boilerplate` repo

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

When updating a client repo from upstream:

1. Fetch upstream changes.
2. Review the affected files before merging.
3. Merge or cherry-pick generic changes.
4. Manually port mixed changes where a shared file also contains client logic.
5. Record notable conflicts in the client repo docs.

Avoid treating `git pull upstream main` as an automatic full sync for every client.

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
