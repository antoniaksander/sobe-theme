# Deploying A Client Fork

The platform repo does not deploy anywhere. Client forks copy
[`docs/examples/deploy.yml`](examples/deploy.yml) to
`.github/workflows/deploy.yml` and configure it through secrets. A push to
`main` then runs the full CI first and only deploys when every check passes.

## First-Time Setup

1. Turn on SSH access on the host.
2. Create a **separate deploy key per site** (no passphrase) and add its public half to the host's authorised keys:
   `ssh-keygen -t ed25519 -f deploy_key -C "<site>-deploy" -N ""`
3. Get the host's SSH fingerprint once and keep it as a secret, instead of trusting whatever answers on every run:
   `ssh-keyscan -p <port> <host>`
4. In the GitHub repo, create an environment called `production` and add these secrets to it:

   | Secret | Example |
   |---|---|
   | `SSH_HOST` | `203.0.113.10` |
   | `SSH_PORT` | `22` |
   | `SSH_USER` | `deploy` |
   | `DEPLOY_SSH_KEY` | private half of the deploy key |
   | `SSH_KNOWN_HOSTS` | output of the `ssh-keyscan` line above |
   | `DEPLOY_PATH` | `/var/www/site/wp-content/themes/<theme>/` (trailing slash) |
   | `WP_ROOT` | `/var/www/site` |

5. Run the workflow manually (**Actions → Deploy to production → Run workflow**) with `dry_run` on. Check in the rsync list that `DEPLOY_PATH` is the theme folder that is actually active; a wrong path creates a second copy of the theme next to the real one.
6. Run it again with `dry_run` off, or merge a PR to `main`.

A repo without these secrets (the platform repo itself, demo forks) skips the deploy job with a notice instead of failing.

## Branch Flow

`feature/*` or `fix/*` → pull request → CI → merge to `main` → deploy.

`ci.yml` runs on pull requests and, through `workflow_call`, as the first job of every deploy. Private repos on free GitHub plans cannot protect `main`, so a direct push to `main` deploys straight away. Always go through a PR.

## What The Deploy Does

1. Runs the full CI (`ci.yml`).
2. `composer install --no-dev --optimize-autoloader`, then `npm ci && npm run build`, and fails if `public/build/manifest.json` is missing.
3. `rsync --delete` to `DEPLOY_PATH`, protecting `vendor/` and `public/build/` and excluding tooling, tests, docs, `node_modules` and `public/hot`.
4. `wp acorn optimize:clear` from `WP_ROOT`.
5. Best-effort page cache purge (`wp litespeed-purge all`; swap in your caching plugin's command).

Fork-specific edits to `deploy.yml` belong in the client repo. Expect them in merge conflicts when syncing from upstream.

## Lockfile

CI pins npm to `.site-requirements.json` (`npm ci` refuses a lockfile written by a different npm). After changing dependencies, regenerate the lockfile with the pinned version:

```bash
npx npm@$(jq -r .npm .site-requirements.json) install --package-lock-only
```

## Launch Checklist

- PHP on the host matches `composer.json` and `.site-requirements.json`.
- The page-cache/optimisation plugin excludes `/public/build/` from JS combine, defer and minify.
- At least one deploy has run successfully before DNS is switched.
- A wp-admin zip upload is a fallback only, not the normal way to ship.

## Pitfalls Seen In Real Forks

| Pitfall | Effect | Guard |
|---|---|---|
| `rsync --delete` without building `vendor/` or `public/build/` | Site down: "Error locating autoloader" | Build steps plus protect filters |
| Deploy not gated on CI | Broken code goes live on push | `needs: checks` |
| `php -r 'opcache_reset()'` over SSH | No effect: CLI and web PHP have separate OPcache | Leave it out; restart PHP from the host panel if ever needed |
| `ssh-keyscan` on every run | Silently trusts whichever server answers | Pin `SSH_KNOWN_HOSTS` |
| Lockfile generated with a newer local npm | `npm ci` fails in CI and in deploys | Regenerate with the pinned npm |
