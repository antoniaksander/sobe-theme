/**
 * Reads blocks-manifest.json and returns Vite input array entries for all
 * registered blocks. File-existence checks ensure view.js is only included
 * for blocks that actually have one — no manual updates to vite.config.js needed.
 */

import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';

export function getBlockEntries() {
  const manifest = JSON.parse(
    readFileSync(resolve('./resources/blocks/blocks-manifest.json'), 'utf8'),
  );

  return Object.keys(manifest).flatMap((slug) => {
    const base = `resources/blocks/${slug}`;
    return [
      `${base}/index.jsx`,
      `${base}/style.scss`,
      `${base}/editor.scss`,
      `${base}/view.js`,
    ].filter((p) => existsSync(resolve(p)));
  });
}
