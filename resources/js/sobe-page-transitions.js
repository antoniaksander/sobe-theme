/**
 * Page transitions engine. Wires Swup to the lifecycle registry. Disabled by default;
 * enabled via config/theme.php page_transitions.enabled flag. Forks customize via
 * 'sobe/page_transitions/*' PHP filters.
 */

import Swup from 'swup';
import SwupHeadPlugin from '@swup/head-plugin';
import SwupScriptsPlugin from '@swup/scripts-plugin';
import { destroyPage, initPage, markPageMounted } from './sobe-reinit.js';
import { mergeBodyClasses } from './body-class-merge.js';
import { shouldIgnoreTransitionVisit } from './page-transition-url.js';

const config = window.sobePageTransitionsConfig;

if (config?.enabled) {
  const swup = new Swup({
    containers: [config.containerSelector || '#main'],
    linkSelector: 'a[href]:not([data-no-swup]):not([target="_blank"])',
    cache: true,
    plugins: [
      new SwupHeadPlugin({
        persistTags: 'style[data-emotion], style[data-styled], link[rel="stylesheet"][data-keep]',
      }),
      new SwupScriptsPlugin({
        head: true,
        body: false,
        optin: true,
      }),
    ],
    requestHeaders: {
      'X-Requested-With': 'swup',
      Accept: 'text/html, application/xhtml+xml',
    },
    ignoreVisit: (url) => shouldIgnoreTransitionVisit(url, config.excludedUrls || []),
  });

  const remountCurrentPage = () => {
    const container = document.querySelector(config.containerSelector || '#main');
    initPage(container || document);
  };

  swup.hooks.on('visit:start', () => {
    document.dispatchEvent(new CustomEvent('sobe:shell-reset'));
    destroyPage();
  });

  swup.hooks.on('content:replace', (visit, { page } = {}) => {
    const incomingHtml = page?.html || visit?.to?.html;
    if (incomingHtml) {
      mergeBodyClasses(incomingHtml, {
        preserveExact: config.preserveBodyClasses || ['admin-bar', 'logged-in'],
        preservePairs: [['customize-support', 'no-customize-support']],
        preservePrefixes: ['sobe-js-'],
      });
    }
  });

  swup.hooks.on('page:view', () => {
    const container = document.querySelector(config.containerSelector || '#main');
    initPage(container || document);

    // Refresh ScrollTrigger after scroll restoration completes. Without this,
    // reveal animations gated by ScrollTrigger don't fire on back-navigation
    // because triggers were created against the previous page's scroll positions.
    // The double-defer (rAF + setTimeout 0) waits for the browser to paint the
    // restored scroll position before re-measuring.
    requestAnimationFrame(() => {
      setTimeout(() => {
        window.ScrollTrigger?.refresh();
      }, 0);
    });
  });

  swup.hooks.on('visit:end', () => {
    markPageMounted();
  });

  swup.hooks.on('fetch:error', remountCurrentPage);
  swup.hooks.on('fetch:timeout', remountCurrentPage);
  swup.hooks.on('visit:abort', remountCurrentPage);

  if (typeof window !== 'undefined') {
    window.sobeSwup = swup;
  }
}
