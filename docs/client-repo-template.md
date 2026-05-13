# Client Repo Documentation Template

Each private client repo should have its own `docs/` folder.

Recommended files:

- `docs/client-overrides.md`
- `docs/plugins.md`
- `docs/upstream-sync-notes.md`

## docs/client-overrides.md

Document:

- major deviations from the public starter
- client-specific headers, footers, templates, blocks
- files that should not be overwritten blindly from upstream
- architectural exceptions made for the client

Suggested sections:

- Branding layer
- Template overrides
- WooCommerce customizations
- Client-only blocks
- High-conflict files

## docs/plugins.md

Document:

- installed plugins
- versions tested
- which theme areas they affect
- any required hooks, filters, templates, or JS integrations

Suggested table:

| Plugin | Version | Status | Affected Areas | Notes |
| --- | --- | --- | --- | --- |

## docs/upstream-sync-notes.md

Document:

- when the client repo was synced from the public starter
- what was merged or cherry-picked
- what was intentionally skipped
- recurring conflicts

Suggested entry format:

## YYYY-MM-DD

- Pulled from upstream commit(s):
- Accepted:
- Manually ported:
- Rejected / skipped:
- Follow-up needed:
