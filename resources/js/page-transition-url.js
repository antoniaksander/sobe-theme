const DEFAULT_BASE_URL = 'http://sobe.local';

function getBaseUrl() {
  return typeof window !== 'undefined' && window.location?.origin
    ? window.location.origin
    : DEFAULT_BASE_URL;
}

function parseUrl(value) {
  return new URL(value, getBaseUrl());
}

function normalizePath(path) {
  const parsedPath = path.split(/[?#]/)[0] || '/';
  const withLeadingSlash = parsedPath.startsWith('/') ? parsedPath : `/${parsedPath}`;
  const withoutTrailingSlash = withLeadingSlash.replace(/\/+$/, '');

  return withoutTrailingSlash || '/';
}

function pathMatchesPattern(pathname, pattern) {
  const target = normalizePath(pathname);
  const excluded = normalizePath(pattern);

  return target === excluded || target.startsWith(`${excluded}/`);
}

function queryMatchesPattern(searchParams, pattern) {
  const [rawKey, rawValue = ''] = pattern.split('=');
  const key = rawKey.trim();

  if (!key) return false;
  if (!searchParams.has(key)) return false;

  return rawValue === '' || searchParams.get(key) === rawValue;
}

export function shouldIgnoreTransitionVisit(url, patterns = []) {
  let parsed;

  try {
    parsed = parseUrl(url);
  } catch {
    return false;
  }

  return patterns.some((rawPattern) => {
    const pattern = String(rawPattern || '').trim();

    if (!pattern) return false;

    if (pattern.includes('=')) {
      return queryMatchesPattern(parsed.searchParams, pattern);
    }

    return pathMatchesPattern(parsed.pathname, pattern);
  });
}
