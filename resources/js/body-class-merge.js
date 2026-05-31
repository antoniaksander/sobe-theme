/**
 * Body class merge helper.
 *
 * Parses incoming page HTML, merges incoming body classes with live runtime
 * classes that should survive navigation, and writes the merged className to
 * document.body.
 *
 * Exports: mergeBodyClasses.
 */

const DEFAULT_PRESERVE_EXACT = ['admin-bar', 'logged-in'];
const DEFAULT_PRESERVE_PAIRS = [['customize-support', 'no-customize-support']];
const DEFAULT_PRESERVE_PREFIXES = ['sobe-js-'];

/**
 * Merge current runtime body classes with incoming page body classes.
 *
 * @param {string} incomingHtmlString
 * @param {object} options
 * @param {string[]} options.preserveExact
 * @param {Array<[string, string]>} options.preservePairs
 * @param {string[]} options.preservePrefixes
 * @returns {string}
 */
export function mergeBodyClasses(incomingHtmlString, options = {}) {
  const {
    preserveExact = DEFAULT_PRESERVE_EXACT,
    // WordPress emits customize-support OR no-customize-support based on runtime feature detection.
    // They are mutually exclusive. Treat as a pair and prefer the live runtime value when incoming has
    // neither, to avoid hiding admin bar Customize links after navigation.
    preservePairs = DEFAULT_PRESERVE_PAIRS,
    preservePrefixes = DEFAULT_PRESERVE_PREFIXES,
  } = options;

  try {
    const parser = new DOMParser();
    const incomingDocument = parser.parseFromString(incomingHtmlString, 'text/html');
    const incomingClasses = new Set(incomingDocument.body.classList);
    const currentClasses = new Set(document.body.classList);

    for (const className of preserveExact) {
      if (currentClasses.has(className)) {
        incomingClasses.add(className);
      }
    }

    for (const prefix of preservePrefixes) {
      for (const className of currentClasses) {
        if (className.startsWith(prefix)) {
          incomingClasses.add(className);
        }
      }
    }

    for (const [classA, classB] of preservePairs) {
      const incomingValue = [classA, classB].find((className) => incomingClasses.has(className));

      incomingClasses.delete(classA);
      incomingClasses.delete(classB);

      if (incomingValue) {
        incomingClasses.add(incomingValue);
        continue;
      }

      const currentValue = [classA, classB].find((className) => currentClasses.has(className));
      if (currentValue) {
        incomingClasses.add(currentValue);
      }
    }

    document.body.className = Array.from(incomingClasses).join(' ');
    return document.body.className;
  } catch (error) {
    console.error('[sobe body-class-merge] Failed to merge body classes.', error);
    return document.body.className;
  }
}
