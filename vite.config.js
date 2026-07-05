import path from 'node:path';
import { defineConfig } from 'vite';
import { execSync } from 'node:child_process';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';
import { getBlockEntries } from './resources/scripts/blocks-entries.js';

// Set APP_URL if it doesn't exist for Laravel Vite plugin
if (!process.env.APP_URL) {
  process.env.APP_URL = 'http://example.test';
}

// WordPress serves built assets from the theme's actual folder name on disk,
// which varies per fork/clone (e.g. a GitHub zip download lands in
// "sobe-theme-main", a client fork gets renamed to "roxder", etc). Deriving
// the base path from the real working directory name — rather than
// hardcoding it — means the build always matches wherever the theme actually
// lives, with no manual step to remember or get wrong per fork.
const themeFolderName = path.basename(process.cwd());

const BUNDLE_LIMITS = {
  css: 150 * 1024, // 150 kB
  js: 250 * 1024, // 250 kB
};

// We create an empty string to hold our log until the very end!
let delayedSizeReport = '';

export default defineConfig({
  base: `/wp-content/themes/${themeFolderName}/public/build/`,
  plugins: [
    tailwindcss(),
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/editor.css',
        'resources/js/editor.js',
        'resources/css/woocommerce.css',
        'resources/js/product-gallery.js',
        'resources/js/sobe-page-transitions.js',
        'resources/js/shop-load-more.js',
        ...getBlockEntries(),
      ],
      refresh: true,
      assets: ['resources/images/**'],
    }),

    wordpressPlugin(),

    wordpressThemeJson({
      disableTailwindColors: false,
      disableTailwindFonts: false,
      disableTailwindFontSizes: false,
      disableTailwindBorderRadius: true,
    }),

    // Custom plugin: Calculate sizes, but DELAY the console.log
    {
      name: 'bundle-size-budget',
      generateBundle(options, bundle) {
        delayedSizeReport = '\n📊 Bundle Size Report:\n';

        for (const [fileName, chunk] of Object.entries(bundle)) {
          const base = fileName.split('/').pop();
          if (!base.startsWith('app')) continue;

          const ext = base.split('.').pop();
          if (!BUNDLE_LIMITS[ext]) continue;

          const bytes =
            chunk.type === 'asset'
              ? typeof chunk.source === 'string'
                ? chunk.source.length
                : chunk.source.byteLength
              : chunk.code.length;

          const sizeKb = (bytes / 1024).toFixed(2);
          const limitKb = (BUNDLE_LIMITS[ext] / 1024).toFixed(0);

          if (bytes > BUNDLE_LIMITS[ext]) {
            this.error(
              `Bundle budget exceeded: ${base} is ${sizeKb} kB — limit is ${limitKb} kB`,
            );
          } else {
            // Save the string instead of printing it!
            delayedSizeReport += ` ${base}: ${sizeKb} kB (Limit: ${limitKb} kB)\n`;
          }
        }
      },
    },

    // Custom plugin: Fires after Vite is completely finished printing its files
    {
      name: 'inject-editor-settings',
      closeBundle() {
        // Print the delayed size report right here at the bottom!
        if (delayedSizeReport) {
          console.log(delayedSizeReport);
        }

        const scriptPath = './resources/scripts/build-theme-json.js';
        console.log('🎨 Injecting Tailwind editor settings...');
        try {
          execSync(`node ${scriptPath}`, { stdio: 'inherit' });
        } catch (e) {
          console.error('Failed to inject editor settings:', e.message);
        }
      },
    },
  ],
  server: {
    host: '127.0.0.1',
    cors: true,
    strictPort: true,
    port: 5173,
  },
  resolve: {
    alias: [
      { find: '@scripts', replacement: '/resources/js' },
      { find: '@styles', replacement: '/resources/css' },
      { find: '@images', replacement: '/resources/images' },
      { find: /^\.\.\/\.\.\/js\//, replacement: '/resources/js/' },
    ],
  },
  optimizeDeps: {
    include: ['react', 'react-dom'],
  },
  esbuild: {
    jsx: 'automatic',
    jsxImportSource: 'react',
  },
});
